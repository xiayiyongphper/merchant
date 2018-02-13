<?php

namespace common\models;

use framework\components\ToolsAbstract;
use Yii;
use framework\db\ActiveRecord;


/**
 * Class BlackList
 * @package common\models
 * @property  integer $entity_id
 * @property  integer $customer_id
 * @property  integer $city
 * @property  string $created_at
 */
class BlackList extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'black_list';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * 获取一个城市的黑名单
     * @param integer $city
     * @return array
     */
    public static function getBlackListByCity($cities)
    {
        $result = self::find()->where([
            'city' => $cities,
        ])->asArray()->all();

        return $result;
    }
}
