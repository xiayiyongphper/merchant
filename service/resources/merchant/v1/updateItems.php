<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/27
 * Time: 12:24
 */

namespace service\resources\merchant\v1;

use service\message\common\UniversalResponse;
use service\message\customer\UpdateCartItemsRequest;
use service\models\ShoppingCart;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class updateItems
 * @package service\resources\merchant
 */
class updateItems extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var UpdateCartItemsRequest $request */
        $request = new UpdateCartItemsRequest();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);

        $response = self::response();
        $cart = new ShoppingCart($customer);
        $cart->updateCartItems($request->getProducts());
        $response->setCode(0);
        return $response;
    }

    public static function request()
    {
        return new UpdateCartItemsRequest();
    }

    public static function response()
    {
        // 实际上没有包体,头里面的code代表成功与否
        return new UniversalResponse();
    }

}