<?php

namespace service\resources\merchant\v1;

use service\components\search\Search;
use service\components\Tools;
use service\message\merchant\searchProductByBarcodeResponse;
use service\message\merchant\searchProductRequest;
use framework\message\Message;
use service\resources\MerchantResourceAbstract;


class searchProductByBarcode extends MerchantResourceAbstract
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     * 扫码搜索使用
     * @param Message $data
     * @return null|searchProductByBarcodeResponse
     */
    public function run($data){
        /** @var searchProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        //Tools::log($request,'wangyang.log');
        $customer = $this->_initCustomer($request);
        $search = new Search($customer,$request);
        $products = $search->barcodeSearch();
        return $products;

    }

    public static function request()
    {
        return new searchProductRequest();
    }

    public static function response()
    {
        return new searchProductByBarcodeResponse();
    }
}