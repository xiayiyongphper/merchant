<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/6/16
 * Time: 11:22
 */

namespace service\resources\merchant\v1;

use common\models\CumulativeReturnActivity;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\message\merchant\SecKillActProductsRequest;
use service\message\merchant\SecKillActProductsResponse;
use service\resources\MerchantResourceAbstract;

/**
 * 活动专区->活动商品列表接口
 *
 * Class GetSecKillActivityProducts
 * @package service\resources\merchant\v1
 */
class GetSecKillActivityProducts extends MerchantResourceAbstract
{
    const EXPIRE_SECONDS = 7200;
    const IS_CACHE = false;
    const DEFAULT_PAGE_SIZE = 10;

    /**
     * @param string $data
     * @throws \Exception
     * @return SecKillActProductsResponse
     */
    public function run($data)
    {
        /** @var SecKillActProductsRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        // 初始化
        $customer = $this->_initCustomer($request);
        if (!$customer->getCity()) {
            throw new \Exception('用户信息不全！', 101);
        }

        /** @var $activity SecKillActivity */
        if (!$activity = SecKillActivity::getEnabledActivityByIdCity(
            $request->getActId(),
            $customer->getCity(),
            SeckillHelper::IS_CACHE)
        ) {
            throw new \Exception('活动信息不存在或已删除！', 102);
        }

        $respData['activity'] = [
            'id' => $activity['entity_id'],
            'status' => SecKillActivity::getStatusInfo($activity),
            'left_to_end' => SecKillActivity::getLeftTime($activity),
        ];

        // 商品列表和分页
        $this->setProductListValue($respData, $customer, $request, $activity);

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * @param array $respData
     * @param CustomerResponse $customer
     * @param SecKillActProductsRequest $request
     * @param SecKillActivity $activity
     * @return bool
     */
    private function setProductListValue(&$respData, $customer, $request, $activity)
    {
        $pbHeader = \Yii::$app->getRequest()->getPbHeader();
        $page = $request->getPagination() && $request->getPagination()->getPage()
            ? $request->getPagination()->getPage() : 1;
        if (!$result = (new SeckillHelper($customer))->getProducts($activity['entity_id'], $page)) {
            return false;
        }

        list($pages, $formatProducts) = $result;
        $respData['pagination'] = [
            'total_count' => $pages->getTotalCount(),
            'page' => $pages->getCurPage(),
            'last_page' => $pages->getLastPageNumber(),
        ];

        /* 库存状态 */
        $productIds = array_keys($formatProducts);
        $productStocks = ToolsAbstract::getSecKillProductsStocks($request->getActId(), $productIds);

        /* 增加秒杀相关状态和倒计时 */
        foreach ($formatProducts as $productId => $formatProduct) {
            if ($respData['activity']['status'] == SecKillActivity::INT_STATUS_END) {
                $formatProduct['seckill_status'] = SpecialProduct::STATUS_END;
            } elseif ($respData['activity']['status'] == SecKillActivity::INT_STATUS_PREPARED) {
                $formatProduct['seckill_status'] = SpecialProduct::STATUS_PREPARED;
            } else {
                if (!empty($productStocks[$productId])) {
                    $formatProduct['seckill_status'] = SpecialProduct::STATUS_STARTED_HAS_STOCK;
                } else {
                    $formatProduct['seckill_status'] = SpecialProduct::STATUS_STARTED_NO_STOCK;
                }
            }
            $formatProduct['seckill_status_str'] = SpecialProduct::getStatusStr($formatProduct['seckill_status']);
            $respData['products'][] = $formatProduct;
        }
        return true;
    }

    /**
     * @return SecKillActProductsRequest
     */
    public static function request()
    {
        return new SecKillActProductsRequest();
    }

    /**
     * @return SecKillActProductsResponse
     */
    public static function response()
    {
        return new SecKillActProductsResponse();
    }
}