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
use service\message\customer\UpdateCartItemsRequest;
use service\message\merchant\CartItemsResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class updateItems2
 * @package service\resources\merchant
 */
class updateItems2 extends MerchantResourceAbstract
{

    const CART_LOCATION = 1;
    const PIECE_TOGETHER_LOCATION = 2;

    public function run($data)
    {
        /** @var UpdateCartItemsRequest $request */
        $request = new UpdateCartItemsRequest();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);

        $location = $request->getLocation();

        $response = self::response();
        $cart = new ShoppingCart($customer);
//        Tools::log($request->toArray(),'updateItems2.log');
        $wholesalerCartItems = $cart->updateItems($request->getProducts(), $location);
        Tools::log($wholesalerCartItems, 'updateProducts2.log');
        $response->setFrom(['wholesaler_cart' => $wholesalerCartItems]);
        return $response;
    }

    public static function request()
    {
        return new UpdateCartItemsRequest();
    }

    public static function response()
    {
        return new CartItemsResponse();
    }

}