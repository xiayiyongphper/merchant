<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_merchant".
 *
 * @property integer $entity_id
 * @property string $name
 * @property string $password
 * @property string $auth_token
 * @property string $real_name
 * @property string $phone
 * @property string $id_card
 * @property string $id_card_front
 * @property string $id_card_back
 * @property string $email
 * @property integer $region_id
 * @property integer $is_recommend
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 */
class LeMerchant extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant';
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
            [['name', 'password', 'phone', 'region_id'], 'required'],
            [['region_id', 'is_recommend', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 30],
            [['password', 'real_name', 'id_card'], 'string', 'max' => 64],
            [['auth_token', 'phone', 'email'], 'string', 'max' => 32],
            [['id_card_front', 'id_card_back'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'name' => 'Name',
            'password' => 'Password',
            'auth_token' => 'token ',
            'real_name' => 'Real Name',
            'phone' => 'Phone',
            'id_card' => 'Id Card',
            'id_card_front' => '身份证正面照片',
            'id_card_back' => 'Id Card Back',
            'email' => 'Email',
            'region_id' => 'Region ID',
            'is_recommend' => 'Is Recommend',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
