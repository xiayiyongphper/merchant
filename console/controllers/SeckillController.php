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

class SeckillController extends Controller
{
    /**
     * ManageCrontab
     */
    public function actionManagecrontab()
    {
        $redis = ToolsAbstract::getRedis();
        $timer_key = ToolsAbstract::getCrontabKey();
        echo $timer_key.PHP_EOL;
        $data1 = [
            'type' => 2,
            'time' => '* * * * *',
            'data' => [
                'route' => 'task.seckillPush',
                'params' => '',
            ]
        ];
        $redis->sAdd($timer_key, json_encode($data1));
        //$redis->sRem($timer_key, json_encode($data1));    //del
        $list = $redis->sMembers($timer_key);
        echo print_r($list, true);
    }
}