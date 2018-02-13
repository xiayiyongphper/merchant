<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */
namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantExtend;
use service\components\Tools;
use service\message\common\Merchant;
use service\message\customer\CustomerResponse;
use service\message\customer\LoginRequest;
use service\resources\Exception;
use service\resources\MerchantException;
use service\resources\MerchantResourceAbstract;

class login extends MerchantResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param $data
     * @return CustomerResponse
     * @throws Exception
     */
    public function run($data)
    {
        /** @var LoginRequest $request */
        $request = new LoginRequest();
		$request->parseFromString($data);
        if(!LeMerchantExtend::checkUsername($request->getUsername())){
            MerchantException::merchantNotFound();
        }
        /* @var LeMerchantExtend $merchant */
        $merchant = LeMerchantExtend::findByUsername($request->getUsername(), $request->getPassword());
        if(!$merchant || !password_verify($request->getPassword(), $merchant->password)){
            MerchantException::merchantPasswordError();
        }

        //不重新生成auth_token,不互踢
        if(!$merchant->getAttribute('auth_token')){
            $merchant->auth_token = Tools::getRandomString(16);
            //$merchant->setAttribute('auth_token', Tools::getRandomString(16));
            $merchant->save();
            //Tools::log($merchant->getErrors(), 'server.log');
        }
        $merchantInfo = $this->getWholesalerAccountInfo($merchant);
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($merchantInfo));
        //Tools::log($response);
        return $response;
    }

    public static function request(){
        return new LoginRequest();
    }

    public static function response(){
        return new Merchant();
    }

}