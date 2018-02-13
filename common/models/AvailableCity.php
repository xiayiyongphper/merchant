<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class SensitiveWords
 * @package common\models
 * @property string $city_name
 * @property integer $city_code
 * @property string $province_name
 * @property integer $province_code
 *
 */
class AvailableCity extends ActiveRecord
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'available_city';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }
}