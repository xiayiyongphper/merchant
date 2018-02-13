<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/27
 * Time: 12:24
 */

/**
 * Class Log
 * @package common\models
 * @property string $trace_id
 * @property string $source
 * @property string $content
 * @property string $type
 */
class Log extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('logDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['trace_id', 'source', 'content', 'type'], 'required'],
        ];
    }
}