<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2017/9/20
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\getProductRequest;
use service\message\merchant\getProductResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getAggregationProducts
 * @package service\resources\merchant\v1
 */
class getAggregationProducts extends MerchantResourceAbstract
{

    /**
     * 获取聚合商品
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var getProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $productIds = $request->getProductIds();
        $elasticSearch = new ElasticSearchExt($customer);
        $products = $elasticSearch->getProductsByIdsOrderByWholesalerWeight($productIds);
        Tools::log($products, 'getAggregationProducts.log');
        $response = $this->response();
        $response->setFrom(Tools::pb_array_filter(['product_list' => $products]));
        return $response;
    }

    public static function request()
    {
        return new getProductRequest();
    }

    public static function response()
    {
        return new getProductResponse();
    }

}