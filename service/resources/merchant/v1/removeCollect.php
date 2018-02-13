<?php
namespace service\resources\merchant\v1;

use service\components\Tools;
use service\message\common\Product;
use service\message\merchant\searchProductResponse;
use service\message\merchant\wishlistRequest;
use service\models\Wishlist;
use service\resources\MerchantResourceAbstract;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-27
 * Time: 下午6:30
 */
class removeCollect extends MerchantResourceAbstract
{

    public function run($data)
    {
        /** @var wishlistRequest $request */
        $request = new wishlistRequest();
		$request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        Wishlist::removeCollect($request->getProducts(), $request->getCustomerId(), $customer->getCity());

		$response = self::response();
		return $response;
    }

    public static function request()
    {
        return new wishlistRequest();
    }

    public static function response()
    {
        return true;
    }
}