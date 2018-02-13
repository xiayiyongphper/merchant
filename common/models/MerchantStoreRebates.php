<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_merchant_store_rebates".
 *
 * @property integer $entity_id
 * @property integer $store_id
 * @property float $rebates
 * @property float $rebates_lelai
 */
class MerchantStoreRebates extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_store_rebates';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

}