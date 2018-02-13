<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use service\components\Redis;
use service\components\Tools;
use service\message\common\Store;
use service\message\merchant\getStoreDetailRequest;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;


class getStoreDetail extends MerchantResourceAbstract
{

	public function run($data)
	{
		/** @var getStoreDetailRequest $request */
		$request = $this->request();
		$request->parseFromString($data);

		$wholesaler_id = $request->getWholesalerId();
		$customer = null;
		if($request->getCustomerId() && $request->getAuthToken()){
			$customer = $this->_initCustomer($request);
		}

		if(!$wholesaler_id){
			Exception::storeNotExisted();
		}

        if($customer){
            if($this->isRemote()){
                $wholesaler_ids = self::getWholesalerIdsByAreaId($customer->getAreaId());
                if(!in_array($wholesaler_id,$wholesaler_ids)){
                    Exception::wholesalerNotDeliveryArea();
                }
            }
        }

        // redis读
		$wholesalers_info = Redis::getWholesalers([$wholesaler_id]);
        if(isset($wholesalers_info[$wholesaler_id])){
			$wholesaler_info = unserialize($wholesalers_info[$wholesaler_id]);
		}else{
			$wholesaler_info = false;
		}

		$response = $this->response();
		if ($wholesaler_info) {
			if($customer){
				$data = MerchantResourceAbstract::getStoreDetail($wholesaler_info,$customer->getAreaId());
			}else{
				$data = MerchantResourceAbstract::getStoreDetail($wholesaler_info);
			}

			$response->setFrom(Tools::pb_array_filter($data));
		} else {
			Exception::storeNotExisted();
		}

		return $response;
	}

	public static function request()
	{
		return new getStoreDetailRequest();
	}

	public static function response()
	{
		return new Store();
	}
}