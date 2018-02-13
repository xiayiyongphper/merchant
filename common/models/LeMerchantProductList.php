<?php

namespace common\models;
use framework\db\ActiveRecord;


/**
 * Author: Jason Y. Wang
 * Class LeMerchantProductList
 * @package common\models
 * @property string $wholesaler_id
 * @property string $barcode
 * @property string $product_id
 * @property string $identifier
 *
 */
class LeMerchantProductList extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_product_list';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['identifier', 'wholesaler_id', 'barcode', 'status','created_at'], 'required'],
            [['wholesaler_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Function: getThematic
     * Author: Jason Y. Wang
     * 获取某个店铺在特定专题中的商品列表
     * @param array $wholesaler_ids
     * @param $identifier
     * @return array|ActiveRecord[]
     */
    public static function getThematic($wholesaler_ids,$identifier){
        return self::find()->where(['identifier' => $identifier,'status' =>1])
            ->andWhere(['in','wholesaler_id',$wholesaler_ids])->orderBy('entity_id asc')->all();
    }


}
