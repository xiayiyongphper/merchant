<?php

namespace common\models;

use framework\components\ToolsAbstract;
use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;


/**
 * Class Topic
 * @property string $title
 * @package common\models
 */
class Topic extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_topic';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

}
