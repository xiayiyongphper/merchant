<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 *
 * @property string $store_id
 * @property string $delivery_lowest_money
 * @property string $note
 */
class LeMerchantDelivery extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_delivery';
    }


    /**
     * @return null|object
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
