<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */
namespace service\resources\merchant\v1;

use service\components\Proxy;
use service\components\Tools;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByAreaIdsResponse;
use service\resources\MerchantResourceAbstract;


class getStoresByAreaIdsV2 extends MerchantResourceAbstract
{
	public function run($data)
	{
		/** @var getStoresByAreaIdsRequest $request */
		$request = $this->request();
		$request->parseFromString($data);
		$customer = $this->_initCustomer($request);
		$areaIds = $request->getAreaIds();
		$areaId = array_pop($areaIds);
		$response = $this->response();

		//$wholesalerIds = self::getWholesalerIdsByAreaId($areaId);
		//$data = $this->getStoreDetailPro($wholesalerIds,$areaId);
		//$data = [
		//	'wholesaler_list' => $data,
		//];
		//$response->setFrom(Tools::pb_array_filter($data));
		//return $response;

		//推荐供应商，优先展示白名单供应商
		//区域内店铺IDs
		$wholesalerIds = $this->getWholesalerIdsByAreaId($areaId);
		$limit = 9999;
		$recommendWholesalerIds = self::getWhiteListWholesalerIds($areaId,$limit);
		$recommendWholesalerCount = count($recommendWholesalerIds);
        //数量小于N时，获取最近购买供应商
        if($recommendWholesalerCount < $limit){
            $recentBuyWholesalerIds = Proxy::getRecentlyBuyWholesalerIds($customer->getCustomerId());
            //将最近购买供应商加入推荐供应商
            foreach ($recentBuyWholesalerIds as $key => $recentBuyWholesalerId){
                if(in_array($recentBuyWholesalerId,$wholesalerIds)){
                    if(!in_array($recentBuyWholesalerId,$recommendWholesalerIds)){
                        array_push($recommendWholesalerIds,$recentBuyWholesalerId);
                        if(count($recommendWholesalerIds) == $limit){
                            break;
                        }
                    }
                }else{
                    //去除不在配送区域的供应商
                    unset($recentBuyWholesalerIds[$key]);
                    continue;
                }

            }
            //还是小于N个则用普通供应商填充
            if(count($recommendWholesalerIds) < $limit){
                //将最近购买供应商加入推荐供应商
                foreach ($wholesalerIds as $wholesalerId){
                    if(!in_array($wholesalerId,$recommendWholesalerIds)){
                        array_push($recommendWholesalerIds,$wholesalerId);
                        if(count($recommendWholesalerIds) == $limit){
                            break;
                        }
                    }
                }
            }
        }
		//$wholesalers = self::getStoreDetailBrief($recommendWholesalerIds,$areaId);
		$wholesalers = $this->getStoreDetailPro($recommendWholesalerIds,$areaId);
		$data = [
			'wholesaler_list' => $wholesalers,
		];
		$response->setFrom(Tools::pb_array_filter($data));
		//Tools::log($response->toArray(), 'zgr.txt');
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