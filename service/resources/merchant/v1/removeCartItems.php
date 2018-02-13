<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/27
 * Time: 12:24
 */

namespace service\resources\merchant\v1;

use service\components\shoppingcart\ShoppingCart;
use service\components\Tools;
use service\message\customer\RemoveCartItemsRequest;
use service\message\merchant\CartItemsResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class updateItems
 * @package service\resources\customers
 */
class removeCartItems extends MerchantResourceAbstract
{
    public function run($data){
        /** @var RemoveCartItemsRequest $request */
        $request = new RemoveCartItemsRequest();
		$request->parseFromString($data);
        $customer = $this->_initCustomer($request);

        $cart = new ShoppingCart($customer);
        $customerShoppingCart = $cart->removeItems($request->getProducts());
        Tools::log($customerShoppingCart,'removeCartItems.log');
        $response = self::response();
        $response->setFrom(['wholesaler_cart' => $customerShoppingCart]);
        return $response;
    }

    public static function request(){
        return new RemoveCartItemsRequest();
    }

    public static function response(){
        return new CartItemsResponse();
    }
}