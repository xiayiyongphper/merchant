<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/22
 * Time: 16:51
 */

namespace service\resources\merchant\v1;


use common\models\extend\LeMerchantExtend;
use Exception;
use service\components\Tools;
use service\message\common\Merchant;
use service\message\merchant\MerchantAuthenticationRequest;
use service\resources\MerchantException;
use service\resources\MerchantResourceAbstract;

class merchantAuthentication extends MerchantResourceAbstract
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param $data
     * @return \service\message\customer\CustomerResponse
     * @throws Exception
     */
    public function run($data){
        $request = new MerchantAuthenticationRequest();
		$request->parseFromString($data);
        //Tools::log('merchantAuthentication', 'server.log');
        //Tools::log($request, 'server.log');
        $response = self::response();

        // 读缓存
        $merchantArray = Tools::getRedis()->hGet(LeMerchantExtend::MERCHANT_INFO_COLLECTION,$request->getWholesalerId());
        $merchantArray = unserialize($merchantArray);
        if($merchantArray && $merchantArray['auth_token'] == $request->getAuthToken()) {
            $merchantInfo = $this->getWholesalerAccountInfo($merchantArray);
        }else{
            // 缓存内的auth_token失效,则刷一下数据库的数据看看是不是真的失效
            // 读数据库
            $merchant = $this->getMerchantModel($request->getWholesalerId(),$request->getAuthToken());
            if(!$merchant){
                MerchantException::merchantNotFound();
            }
            Tools::getRedis()->hSet(
                LeMerchantExtend::MERCHANT_INFO_COLLECTION,
                $request->getWholesalerId(),
                serialize($merchant->toArray())
            );
            $merchantInfo = $this->getWholesalerAccountInfo($merchant);
        }
        $response->setFrom($merchantInfo);
        //Tools::log($merchantInfo, 'server.log');
        //Tools::log($response, 'server.log');
        return $response;
    }

    public static function request(){
        return new MerchantAuthenticationRequest();
    }

    public static function response(){
        return new Merchant();
    }

}