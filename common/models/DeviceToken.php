<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "device_token".
 *
 * @property integer $entity_id
 * @property integer $merchant_id
 * @property string $token
 * @property integer $system
 * @property integer $channel
 * @property string $checksum
 * @property string $created_at
 * @property string $updated_at
 * @property integer $typequeue
 */
class DeviceToken extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'device_token';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['merchant_id', 'token', 'checksum'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'merchant_id' => 'Merchant ID',
            'token' => 'Token',
            'system' => 'System',
            'channel' => 'Channel',
            'checksum' => 'Checksum',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'typequeue' => 'Typequeue',
        ];
    }

    /**
     * Function: findDeviceTokenByCustomerId
     * Author: Jason Y. Wang
     *
     * @param $customer_id
     * @return null|static|self
     */
    public static function findDeviceTokenByMerchantId($merchant_id){
        return static::findOne(['merchant_id' => $merchant_id]);
    }
}
