<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "region_area".
 *
 * @property integer $entity_id
 * @property integer $area_admin_id
 * @property string $registered_at
 * @property integer $city
 * @property integer $district
 * @property string $area_name
 * @property string $area_address
 * @property string $polygon
 * @property string $min_lng
 * @property string $max_lng
 * @property string $min_lat
 * @property string $max_lat
 */
class RegionArea extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region_area';
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
            [['area_admin_id', 'registered_at', 'polygon', 'min_lng', 'max_lng', 'min_lat', 'max_lat'], 'required'],
            [['area_admin_id', 'city', 'district'], 'integer'],
            [['registered_at'], 'safe'],
            [['polygon'], 'string'],
            [['area_name'], 'string', 'max' => 120],
            [['area_address'], 'string', 'max' => 255],
            [['min_lng', 'max_lng', 'min_lat', 'max_lat'], 'string', 'max' => 32]
        ];
    }

    /**
     * Function: regionNames
     * Author: Jason Y. Wang
     *
     * @param array $areaIds
     * @return string
     */
    public static function regionNames($areaIds){
        $regions = static::find()->where(['in','entity_id',$areaIds])->all();
        $regionsName = '';
        /** @var RegionArea $region */
        foreach($regions as $region){
            $regionsName = $region->area_name.' '.$regionsName;
        }
        return $regionsName;
    }

    /**
     * Function: regionNames
     * Author: Jason Y. Wang
     * 返回城市所有区域$area_id => $area_name
     * @param $city
     * @return string
     */
    public static function regionNamesArray($city){
        $regions = static::find()->where(['city' => $city])->all();
        $regionsName = [];
        /** @var RegionArea $region */
        foreach($regions as $region){
            $regionsName[$region->entity_id] = $region->area_name;
        }
        return $regionsName;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'area_admin_id' => '此区块的管理员id',
            'registered_at' => 'Registered At',
            'city' => '城市ID',
            'district' => 'District',
            'area_name' => 'Area Name',
            'area_address' => 'Area Address',
            'polygon' => '区块的多边形序列',
            'min_lng' => 'Min Lng',
            'max_lng' => 'Max Lng',
            'min_lat' => 'Min Lat',
            'max_lat' => 'Max Lat',
        ];
    }
}
