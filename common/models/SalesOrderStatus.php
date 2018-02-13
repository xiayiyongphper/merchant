<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 *
 * @property string $label
 * @property string $status
 */
class SalesOrderStatus extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_order_status';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('coreDb');
    }
}
