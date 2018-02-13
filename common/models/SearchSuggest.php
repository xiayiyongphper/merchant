<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class SearchSuggest
 * @package common\models
 * @property integer $entity_id
 * @property string $word
 *
 */
class SearchSuggest extends ActiveRecord
{

    public static function tableName()
    {
        return 'search_suggest';
    }

    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }
}