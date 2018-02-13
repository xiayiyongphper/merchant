<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_customers_relationship".
 *
 * @property integer $entity_id
 * @property integer $merchant_id
 * @property string $storekeeper
 * @property string $phone
 * @property string $store_name
 * @property string $store_alias
 * @property string $consignee
 * @property string $consignee_phone
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property integer $category
 * @property integer $client_type
 * @property integer $bind_id
 * @property string $remark
 * @property string $created_at
 * @property string $updated_at
 * @property integer $is_del
 */
class LeCustomersRelationship extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_relationship';
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
            [['merchant_id', 'storekeeper', 'phone', 'store_name', 'province', 'city', 'district', 'address'], 'required'],
            [['merchant_id', 'province', 'city', 'district', 'category', 'client_type', 'bind_id', 'is_del'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['storekeeper'], 'string', 'max' => 32],
            [['phone', 'consignee_phone'], 'string', 'max' => 24],
            [['store_name', 'store_alias', 'address', 'remark'], 'string', 'max' => 255],
            [['consignee'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => '线下客户ID',
            'merchant_id' => '所属商家ID',
            'storekeeper' => '线下客户(店主)姓名',
            'phone' => '电话(用户内唯一，可手机/座机)',
            'store_name' => '超市(商家)的名称',
            'store_alias' => '超市(商家)别名',
            'consignee' => '收货人',
            'consignee_phone' => '收货人手机',
            'province' => '所在省code',
            'city' => '所在的城市code',
            'district' => '所在县区code',
            'address' => '详细地址',
            'category' => '客户分类: 0-未分类,1-供货商(上游),2-客户(下游),3-供货商+客户',
            'client_type' => '客户平台类型:0-未分类,1-超市,2-供货商',
            'bind_id' => '关联的平台ID',
            'remark' => '备注',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'is_del' => '状态:0-正常,1-已删除,',
        ];
    }
}
