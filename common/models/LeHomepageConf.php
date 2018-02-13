<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "products_city_%".
 *
 * @property string $entity_id
 * @property integer $city
 * @property string $content
 * @property string $version
 */
class LeHomepageConf extends ActiveRecord
{

    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return 'le_homepage_conf';
    }
 
   
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['city', 'content'], 'required'],
        ];
    }
}
