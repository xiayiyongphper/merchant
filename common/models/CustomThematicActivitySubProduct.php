<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class CustomThematicActivitySub
 * @package common\models
 * @property integer $entity_id
 * @property integer $sub_id
 * @property integer $product_id
 *
 */
class CustomThematicActivitySubProduct extends ActiveRecord
{
    
    public static function tableName()
    {
        return 'custom_thematic_activity_sub_product';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

}