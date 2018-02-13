<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/26
 * Time: 10:59
 */

namespace service\resources\merchant\v1;

use service\message\customer\CartItemsRequest;
use service\message\customer\CartItemsResponse2;
use service\models\ShoppingCart;
use service\resources\MerchantResourceAbstract;

/**
 * Class cartItems3
 * 增加秒杀商品支持
 * @package service\resources\merchant\v1
 */
class cartItems3 extends MerchantResourceAbstract
{
    public function run($data){
        /** @var CartItemsRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $cart = new ShoppingCart($customer);
        $cartItems = $cart->formatShoppingCart3();
        return $cartItems;
    }

    public static function request(){
        return new CartItemsRequest();
    }

    public static function response(){
        return new CartItemsResponse2();
    }
}