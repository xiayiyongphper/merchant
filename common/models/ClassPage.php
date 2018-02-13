<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/9/15
 * Time: 12:04
 */

namespace common\models;

use framework\db\ActiveRecord;
use common\models\HomePageConfig;
use service\components\Tools;

/**
 * Class ClassPage
 * @package common\models
 */
class ClassPage extends ActiveRecord
{
    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_class_page';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    //获取有效分类页推荐列表
    public static function getClassPageRecommendList($city){
        $data = self::find()->alias('class')
            ->leftJoin(['config' => HomePageConfig::tableName()],'config.refer_id = class.entity_id')
            ->where(['class.city' => $city])
            ->andWhere(['class.status' => self::STATUS_ENABLE])
            ->andWhere(['config.type' => HomePageConfig::CONFIG_TYPE_CLASS_PAGE])
            ->select('config.content,class.*')
            ->asArray()
            ->all();
        //Tools::log($data,'class.log');

        if(empty($data)){
            return [];
        }

        foreach ($data as $k=>$row){
            $config = json_decode($row['content'],true);
            if(empty($config['product_blocks']) && empty($config['topic_blocks'])){
                unset($data[$k]);
            }else{
                unset($data[$k]['content']);
            }
        }
        //Tools::log($data,'class.log');

        return $data;
    }
}