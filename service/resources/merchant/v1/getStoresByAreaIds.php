<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */
namespace service\resources\merchant\v1;

use service\components\Tools;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByAreaIdsResponse;
use service\resources\MerchantResourceAbstract;


class getStoresByAreaIds extends MerchantResourceAbstract
{
	public function run($data)
	{
		/** @var getStoresByAreaIdsRequest $request */
		$request = $this->request();
		$request->parseFromString($data);

		$areaIds = $request->getAreaIds();
		$areaId = array_pop($areaIds);
		$response = $this->response();
		$wholesalerIds = self::getWholesalerIdsByAreaId($areaId);
		$data = $this->getStoreDetailPro($wholesalerIds,$areaId);
        $data = [
            'wholesaler_list' => $data,
        ];
		$response->setFrom(Tools::pb_array_filter($data));
		return $response;
	}

	public static function request()
	{
		return new getStoresByAreaIdsRequest();
	}

	public static function response()
	{
		return new getStoresByAreaIdsResponse();
	}
}