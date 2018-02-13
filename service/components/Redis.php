<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/29
 * Time: 14:58
 */
namespace service\components;

use common\models\LeMerchantStore;
use common\models\Products;
use service\resources\Exception;
use Yii;
use yii\helpers\ArrayHelper;

class Redis
{
    const REDIS_KEY_WHOLESALERS = 'wholesalers';
    const REDIS_KEY_PRODUCTS = 'products';
    const REDIS_KEY_EVENTS = 'events_queue';
    const REDIS_KEY_PMS_CATEGORIES = 'pms_categories';
    const REDIS_KEY_CUSTOMERIDS = 'customerids';
    const SOAP_PMS_URL = 'http://pms.lelai.com/api/soap?wsdl';

    /**
     * @param $wholesalerId
     * @param $wholesaler
     * @return bool
     */
    public static function setWholesaler($wholesalerId, $wholesaler)
    {
        if (!$wholesalerId) {
            return false;
        }
        $redis = Tools::getRedis();
        $existed = $redis->exists(self::REDIS_KEY_WHOLESALERS);
        $redis->hSet(self::REDIS_KEY_WHOLESALERS, $wholesalerId, $wholesaler);
        if (!$existed) {
            $redis->expire(self::REDIS_KEY_WHOLESALERS, 3600);
        }
    }

    /**
     * 缓存供应商信息
     * @todo setWholesalers([1=>serialize($wholesaler),2=>serialize($wholesaler)])
     * @param $wholesalers
     * @return bool|null
     */
    public static function setWholesalers($wholesalers)
    {
        if (!$wholesalers) {
            return false;
        }
        if (is_array($wholesalers) && count($wholesalers) > 0) {
            $redis = Tools::getRedis();
            $existed = $redis->exists(self::REDIS_KEY_WHOLESALERS);
            $redis->hMSet(self::REDIS_KEY_WHOLESALERS, $wholesalers);
            if (!$existed) {
                $redis->expire(self::REDIS_KEY_WHOLESALERS, 3600);
            }
        }
    }

    /**
     * 根据供应商ID获取供应商信息
     * @todo getWholesalers([1,2,3,4])
     * @param $source
     * @return array|bool|null|string
     */
    public static function getWholesalers($source)
    {
        if (!is_array($source)) {
            $source = [$source];
        }
        $redis = Tools::getRedis();
        $wholesalers = $redis->hMGet(self::REDIS_KEY_WHOLESALERS, $source);
        if ($wholesalers) {
            $wholesalers = array_filter($wholesalers);
        }
        $matched = array();
        if (is_array($wholesalers)) {
            $matched = array_keys($wholesalers);
        }
        $diff = array_diff($source, $matched);
        if (count($diff) > 0) {
            $stores = LeMerchantStore::find()->where(['entity_id' => $diff])->asArray()->all();
            $diffProduct = [];
            foreach ($stores as $store) {
                // 处理返点小数位数问题
                $store['rebates'] = floatval($store['rebates']);
                $diffProduct[$store['entity_id']] = serialize($store);
                $wholesalers[$store['entity_id']] = serialize($store);
            }
            if (count($diffProduct) > 0) {
                $redis->hMSet(self::REDIS_KEY_WHOLESALERS, $diffProduct);
            }
        }
        return $wholesalers;
    }

    /**
     * 根据供应商ID获取对应的字段
     * @todo getWholesalersColumn([1,2,3,4],'store_name')
     * @param $wholesalerIds
     * @param $column
     * @return array
     */
    public static function getWholesalersColumn($wholesalerIds, $column)
    {
        $values = [];
        if ($wholesalerIds) {
            if (!is_array($wholesalerIds)) {
                $wholesalerIds = [$wholesalerIds];
            }
            $wholesalers = self::getWholesalers($wholesalerIds);
            foreach ($wholesalers as $wholesalerId => $wholesaler) {
                $wholesaler = unserialize($wholesaler);
                if (isset($wholesaler[$column])) {
                    $values[$wholesalerId] = $wholesaler[$column];
                } else {
                    $values[$wholesalerId] = '';
                }
            }
        }
        return $values;
    }

    public static function getPMSCategories($useCache = true)
    {
        $redis = Tools::getRedis();
        if (!$useCache || !$redis->exists(self::REDIS_KEY_PMS_CATEGORIES)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, ENV_PMS_API_CATEGORY_URL);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=UTF-8', 'Authorization:Bearer ' . ENV_PMS_API_TOKEN));
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $resultData = json_decode($result, true);
            //服务器错误 code != 0
            if ($resultData['code'] != 0) {
                return [];
            }
            $categories = $resultData['data'];
            /** @var \yii\Redis\Cache $redis */
            $_data = [];
            foreach ($categories as $category) {
                $category['id'] = $category['entity_id'];
                unset($category['entity_id']);
                $_data[$category['id']] = serialize($category);
            }
            $categoryCount = count($_data);
            if ($categoryCount) {
                $redis->hMSet(self::REDIS_KEY_PMS_CATEGORIES, $_data);
                $redis->expire(self::REDIS_KEY_PMS_CATEGORIES, 3600);
                \Yii::trace(sprintf('There is %s categories load from pms', $categoryCount));
            } else {
                \Yii::trace('There is no categories load from pms');
            }
            \Yii::trace(sprintf('Load categories from pms:%s', ENV_PMS_API_CATEGORY_URL));
        } else {
            \Yii::trace('Load categories from redis cache');
        }
        return $redis->hGetAll(self::REDIS_KEY_PMS_CATEGORIES);
    }

    /**
     * @param array|int $id
     * @return array
     */
    public static function getCategories($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        $id = array_filter($id);

        if (count($id) == 0) {
            return false;
        }

        $redis = Tools::getRedis();
        if (!$redis->exists(self::REDIS_KEY_PMS_CATEGORIES)) {
            self::getPMSCategories();
        }
        $categories = $redis->hMGet(self::REDIS_KEY_PMS_CATEGORIES, $id);

        $categoryArray = [];

        if(empty($categories)){
            return $categoryArray;
        }

        foreach ($categories as $key => $value) {
            if(!empty($value)){
                $categoryArray[$key] = unserialize($value);
            }
        }

        return $categoryArray;
    }

    /**
     * @return array
     */
    public static function getAllCategories()
    {
        $redis = Tools::getRedis();
        if (!$redis->exists(self::REDIS_KEY_PMS_CATEGORIES)) {
            self::getPMSCategories();
        }
        $categories = $redis->hGetAll(self::REDIS_KEY_PMS_CATEGORIES);
        $result = [];
        foreach ($categories as $key => $value) {
            $data = unserialize($value);
            if (isset($data['level']) && $data['level'] == 1) {
                $result[$key] = $data;
            }
        }
        return $result;
    }

    /**
     * @param array|int $id
     * @return array
     */
    public static function getCategory($id)
    {
        $redis = Tools::getRedis();
        if (!$redis->exists(self::REDIS_KEY_PMS_CATEGORIES)) {
            self::getPMSCategories();
        }
        $category = $redis->hGet(self::REDIS_KEY_PMS_CATEGORIES, $id);
        return unserialize($category);
    }

    /**
     * @param $customerIds
     * @return bool
     */
    public static function setCustomerIds($customerIds = null)
    {
        if (!$customerIds || !is_array($customerIds)) {
            return false;
        }
        $redis = Tools::getRedis();
        return $redis->set(self::REDIS_KEY_CUSTOMERIDS, serialize($customerIds));// 不过期
    }

    /**
     * @return bool|array
     */
    public static function getCustomerIds()
    {
        $redis = Tools::getRedis();
        $existed = $redis->exists(self::REDIS_KEY_CUSTOMERIDS);
        if (!$existed) {
            return false;
        } else {
            $list = $redis->get(self::REDIS_KEY_CUSTOMERIDS);
            return unserialize($list);
        }

    }


    /**
     * 根据city和商品ID获取商品信息
     * @param $city
     * @return string
     */
    protected static function getRedisKey_Products($city)
    {
        if (!$city) {
            Exception::serviceNotAvailable();
        }
        return self::REDIS_KEY_PRODUCTS . "_" . $city;
    }

    /**
     * 根据city和商品ID获取商品信息
     * getProducts([1,2,3,4])
     * @param $city
     * @param $productIds
     * @param bool $filter
     * @return array|bool|null|string
     */
    public static function getProducts($city = 0, $productIds, $filter = true)
    {
        $redisKey = self::getRedisKey_Products($city);
        if (!is_array($productIds)) {
            $productIds = [$productIds];
        }
        $redis = Tools::getRedis();
        $products = $redis->hMGet($redisKey, $productIds);
        if ($products) {
            $products = array_filter($products);
        }
        $matched = array();
        if (is_array($products)) {
            $matched = array_keys($products);
        }
        $diff = array_diff($productIds, $matched);
        if (count($diff) > 0) {
            $model = new Products($city);
            $dbProducts = $model->find()->where(['entity_id' => $diff])->all();
            $diffProduct = [];
            /** @var Products $dbProduct */
            foreach ($dbProducts as $dbProduct) {
                // 获取属性
                $product = $dbProduct->getAttributes();
                // 处理返点小数位数问题
                $product['rebates'] = floatval($product['rebates']);
                $product['rebates_lelai'] = floatval($product['rebates_lelai']);
                // tags
                $product['tags'] = $dbProduct->getTags($city, $product['entity_id']);
                // 插入
                $diffProduct[$product['entity_id']] = serialize($product);
                $products[$product['entity_id']] = serialize($product);
            }
            if (count($diffProduct) > 0) {
                $redis->hMSet($redisKey, $diffProduct);
            }
        }
        //过滤审核上架状态
        $productsResult = [];

        //保证返回顺序
        foreach ($productIds as $productId) {
            if (isset($products[$productId])) {
                $productData = unserialize($products[$productId]);
                //过滤审核上架状态
                if(isset($productData['state']) && $productData['state'] == 2){  //一定展示审核通过商品
                    if($filter) { //过滤上下架状态
                        if(isset($productData['status']) && $productData['status'] == 1 &&
                            Tools::dataInRange($productData['shelf_from_date'],$productData['shelf_to_date'])){
                            $productsResult[$productId] = $productData;
                        }
                    }else{ //不过滤
                        $productsResult[$productId] = $productData;
                    }
                }
            }

        }
        return $productsResult;
    }

    /**
     * 根据city和conditions来获取商品信息
     * getProductsByCondition('440300', ['entity_id'>1])
     * @param $city
     * @param $dbProducts
     * @return array|bool|null|string
     */
    public static function processDbProducts($city = 0, $dbProducts)
    {
        $redisKey = self::getRedisKey_Products($city);
        $redis = Tools::getRedis();

        $products = [];
        /** @var Products $dbProduct */
        foreach ($dbProducts as $dbProduct) {
            // 获取属性
            $product = $dbProduct->getAttributes();
            // 处理返点小数位数问题
            $product['rebates'] = floatval($product['rebates']);
            // tags
            $product['tags'] = $dbProduct->getTags($city, $product['entity_id']);
            // 插入
            $products[$product['entity_id']] = serialize($product);
        }
        if (count($products) > 0) {
            $redis->hMSet($redisKey, $products);
        }

        //过滤审核上架状态
        $productsResult = [];
        //Tools::wLog($products);
        //保证返回顺序
        foreach ($products as $productId => $product) {
            $productData = unserialize($products[$productId]);
            //过滤审核上架状态
            if (isset($productData['state']) && $productData['state'] == 2 &&
                isset($productData['status']) && $productData['status'] == 1
            ) {
                $productsResult[$productId] = $productData;
            }
        }
        return $productsResult;
    }

    /**
     * 获取已更新商品的数组
     * redis里结构为
     * redisKey = products_updated
     * key      = 440300:2
     * value    = 1463555087
     *
     * return [
     * '440300'=>[
     * 145,146,147
     * ],
     * ]
     */
    public static function getUpdatedProductIds()
    {
        $redis = Tools::getRedis();
        $redisKey = self::REDIS_KEY_PRODUCTS . '_updated';
        $redisList = $redis->hGetAll($redisKey);

        $data = [];
        foreach ($redisList as $key => $updated_at) {
            $tmp = explode(':', $key);
            if (is_array($tmp) && count($tmp) == 2) {
                $city = $tmp[0];
                $product_id = $tmp[1];
                if (!isset($data[$city])) {
                    $data[$city] = [];
                }
                array_push($data[$city], $product_id);
            }
        }
        return $data;
    }

    public static function deleteUpdatedProductIds($product_list_by_city = null)
    {
        $redis = Tools::getRedis();
        $redisKey = self::REDIS_KEY_PRODUCTS . '_updated';

        if (!$product_list_by_city) {
            // 如果不传,则全删
            $redis->del($redisKey);
        } else {
            // 如果传了getUpdatedProductIds返回的数组,就精确删除上次的数据
            foreach ($product_list_by_city as $city => $one_city) {
                foreach ($one_city as $productId) {
                    $key = $city . ':' . $productId;
                    $redis->hDel($redisKey, $key);
                }
            }
        }
    }

    /**
     * 获取redis里的event队列
     */
    public static function getEventQueue()
    {
        $redis = Tools::getRedis();
        $redisKey = self::REDIS_KEY_EVENTS;
        $events = $redis->hGetAll($redisKey);

        $data = [];
        if ($events && is_array($events) && count($events)) {
            foreach ($events as $key => $value) {
                $params = unserialize($value);
                if (!is_array($params)
                    || !isset($params['name'])
                    || !isset($params['data'])
                ) {
                    // 数据有误,删?
                    // $redis->del($redisKey, $key);
                } else {
                    // 数据正确
                    array_push($data, $params);
                }
            }
        }
        return $data;
    }

    public static function deleteEventQueue($eventQueue = null)
    {
        $redis = Tools::getRedis();
        $redisKey = self::REDIS_KEY_EVENTS;

        if (!$eventQueue) {
            // 如果不传,则全删
            $redis->del($redisKey);
        } else {
            // 如果传了getEventQueue返回的数组,就精确删除上次的数据
            foreach ($eventQueue as $value) {
                $key = md5(serialize($value));
                $redis->hDel($redisKey, $key);
            }
        }
    }

}