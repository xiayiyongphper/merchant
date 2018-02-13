<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_store_homepage_conf".
 *
 * @property integer $entity_id
 * @property integer $store_id
 * @property string $name
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property string $start_time
 * @property string $end_time
 * @property string $json
 */
class LeStoreHomepageConf extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_store_homepage_conf';
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
            [['store_id', 'status'], 'integer'],
            [['status'], 'required'],
            [['create_time', 'update_time', 'start_time', 'end_time'], 'safe'],
            [['json'], 'string'],
            [['name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'ID',
            'store_id' => '配置所属店铺，0表示平台级配置',
            'name' => '配置名称',
            'status' => '是否上线，1下线，2上线。默认1下线，同一家店铺只能有一个上线的。',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'json' => '配置的json串',
        ];
    }
}
