<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class CustomThematicActivity
 * @package common\models
 * @property integer $entity_id
 * @property string $title
 * @property string $rule
 * @property string $type
 * @property string $promotion_ids
 * @property string $banner
 * @property integer $status
 *
 */
class CustomThematicActivity extends ActiveRecord
{
    const CUSTOM_THEMATIC_TYPE_ONE = 1;//不使用tab分组
    const CUSTOM_THEMATIC_TYPE_TWO = 2;//按供应商分组
    const CUSTOM_THEMATIC_TYPE_THREE = 3;//按供应商分组
    const CUSTOM_THEMATIC_TYPE_FOUR = 4; //自定义分组

    public static function tableName()
    {
        return 'custom_thematic_activity';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}