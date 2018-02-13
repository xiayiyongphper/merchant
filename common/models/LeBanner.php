<?php

namespace common\models;

use Yii;

/**
 *
 * @property string $image_big
 * @property string $image
 *
 * Class LeBanner
 * @package common\models
 */

class LeBanner extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_banner';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['position', 'type_code', 'image', 'status'], 'required'],
            [['status', 'sort'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAreabanner()
    {
        return $this->hasOne(LeAreaBanner::className(), ['banner_id' => 'entity_id']);
    }
}
