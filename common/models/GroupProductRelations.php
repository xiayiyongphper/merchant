<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/8/21
 * Time: 15:11
 */

namespace common\models;


use yii\db\ActiveRecord;

/**
 *
 *
 * Class GroupProductRelations
 * @package common\models
 */
class GroupProductRelations extends ActiveRecord
{
    public static function tableName()
    {
        return 'group_product_relations';
    }

    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}