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
use common\models\GreyList;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use service\components\Tools;
use service\components\Redis;
use service\components\Proxy;
use framework\components\mq\Merchant;

class generateGreyList extends TaskAbstract
{
    //黑名单缓存，键前缀
    const GREY_LIST_KEY_PREFIX = 'sk_greylist_%s';

    public function run($data)
    {
        //清空之前的灰名单
        $redis = Tools::getRedis();
        $old_keys = $redis->keys('sk_greylist_*');
        //Tools::log('$old_keys=====','greylist.log');
        //Tools::log($old_keys,'greylist.log');
        foreach ($old_keys as $key){
            $redis->del($key);
        }

        //获取所有灰名单规则
        $grey_rules = GreyList::find()->asArray()->all();
        Tools::log('$grey_rules=========','greylist.log');
        Tools::log($grey_rules,'greylist.log');
        //获取灰名单
        $grey_list = array();
        if(!empty($grey_rules)){
            $grey_list = Proxy::getGreyList($grey_rules);
        }
        Tools::log('$grey_list=========','greylist.log');
        Tools::log($grey_list,'greylist.log');

        //写缓存
        foreach ($grey_list as $item){
            $key = sprintf(self::GREY_LIST_KEY_PREFIX,$item['city']);
            $redis->hSet($key,intval($item['customer_id']),1);
        }
    }
}