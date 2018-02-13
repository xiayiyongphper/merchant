<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class CustomThematicActivitySub
 * @package common\models
 * @property integer $entity_id
 * @property integer $thematic_id
 * @property string $short_name
 * @property integer $long_name
 * @property integer $image_name
 * @property integer $schema_url
 *
 */
class CustomThematicActivitySub extends ActiveRecord
{

    public static function tableName()
    {
        return 'custom_thematic_activity_sub';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}