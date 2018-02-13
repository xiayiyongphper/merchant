<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class LeMerchantStoreCategory
 * @package common\models
 *
 * @property integer $entity_id
 * @property string $name
 */

class LeMerchantStoreCategory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_store_category';
    }

    /**
     * @return null|object
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
