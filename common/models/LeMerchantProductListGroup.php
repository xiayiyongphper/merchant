<?php

namespace common\models;
use framework\db\ActiveRecord;


/**
 * Author: Jason Y. Wang
 * Class LeMerchantProductList
 * @package common\models
 * @property string $entity_id
 * @property string $identifier
 * @property string $title
 * @property string $description
 * @property string $banner
 *
 */
class LeMerchantProductListGroup extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_product_list_group';
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
            [['identifier', 'title', 'description', 'banner'], 'required']
        ];
    }

    /**
     * Function: getThematic
     * Author: Jason Y. Wang
     * 获取某个专题的详情
     * @param $identifier
     * @return null|static
     */
    public static function getThematicInfo($identifier){
        return self::findOne(['identifier' => $identifier]);
    }


}
