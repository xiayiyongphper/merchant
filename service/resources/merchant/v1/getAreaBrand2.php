<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\getAreaBrandRequest;
use service\message\merchant\getAreaBrandResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Class getAreaBrand2
 * @package service\resources\merchant\v1
 * 3.0版本生效
 * 返回品牌的同时返回‘保障信息’
 */
class getAreaBrand2 extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getAreaBrandRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        Tools::log($request->toArray(), 'getAreaBrand2.log');
        $customer = $this->_initCustomer($request);

        $elasticSearch = new ElasticSearchExt($customer);
        $brands = $elasticSearch->getBrand($request->getWholesalerId(), $request->getCategoryId());

        $result = [
            'brand_list' => $brands,
            'product_sales_type' => [
                [
                    'key' => '1',
                    'value' => '乐来自营'
                ],
            ]
        ];

        $response = $this->response();
        $response->setFrom(Tools::pb_array_filter($result));

        return $response;
    }

    public static function request()
    {
        return new getAreaBrandRequest();
    }

    public static function response()
    {
        return new getAreaBrandResponse();
    }
}