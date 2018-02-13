<?php

namespace common\models;

use framework\db\ActiveRecord;

/**
 * This is the model class for table "best_selling_product".
 *
 */
class BestSellingProduct extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'best_selling_product';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}
