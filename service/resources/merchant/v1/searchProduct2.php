<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use framework\message\Message;
use service\components\search\DateBaseSearch;
use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\searchProductRequest;
use service\message\merchant\searchProductResponse;
use service\resources\MerchantResourceAbstract;


class searchProduct2 extends MerchantResourceAbstract
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     * 加入sphinx搜索
     * @param Message $data
     * @return null|searchProductResponse
     */
    public function run($data)
    {
        $timeStart = microtime(true);
        /** @var searchProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
//        Tools::log($request->toArray(),'searchProduct2.log');
        try {
            $search = new ElasticSearchExt($customer, $request);
        } catch (\Exception $e) {
            $search = new DateBaseSearch($customer, $request);
        }

        $products = $search->search();
        return $products;

    }

    public static function request()
    {
        return new searchProductRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}