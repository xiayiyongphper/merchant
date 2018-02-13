<?php
namespace common\models;

use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\message\customer\CustomerResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;
use yii\db\ActiveQuery;

/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/6/30
 * Time: 10:51
 */
class SeckillHelper
{
    const EXPIRE_SECONDS = 7200;
    const IS_CACHE = false;
    const DEFAULT_PAGE_SIZE = 10;

    /**
     * @var \service\message\customer\CustomerResponse
     */
    private $customer;

    public function __construct(CustomerResponse $customer)
    {
        $this->customer = $customer;
    }

    /**
     * 获取商品列表，先从缓存里面获取，如果没有，则从数据库获取
     * @param int $actId
     * @param int $page
     * @return array
     */
    public function getProducts($actId, $page = null)
    {
        $page = $page ? $page : 1;
        if (false === ($ret = $this->getProductsByCache($actId, $page))) {
            $ret = $this->getProductsByDB($actId, $page);
            /* 不为空时，把pages信息和商品信息以及商品ID缓存到redis */
            if ($ret) {
                /** @var Pagination $pages */
                list($pages, $products) = $ret;
                if ($products && is_array($products)) {
                    $actPageCacheKey = $this->getActPageCacheKey($actId, $page);
                    $productsKey = $this->getProductsCacheKey();
                    foreach ($products as $k => $product) {
                        $products[$k] = serialize($product);
                    }
                    ToolsAbstract::getRedis()->hMset($productsKey, $products);
                    ToolsAbstract::getRedis()->expire($productsKey, self::EXPIRE_SECONDS + 10); // 比其他多10秒
                    $value = json_encode([
                        $pages->getTotalCount(),
                        array_keys($products),
                    ]);
                    ToolsAbstract::getRedis()->set($actPageCacheKey, $value, self::EXPIRE_SECONDS);
                    self::addToProductCacheKey($this->customer->getCity(), $actPageCacheKey);
                    unset($products, $proIdKeys);
                }
            }
        }
        return $ret ? $ret : [];
    }

    /**
     * 从缓存获取商品列表
     *
     * @since 2.6.6
     * @author zqy
     * @param int $actId
     * @param int $page
     * @return array
     */
    public function getProductsByCache($actId, $page)
    {
        if (!self::IS_CACHE) {
            return false;
        }

        $actPageCacheKey = $this->getActPageCacheKey($actId, $page);
        $result = ToolsAbstract::getRedis()->get($actPageCacheKey);
        if (false === $result) {
            return false;
        }

        $result = json_decode($result, 1);
        if (!$result) {
            return [];
        }

        list($total, $proIds) = $result;
        $pages = $this->getPagination($total, $page);
        if (!$proIds) {
            return [$pages, []];
        }

        $proIdsKey = $this->getProductsCacheKey();
        $productsInJson = ToolsAbstract::getRedis()->hMGet($proIdsKey, $proIds);
        if (!$productsInJson) {
            return [$pages, []];
        }

        $products = [];
        foreach ($proIds as $proId) {
            $products[$proId] = $productsInJson[$proId] ? unserialize($productsInJson[$proId]) : [];
        }

        return [$pages, $products];
    }

    /**
     *
     * @since 2.6.6
     * @author zqy
     * @param int $actId
     * @param int $page
     * @return array
     */
    public function getProductsByDB($actId, $page)
    {
        $wholesalerIds = MerchantResourceAbstract::getWholesalerIdsByAreaId($this->customer->getAreaId());
        if (!$wholesalerIds) {
            return [];
        }

        /* 先判断有没有商品，没有则直接返回 */
        $productsQuery = SpecialProduct::find()->where([
            'activity_id' => $actId,
            'wholesaler_id' => $wholesalerIds,
            'status' => SpecialProduct::STATUS_ENABLED,
            'type2' => SpecialProduct::TYPE_SECKILL,
            'city' => $this->customer->getCity(),
        ]);

        $count = $this->getProductsCount($actId, $productsQuery);
        if ($count <= 0) {
            return [];
        }

        $pages = $this->getPagination($count, $page);
        /* 获取商品列表 */
        $newProductsQuery = clone $productsQuery;
        $products = $newProductsQuery->offset($pages->getOffset())
            ->limit($pages->getLimit())
            ->orderBy('entity_id ASC')
            ->all();

        /* 获取格式化的商品数据 */
        $formatProducts = (new ProductHelper())
            ->initWithProductArray($products, $this->customer->getCity())
            ->getTags()->getData();

        return [$pages, $formatProducts];
    }

    /**
     * 检查是否允许（检测黑名单和灰名单）。
     *
     * @param array $activity
     * @param integer $customerId
     * @param integer $cityId
     * @param integer $areaId
     * @return bool
     */
    public static function checkAccess($activity, $customerId, $cityId, $areaId)
    {
        if (empty($activity['entity_id']) || !$customerId) {
            return false;
        }

        /* 黑名单存在则直接返回false */
        $blackListKey = sprintf('sk_blacklist_%s', $cityId);
        if (ToolsAbstract::getRedis()->hGet($blackListKey, $customerId)) {
            return false;
        }

        /* 灰名单存在则直接返回false */
        $grayListKey = sprintf('sk_graylist_%s', $cityId);
        if (ToolsAbstract::getRedis()->hGet($grayListKey, $customerId)) {
            return false;
        }

        return true;
    }

    /**
     * 添加到商品缓存键集，可以用来清空缓存
     *
     * @param int $city
     * @param string $key
     */
    private static function addToProductCacheKey($city, $key)
    {
        $cacheKey = sprintf('sk_pro_keys_%s', $city);
        ToolsAbstract::getRedis()->sAdd($cacheKey, $key);
    }

    /**
     * @param int $actId
     * @param ActiveQuery $productsQuery
     * @return false|int
     */
    private function getProductsCount($actId, $productsQuery)
    {
        if (!self::IS_CACHE) {
            return $productsQuery->count();
        }

        $redisKey = $this->getActTotalCacheKey($actId);
        $result = ToolsAbstract::getRedis()->get($redisKey);
        if ($result !== false) {
            return (int)$result;
        }

        $count = $productsQuery->count();
        ToolsAbstract::getRedis()->set($redisKey, $count, self::EXPIRE_SECONDS);
        self::addToProductCacheKey($this->customer->getCity(), $redisKey);
        return $count;
    }

    /**
     *
     * @since 2.6.6
     * @author zqy
     * @param int $actId
     * @return string
     */
    private function getActTotalCacheKey($actId)
    {
        return sprintf(
            'sk_plist_%s_%s_%s_total',
            $this->customer->getCity(),
            $this->customer->getAreaId(),
            $actId
        );
    }

    /**
     *
     * @since 2.6.6
     * @author zqy
     * @param int $actId
     * @param int $page
     * @return string
     */
    private function getActPageCacheKey($actId, $page)
    {
        return sprintf(
            'sk_plist_%s_%s_%s_%s',
            $this->customer->getCity(),
            $this->customer->getAreaId(),
            $actId,
            $page
        );
    }

    /**
     * @return string
     */
    private function getProductsCacheKey()
    {
        return 'sk_plist_' . $this->customer->getCity();
    }

    /**
     * @param int $count
     * @param int $page
     * @return Pagination
     */
    private function getPagination($count, $page)
    {
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setCurPage($page);
        $pages->setPageSize(self::DEFAULT_PAGE_SIZE);
        return $pages;
    }
}