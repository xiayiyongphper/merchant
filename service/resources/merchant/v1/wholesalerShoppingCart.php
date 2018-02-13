<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/27
 * Time: 12:24
 */

namespace service\resources\merchant\v1;

use service\components\shoppingcart\ShoppingCart;
use service\message\merchant\CartItemsResponse;
use service\message\merchant\getStoreDetailRequest;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class updateItems2
 * @package service\resources\merchant
 */
class wholesalerShoppingCart  extends MerchantResourceAbstract
{
    public function run($data){
        /** @var getStoreDetailRequest $request */
        $request = new getStoreDetailRequest();
		$request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $wholesaler_id = $request->getWholesalerId();
        $response = self::response();
        $cart = new ShoppingCart($customer);
        $wholesalerCartItems = $cart->getSingleWholesalerShoppingCart($wholesaler_id);
        $response->setFrom(['wholesaler_cart' => $wholesalerCartItems]);
        return $response;
    }

    public static function request(){
        return new getStoreDetailRequest();
    }

    public static function response(){
        return new CartItemsResponse();
    }

}