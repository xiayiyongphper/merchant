<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class LeMerchantStoreCategoryCommission
 * @package common\models
 *
 * @property integer $entity_id
 * @property integer $store_id
 * @property string $category_name
 * @property integer $category_id
 * @property integer $level
 * @property float $value
 * @property float $special_value
 * @property string $special_from_date
 * @property string $special_to_date
 * @property string $created_at
 * @property string $updated_at
 */
class LeMerchantStoreCategoryCommission extends ActiveRecord
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_store_category_commission';
    }

    /**
     * @return null|object
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
