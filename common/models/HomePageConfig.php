<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;
use framework\components\Date;
use framework\components\ToolsAbstract;

/**
 *
 * @property integer $entity_id
 * @property integer $type
 * @property integer $refer_id
 * @property string $content
 * @property string $start_time
 * @property string $version
 * @property string $name
 * @property string $create_time
 *
 */
class HomePageConfig extends ActiveRecord
{

    const CONFIG_TYPE_HOME = 1;
    const CONFIG_TYPE_STORE_HOME = 2;
    const CONFIG_TYPE_TOPIC_HOME = 3;
    const CONFIG_TYPE_CLASS_PAGE = 4;

    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return 'homepage_config';
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
            [['type','version','start_time','create_time','content','refer_id'], 'required'],
        ];
    }

    public function parseJson($cityId,$areaId)
    {
        $featured = self::find()
            ->where(['refer_id' => $cityId])
            ->andWhere(['type' => self::CONFIG_TYPE_HOME])
            ->andWhere(['<=', 'start_time', ToolsAbstract::getDate()->date()])
            ->andWhere(['like', 'version', '2.0'])
            ->orderBy('start_time DESC');
        $featured = $featured->asArray()->one();
        $json = $featured['content'];
        $json = json_decode($json, true);

        //json新格式转回旧格式
        if(isset($json['quick_entry_blocks']) && isset($json['quick_entry_blocks'][0])){
            $json['quick_entry_blocks'] = $json['quick_entry_blocks'][0];
        }
        if(isset($json['brand_blocks']) && isset($json['brand_blocks'][0])){
            $json['brand_block'] = $json['brand_blocks'][0];
            if(isset($json['brand_blocks'][0]['brands'])){
                $json['brand_block']['brand_id'] = [];
                foreach ($json['brand_blocks'][0]['brands'] as $brand){
                    $json['brand_block']['brand_id'] []= $brand['brand_id'];
                }
            }

            unset($json['brand_blocks']);
        }

        if(isset($json['topic_blocks'])){
            foreach ($json['topic_blocks'] as $k=>$topic_block){
                if(!empty($topic_block['area_ids']) && !in_array($areaId,$topic_block['area_ids'])){
                    unset($json['topic_blocks'][$k]);
                }
                if($topic_block['topic_type'] > 4){
                    unset($json['topic_blocks'][$k]);
                }
            }
        }

        if(isset($json['store_blocks']) && !empty($json['store_blocks'][0])){
            $json['store'] = $json['store_blocks'][0];
        }

        if(isset($json['product_blocks'])){
            foreach ($json['product_blocks'] as $k => $product_block){
                $json['product_blocks'][$k]['products'] = !empty($product_block['products']) ? explode(',',$product_block['products']) : [];
            }
        }

        return $json;
    }
}
