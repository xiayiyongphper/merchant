<?php

namespace common\models;

use framework\components\ToolsAbstract;
use Yii;
use framework\db\ActiveRecord;


/**
 * Class ProductType
 * @package common\models
 * @property  integer $entity_id
 * @property  string $name 类型名称
 * @property  integer $status 状态：1：启用，2：禁用
 * @property  string $created_at
 * @property  string $updated_at
 */
class ProductType extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_product_type';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * 根据十进制数解析出二进制位获取信息
     * @param integer $decProductType
     * @return ProductType[]|null
     */
    public static function getTypesByDecProductType($decProductType)
    {
        if ($decProductType <= 0) {
            return [];
        }

        $ids = [];
        if ($dec2bin = decbin((int)$decProductType)) {
            for ($i = 1; $i <= strlen($dec2bin); $i++) {
                if ($dec2bin[$i - 1] == 1) {
                    $ids[] = $i;
                }
            }
        }

        if ($ids) {
            return self::findAll(['entity_id' => $ids]);
        }
        return [];
    }
}
