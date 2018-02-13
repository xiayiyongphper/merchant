<?php

namespace common\models\extend;

use common\models\DeviceToken;
use common\models\LeMerchant;
use service\components\Events;
use service\components\Proxy;

use Yii;


class LeMerchantExtend extends LeMerchant
{

    const MERCHANT_INFO_COLLECTION = "merchant_info_collection";
    /**
     * Function: checkUsername
     * Author: Jason Y. Wang
     * copy from swoole_customer
     *
     * @param $username
     * @return bool
     */
    public static function checkUsername($username)
    {
        //手机号登陆或用户名登陆都可以
        return self::find()->where(['name' => $username])
            ->orWhere(['phone' => $username])->exists();
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @param string $password
     * @return static|null
     */
    public static function findByUsername($username, $password)
    {
        /*
        //手机号登陆或用户名登陆都可以
        return self::find()->where(['and',['username' => $username],['password' => $password]])
            ->orWhere(['and',['phone' => $username],['password' => $password]])->one();
        */
        // 如果是11位数字,则用phone字段登陆  ^[\d]{11}$
        if( preg_match('/^[\d]{11}$/', $username) ){
            return self::find()
                ->where(['and',['phone' => $username]])//,['password' => $password]
                ->one();
        }else{
            return self::find()
                ->where(['and',['name' => $username]])//,['password' => $password]
                ->one();
        }
    }

    public static function loadOne($value, $key='entity_id'){
        return self::find()
            ->where([$key => $value])
            ->one();
    }

    public static function notifyOrder($wholesalerId, $orderId, $content, $sound=null){

        // 拿到推送数据
        $deviceModel = new DeviceToken();
        $devices = $deviceModel->findAll(['merchant_id'=>$wholesalerId]);

        foreach ($devices as $device) {
            $scheme = 'lelaiwholesaler://order/info?oid='.$orderId;
            if($sound) {
                $scheme .= '&sound='.$sound;
            }
            Proxy::sendMessage(Events::getCustomerEventName(Events::EVENT_PUSH_NOTIFICATION), array(
                'name'=>Events::EVENT_PUSH_NOTIFICATION,
                'data'=>array(
                    'platform'  => 2,// 1:订货网  2:商家版  我这边固定传2
                    'system'    =>$device->system,
                    'token'     =>$device->token,
                    //'type'      => 2,  // 固定LE_Push_Model_Queue::TYPE_MERCHANT
                    'value_id'  => $wholesalerId,
                    'channel'   => $device->channel,
                    'typequeue' => $device->typequeue,
                    'params'    => serialize(array(
                        'title' => '乐来订货',
                        'content' => $content,
                        'scheme' => $scheme,
                    )),
                )
            ));
        }
    }
    
}
