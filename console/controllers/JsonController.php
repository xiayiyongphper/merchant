<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:30
 */

namespace console\controllers;

use Yii;
use yii\console\Controller;
use framework\components\ToolsAbstract;
use common\models\LeHomepageConf;
use common\models\LeStoreHomepageConf;
use common\models\CoreConfigData;
use common\models\HomePageConfig;
use service\resources\MerchantResourceAbstract;

class JsonController extends Controller
{
    const CONFIG_HOME = 1;
    const CONFIG_STORE = 2;
    const CONFIG_STORE_DEFAULT = 3;
    /**
     * run
     */
    public function actionHome()
    {
        $maxId = 0;
        $num = 50;

        while (true){
            $data = LeHomepageConf::find()
                ->where(['>','entity_id',$maxId])
                ->andWhere(['version' => 2])
                ->orderBy('entity_id ASC')
                ->limit($num)->asArray()->all();

            if(!$data) {
                ToolsAbstract::log("=============task finished==============",'format_json.log');
                exit;
            }

            foreach ($data as $row){
                $json = $this->format($row['content'],self::CONFIG_HOME);
                //ToolsAbstract::log($json,'format_json.log');

                //插入新表
                $this->add_new_config(1,$json,$row['start_time'],$row['city']);
            }

            $maxId = $data[count($data) -1]['entity_id'];
            ToolsAbstract::log("max_id=====".$maxId,'format_json.log');
        }
    }

    public function actionStore(){
        $maxId = 0;
        $num = 50;

        while (true){
            $data = LeStoreHomepageConf::find()
                ->where(['>','entity_id',$maxId])
                ->andWhere(['status' => [0,1]])
                ->orderBy('entity_id ASC')
                ->limit($num)->asArray()->all();

            if(!$data) {
                ToolsAbstract::log("=============task finished==============",'format_json.log');
                exit;
            }

            foreach ($data as $row){
                $json = $this->format($row['json'],self::CONFIG_STORE);
                //ToolsAbstract::log($json,'format_json.log');

                //插入新表
                $this->add_new_config(2,$json,$row['start_time'],$row['store_id']);
            }

            $maxId = $data[count($data) -1]['entity_id'];
            ToolsAbstract::log("max_id=====".$maxId,'format_json.log');
        }
    }

    public function actionDefaultStore(){
        $data = CoreConfigData::findOne(['path' => 'merchant_config/homepage/default_json']);
        $json = $this->format($data->value,self::CONFIG_STORE_DEFAULT);

        $data->value = $json;
        $data->save();
    }

    private function format($json,$type){
        $json = json_decode($json,true);
        //快捷入口
        if(isset($json['quick_entry_blocks'])){
            $quick_entry_block = $json['quick_entry_blocks'];
            $quick_entry_block['sort'] = MerchantResourceAbstract::HOME_ENTRY_BLOCK_DEFAULT_SORT;
            if(!empty($quick_entry_block['quick_entry'])){
                foreach ($quick_entry_block['quick_entry'] as &$entry){
                    $entry['href_backup'] = '';
                    $entry['compare_type'] = '';
                    $entry['version'] = '';
                }
                ToolsAbstract::log($quick_entry_block,'aaa.log');
            }

            $json['quick_entry_blocks'] = [$quick_entry_block];
        }

        //商品模块
        if(isset($json['product_blocks'])){
            foreach ($json['product_blocks'] as $k=>$block){
                $json['product_blocks'][$k]['show_type'] = 1;
                $json['product_blocks'][$k]['products'] = !empty($block['products']) ? implode(',',$block['products']) : '';
            }
        }

        //品牌模块
        if(isset($json['brand_block'])){
            $brand_block = $json['brand_block'];
            if(isset($brand_block['brand_id'])){
                foreach ($brand_block['brand_id'] as $brand_id){
                    $brand_block['brands'] []= array(
                        'brand_id' => $brand_id,
                        'url' => ''
                    );
                }
            }

            $json['brand_blocks'] = [$brand_block];
            unset($json['brand_block']);
        }

        //热门推荐商品模块
        if(in_array($type,[self::CONFIG_HOME,self::CONFIG_STORE,self::CONFIG_STORE_DEFAULT])){
            $json['hot_recommend_block'] = array(
                'sort' => 50,//首页该模块固定在底部显示，所以给大点
                'title' => '热门推荐商品',
                'title_img' => ''
            );
        }

        //供应商模块不变
        if(isset($json['store'])){
            $json['store_blocks'] = [$json['store']];
            unset($json['store']);
        }

        //专题模块
        if(isset($json['topic_blocks'])){
            foreach ($json['topic_blocks'] as $k=>&$block){
                //$json['topic_blocks'][$k]['sync_to_recommend'] = false;
                if($type == self::CONFIG_HOME){
                    $block['sync_to_recommend'] = false;
                }

                if(!empty($block['banner'])){
                    foreach ($block['banner'] as &$banner){
                        $banner['href_backup'] = '';
                        $banner['compare_type'] = '';
                        $banner['version'] = '';
                    }
                }
            }
        }

        //秒杀模块
        if($type == self::CONFIG_HOME){
            $json['seckill_blocks'] = [
                array(
                    'sort' => 11,
                )
            ];
        }


        return json_encode($json);
    }

    private function add_new_config($type,$content,$start_time,$refer_id){
        try
        {
            $model = new HomePageConfig();
            $model->type = $type;
            $model->refer_id = $refer_id;
            $model->version = '2.0';
            $model->start_time = $start_time;
            $model->content = $content;
            $model->create_time = date("Y-m-d H:i:s");
            //$model->setAttributes($config_data);
            //ToolsAbstract::log($model->attributes,'format_json.log');
            $model->save();
        }
        catch (\Exception $exception){
            ToolsAbstract::log($exception->getMessage(),'format_json.log');
        }
    }

    public function actionNew(){
        $maxId = 0;
        $num = 50;

        while (true){
            $data = HomePageConfig::find()
                ->where(['>','entity_id',$maxId])
                ->orderBy('entity_id ASC')
                ->limit($num)->asArray()->all();

            if(!$data) {
                ToolsAbstract::log("=============task finished==============",'format_json.log');
                exit;
            }

            foreach ($data as $row){
                $json = json_decode($row['content'],true);

                //专题模块
                if(isset($json['topic_blocks'])){
                    foreach ($json['topic_blocks'] as $k=>&$block){
                        if(isset($block['topic_type'])){
                            $block['topic_type'] = intval($block['topic_type']);
                        }
                    }
                }

                //更新
                $model = HomePageConfig::findOne(['entity_id' => $row['entity_id']]);
                $model->content = json_encode($json);
                $model->save();
            }

            $maxId = $data[count($data) -1]['entity_id'];
            ToolsAbstract::log("max_id=====".$maxId,'format_json.log');
        }
    }
}