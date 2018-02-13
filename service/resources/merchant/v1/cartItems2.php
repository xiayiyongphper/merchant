<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/26
 * Time: 10:59
 */

namespace service\resources\merchant\v1;

use service\components\Tools;
use service\message\customer\CartItemsRequest;
use service\message\customer\CartItemsResponse;
use service\message\customer\CartItemsResponse2;
use service\models\ShoppingCart;
use service\resources\MerchantResourceAbstract;

class cartItems2 extends MerchantResourceAbstract
{
    public function run($data){
        //$timeStart = microtime(true);
        /** @var CartItemsRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $cart = new ShoppingCart($customer);
        $cartItems = $cart->formatShoppingCart2();
        //$timeEnd = microtime(true);
        //Tools::log($timeEnd-$timeStart,'wangyang.log');
        return $cartItems;
    }

    public static function request(){
        return new CartItemsRequest();
    }

    public static function response(){
        return new CartItemsResponse2();
    }
}