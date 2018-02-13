<?php
/**
 * 供货商综合得分规则
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;

use common\models\SecKillActivity;
//use common\models\BlackList;
//use common\models\GreyList;
use common\models\SpecialProduct;
use common\models\LeMerchantStore;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use service\components\Tools;
//use service\components\Proxy;
use framework\components\mq\Merchant;

class seckillPush extends TaskAbstract
{
    //灰名单缓存，键前缀
    const GREY_LIST_KEY_PREFIX = 'sk_greylist_%s';
    //黑名单缓存，键前缀
    const BLACK_LIST_KEY_PREFIX = 'sk_blacklist_%s';

    public function run($data)
    {
        //获取5分钟内要开始的未推送的活动
        $activity_data = SecKillActivity::getPushActivities();
        //Tools::log($activity_data,'seckillpush.log');
        if(empty($activity_data)){
            return;
        }

        $redis = Tools::getRedis();
        $push_data = array();
        $act_ids = array();
        //$cities = array();
        //$activity_map = array();
        foreach ($activity_data as $activity){
            /*if(!in_array($activity['city'],$cities)){
                $cities []= $activity['city'];
            }*/

            $city = intval($activity['city']);
            $act_ids []= $activity['entity_id'];
            //获取一个活动对应的配送区域
            $area_ids = $this->_get_area_ids_by_act_id($activity['entity_id']);
            Tools::log('$area_ids========='.$city,'seckillpush.log');
            Tools::log($area_ids,'seckillpush.log');

            /*$activity_map[$activity['city']] = array(
                'activity_id' => $activity['entity_id'],
                'area_ids' => $area_ids,
            );*/

            //获取该城市黑名单
            $black_list_key = sprintf(self::BLACK_LIST_KEY_PREFIX,$city);
            $black_list = $redis->hKeys($black_list_key);
            Tools::log('$black_list========='.$city,'seckillpush.log');
            Tools::log($black_list,'seckillpush.log');

            //获取该城市灰名单
            $grey_list_key = sprintf(self::GREY_LIST_KEY_PREFIX,$city);
            $grey_list = $redis->hKeys($grey_list_key);
            Tools::log('$grey_list========='.$city,'seckillpush.log');
            Tools::log($grey_list,'seckillpush.log');

            //合并黑名单和灰名单
            $reject_list = array_merge($black_list,$grey_list);

            $push_data []= array(
                'city' => $city,
                'activity_id' => $activity['entity_id'],
                'area_ids' => $area_ids,
                'reject_list' => $reject_list,
                'message' => array(
                    'title' => '温馨提示',
                    'content' => '5分钟之后，秒杀专区将有超值低价的商品开抢哦~'
                )
            );
        }

        //获取黑名单
        //$black_list = BlackList::getBlackListByCity($cities);


        //获取灰名单
        //$grey_rules = GreyList::getGreyListByCity($cities);
//        $grey_list = array();
//        if(!empty($grey_rules)){
//            $grey_list = proxy::getGreyList($grey_rules);
//        }


        //黑名单和灰名单汇总
//        $reject_list_group = array();
//        if(!empty($black_list)){
//            foreach ($black_list as $item){
//                if(!isset($reject_list_group[$item['city']])){
//                    $reject_list_group[$item['city']] = array();
//                }
//
//                if(!in_array($item['customer_id'],$reject_list_group[$item['city']])){
//                    $reject_list_group[$item['city']] []= $item['customer_id'];
//                }
//
//            }
//        }
//
//        if(!empty($grey_list)){
//            foreach ($grey_list as $item){
//                if(!isset($reject_list_group[$item['city']])){
//                    $reject_list_group[$item['city']] = array();
//                }
//
//                if(!in_array($item['customer_id'],$reject_list_group[$item['city']])){
//                    $reject_list_group[$item['city']] []= $item['customer_id'];
//                }
//            }
//        }
//        Tools::log('$reject_list_group=========','seckillpush.log');
//        Tools::log($reject_list_group,'seckillpush.log');

//        $push_data = array();
//        foreach ($reject_list_group as $k=>$v){
//            $push_data []= array(
//                'city' => $k,
//                'activity_id' => $activity_map[$k]['activity_id'],
//                'area_ids' => $activity_map[$k]['area_ids'],
//                'reject_list' => $v,
//                'message' => array(
//                    'title' => '温馨提示',
//                    'content' => '5分钟之后，秒杀专区将有超值低价的商品开抢哦~'
//                )
//            );
//        }

        $result = Merchant::publishPushEvent($push_data);
        if($result){
            foreach ($act_ids as $act_id){
                $activity = SecKillActivity::findOne(['entity_id'=>$act_id]);
                $activity->setAttribute('has_pushed',SecKillActivity::STATUS_PUSHED);
                $activity->save();
            }
        }
    }

    //获取一个活动对应的所有配送区域，通过活动找商品，再找供应商，再找配送区域
    private function _get_area_ids_by_act_id($act_id){
        $area_id_data = SpecialProduct::find()->alias('p')
            ->where([
                'p.activity_id' => $act_id,
                'p.type2' => SpecialProduct::TYPE_SECKILL,
                'p.status' => SpecialProduct::STATUS_ENABLED
            ])->leftJoin(['store' => LeMerchantStore::tableName()],'store.entity_id = wholesaler_id')
            ->select('store.area_id')
            ->asArray()
            ->all();

        $area_ids = array();
        foreach ($area_id_data as $row){
            $area_ids = array_merge($area_ids,explode('|',$row['area_id']));
        }

        $area_ids = array_unique(array_filter($area_ids));
        return $area_ids;
    }



}