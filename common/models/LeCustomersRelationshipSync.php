<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_customers_relationship_sync".
 *
 * @property integer $entity_id
 * @property integer $relation_id
 * @property integer $merchant_id
 * @property integer $action_type
 * @property string $data_updated_at
 * @property string $snapshot_data
 * @property string $created_at
 * @property string $updated_at
 */
class LeCustomersRelationshipSync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_relationship_sync';
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
            [['relation_id', 'merchant_id'], 'required'],
            [['relation_id', 'merchant_id', 'action_type'], 'integer'],
            [['data_updated_at', 'created_at'], 'safe'],
            [['snapshot_data'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => '记录ID',
            'relation_id' => '商家客户表ID',
            'merchant_id' => '所属商家ID',
            'action_type' => '操作:0-未操作，1-忽略，2-引用',
            'data_updated_at' => '源数据更新时间',
            'snapshot_data' => '源数据快照(json)',
            'created_at' => '创建时间',
        ];
    }
}
