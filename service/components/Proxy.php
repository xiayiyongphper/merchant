<?php

namespace service\components;

use framework\components\ProxyAbstract;
use framework\components\ToolsAbstract;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\common\Store;
use service\message\core\CouponReceiveListRequest;
use service\message\core\CouponReceiveListResponse;
use service\message\core\getWholesalerRequest;
use service\message\core\getWholesalerResponse;
use service\message\customer\CustomerResponse;
use service\message\merchant\getProductRequest;
use service\message\merchant\getProductResponse;
use service\message\merchant\SaleRuleRequest;
use service\message\merchant\SaleRuleResponse;
use service\message\sales\GetCumulativeReturnDetailRequest;
use service\message\sales\GetCumulativeReturnDetailResponse;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderNumberResponse;
use service\message\sales\GreyListRequest;
use service\message\sales\GreyListRule;
use service\message\sales\GreyListResponse;
use service\resources\merchant\v1\getStoreDetail;

use service\message\core\AllSaleRuleRequest;
use service\message\core\AllSaleRuleResponse;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/25
 * Time: 11:02
 */
class Proxy extends ProxyAbstract
{
    const ROUTE_MERCHANT_GET_STORE_DETAIL = 'merchant.getStoreDetail';
    const ROUTE_SALES_GET_WHOLESALER = 'sales.getWholesaler';
    const ROUTE_SALES_ORDER_COUNT = 'sales.orderCountToday';
    const ROUTE_SALES_COUPON_RECEIVE_LIST = 'sales.couponReceiveList';
    const ROUTE_MERCHANT_GET_PRODUCT = 'merchant.getProduct';
    const ROUTE_MERCHANT_GET_RECENTLY_BUY_STORE = 'sales.getRecentlyBuyWholesalerIds';
    const ROUTE_SALES_RULE = 'sales.saleRule';
    const ROUTE_SALES_CUMULATIVE_RETURN_ACTIVITY = 'sales.GetCumulativeReturnDetail';
    const ROUTE_ALL_SALES_RULE = 'sales.allSaleRule';
    const ROUTE_SALES_GET_BLACK_GREY_LIST = 'sales.greyList';

    /**
     * @param $cities
     * @return array
     * @throws \Exception
     */

    public static function getGreyList($rules){
        $request = new GreyListRequest();
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $grey_list_rule = new GreyListRule();
                $grey_list_rule->setCity($rule['city']);
                $grey_list_rule->setDays($rule['days']);
                $grey_list_rule->setSeckillTimes($rule['seckill_times']);
                $request->appendRules($grey_list_rule);
            }
        }
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_GET_BLACK_GREY_LIST);
        $message = self::sendRequest($header, $request);
        $grey_list = [];
        if ($message->getPackageBody()) {
            /** @var GreyListResponse $response */
            $response = new GreyListResponse();
            $response->parseFromString($message->getPackageBody());
            $grey_list = $response->getGreyList();
        }
        //ToolsAbstract::log($grey_list,'hl.log');

        foreach ($grey_list as $k=>$item){
            $grey_list[$k] = $item->toArray();
        }
        return $grey_list;
    }

    /**
     * @param $customer_id
     * @return array
     * @throws \Exception
     *
     */
    public static function getRecentlyBuyWholesalerIds($customer_id, $wholesalerIds = [])
    {
        $request = new getWholesalerRequest();
        $request->setCustomerId($customer_id);
        if (!empty($wholesalerIds)) {
            foreach ($wholesalerIds as $wholesalerId) {
                $request->appendWholesalerIds($wholesalerId);
            }
        }
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_MERCHANT_GET_RECENTLY_BUY_STORE);
        $message = self::sendRequest($header, $request);
        $recentlyBuyWholesalerIds = [];
        if ($message->getPackageBody()) {
            /** @var getWholesalerResponse $response */
            $response = new getWholesalerResponse();
            $response->parseFromString($message->getPackageBody());
            $recentlyBuyWholesalerIds = $response->getWholesalerIds();
        }

        return $recentlyBuyWholesalerIds;
    }

    /**
     * @param $customer_id
     * @return array
     * @throws \Exception
     *
     */
    public static function getWholesalerIdsByOrder($customer_id)
    {
        $request = new getWholesalerRequest();
        $request->setCustomerId($customer_id);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_GET_WHOLESALER);
        $message = self::sendRequest($header, $request);
        $wholesalerIdsByOrderIds = [];
        if ($message->getPackageBody()) {
            /** @var getWholesalerResponse $response */
            $response = new getWholesalerResponse();
            $response->parseFromString($message->getPackageBody());
            $wholesalerIdsByOrderIds = $response->getWholesalerIds();
        }

        return $wholesalerIdsByOrderIds;
    }

    /**
     * @param CustomerResponse $customer
     * @param $wholesaler_id
     * @return integer
     * @throws \Exception
     */
    public static function getOrderCountToday($customer, $wholesaler_id)
    {
        $request = new OrderCollectionRequest();
        $request->setCustomerId($customer->getCustomerId());
        $request->setAuthToken($customer->getAuthToken());
        $request->setWholesalerId($wholesaler_id);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_ORDER_COUNT);
        $message = self::sendRequest($header, $request);
        $count = 0;
        if ($message->getPackageBody()) {
            /** @var OrderNumberResponse $response */
            $response = new OrderNumberResponse();
            $response->parseFromString($message->getPackageBody());
            $count = $response->getAll();
        }

        return $count;
    }

    /**
     * @param int $location
     * @param int $rule_id
     * @param int $wholesaler_id
     * Author Jason Y. wang
     *
     * @return Proxy|CouponReceiveListResponse
     */
    public static function getCouponReceiveList($location = 0, $rule_id = 0, $wholesaler_id = 0)
    {
        $request = new CouponReceiveListRequest();
        $data = [
            'location' => $location,
            'rule_id' => $rule_id,
            'wholesaler_id' => $wholesaler_id,
        ];

        $request->setFrom(Tools::pb_array_filter($data));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_COUPON_RECEIVE_LIST);
        $message = self::sendRequest($header, $request);
        $response = [];
        if ($message->getPackageBody()) {
            $response = new CouponReceiveListResponse();
            $response->parseFromString($message->getPackageBody());
        }

        return $response;
    }

    /**
     * 获取累计满返活动
     *
     * @param CustomerResponse $customer
     * @param integer $type 类型， 1：我的，2：订单列表，3：累计满返活动详情，4：首页红点
     * @return GetCumulativeReturnDetailResponse|null
     */
    public static function getCumulativeReturnActivity(CustomerResponse $customer, $type = 4)
    {
        $request = new GetCumulativeReturnDetailRequest();
        $request->setCustomerId($customer->getCustomerId());
        $request->setAuthToken($customer->getAuthToken());
        $request->setType($type);   // 类型， 1：我的，2：订单列表，3：累计满返活动详情，4：首页红点
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_CUMULATIVE_RETURN_ACTIVITY);
        $header->setCustomerId($customer->getCustomerId());
        $message = self::sendRequest($header, $request);
        $response = null;
        if ($message->getPackageBody()) {
            $response = new GetCumulativeReturnDetailResponse();
            $response->parseFromString($message->getPackageBody());
        }

        return $response;
    }

    /**
     * @param int $rule_id
     * @param int $wholesaler_id
     * @return bool|SaleRuleResponse
     * @throws \Exception
     */
    public static function getSaleRule($rule_id = 0, $wholesaler_id = 0)
    {
        if (empty($rule_id) && empty($wholesaler_id)) {
            return false;
        }
        if (!is_array($rule_id)) {
            $rule_id = [$rule_id];
        }

        if (!is_array($wholesaler_id)) {
            $wholesaler_id = [$wholesaler_id];
        }

        $request = new SaleRuleRequest();
        $data = [
            'rule_id' => $rule_id,
            'wholesaler_id' => $wholesaler_id,
        ];

        $request->setFrom(Tools::pb_array_filter($data));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_RULE);
        $message = self::sendRequest($header, $request);
        if (!$message->getPackageBody()) {
            return false;
        }
        /** @var SaleRuleResponse $response */
        $response = new SaleRuleResponse();
        $response->parseFromString($message->getPackageBody());

        return $response;
    }

    /**
     * 用于统计综合得分脚本获取供应商全部优惠信息
     * @param int $wholesaler_id
     * @return bool|AllSaleRuleRequest
     * @throws \Exception
     */
    public static function getAllSaleRule($wholesaler_id = 0)
    {
        if (empty($wholesaler_id)) {
            return false;
        }

        if (!is_array($wholesaler_id)) {
            $wholesaler_id = [$wholesaler_id];
        }

        $request = new AllSaleRuleRequest();
        $data = [
            'wholesaler_id' => $wholesaler_id,
        ];

        $request->setFrom(Tools::pb_array_filter($data));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_ALL_SALES_RULE);
        $message = self::sendRequest($header, $request);
        if (!$message->getPackageBody()) {
            return false;
        }
        /** @var SaleRuleResponse $response */
        $response = new AllSaleRuleResponse();
        $response->parseFromString($message->getPackageBody());

        return $response;
    }

    /**
     * @param $wholesalerId
     * @param $productId
     *
     * @return bool|getProductResponse
     * @throws \Exception
     */
    public static function getProducts($wholesalerId, $productId, $traceId)
    {
        if (!$wholesalerId || !is_array($productId) || count($productId) == 0) {
            return false;
        }

        $requestData = [
            'wholesaler_id' => $wholesalerId,
            'product_ids' => $productId,
        ];
        $request = new getProductRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_MERCHANT_GET_PRODUCT);
        $header->setTraceId($traceId);
        $message = self::sendRequest($header, $request);
        /** @var getProductResponse $response */
        $response = new getProductResponse();
        $response->parseFromString($message->getPackageBody());

        return $response;
    }

    public static function reportSyncProcessResult($data)
    {
        //data.notification
        $response = new \service\message\syncProcess\ProcessResponse();
        $response->setFrom(array_filter($data));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('data.notification');
        // 发送
        try {
            $reportResponse = self::sendRequest($header, $response);
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }


    /**
     * @param $wholesalerId
     * @param $traceId
     *
     * @return Store
     * @throws \Exception
     */
    public static function getWholesaler($wholesalerId, $traceId)
    {
        $obj = new getStoreDetail();
        $request = $obj->request();
        $request->setWholesalerId($wholesalerId);
        $response = $obj->run($request->serializeToString());
        return $response;
    }
}
