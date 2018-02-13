<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/29
 * Time: 15:15
 */

namespace service\resources\merchant\v1;


use common\models\DeviceToken;
use common\models\LeMerchant;
use service\message\customer\PostDeviceTokenRequest;
use service\resources\MerchantException;
use service\resources\MerchantResourceAbstract;

class postDeviceToken extends MerchantResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param \ProtocolBuffers\Message $data
     * @return bool
     * @throws MerchantException
     */
    public function run($data){
        /** @var PostDeviceTokenRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        /* @var LeMerchant $merchant */
        $merchant = $this->getMerchantModel($request->getWholesalerId(),$request->getAuthToken());
        if($merchant){
            //should find device token by wholesaler_id not merchant_id
            if($deviceToken = DeviceToken::findDeviceTokenByMerchantId($request->getWholesalerId())){
                $deviceToken->merchant_id = $request->getWholesalerId();//merchant_id field name means wholesaler_id
                $deviceToken->token = $request->getToken();
                $deviceToken->system = $request->getSystem();
                $deviceToken->channel = $request->getChannel();
                $deviceToken->checksum = $this->getMd5Sum($request->getToken(),$request->getSystem(),$request->getWholesalerId());
                $deviceToken->typequeue = $request->getTypequeue();
                $deviceToken->updated_at = date('Y-m-d H:i:s');
                $deviceToken->save();
            }else{
                $deviceToken = new DeviceToken();
                $deviceToken->merchant_id = $request->getWholesalerId();
                $deviceToken->token = $request->getToken();
                $deviceToken->system = $request->getSystem();
                $deviceToken->channel = $request->getChannel();
                $deviceToken->checksum = $this->getMd5Sum($request->getToken(),$request->getSystem(),$request->getWholesalerId());
                $deviceToken->typequeue = $request->getTypequeue();
                $deviceToken->created_at = date('Y-m-d H:i:s');
                $deviceToken->updated_at = date('Y-m-d H:i:s');
                $deviceToken->save();
            }

        }else{
            MerchantException::merchantAuthTokenExpired();
        }

        $response = self::response();
        return $response;
    }

    public static function request()
    {
        return new PostDeviceTokenRequest();
    }

    public static function response()
    {
        return true;
    }

    /**
     * Function: getMd5Sum
     * Author: Jason Y. Wang
     *
     * @param $token
     * @param $system
     * @param $customer_id
     * @return string
     */
    private function getMd5Sum($token,$system,$customer_id)
    {
        return md5(sprintf('%s%s%s', $token, $system, $customer_id));
    }

}