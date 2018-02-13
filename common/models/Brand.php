<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "sales_flat_order_address".
 *
 * @property string $entity_id
 * @property string $name
 * @property integer $sort
 * @property integer $icon
 */
class Brand extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lelai_slim_common.brand';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

}
