<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:30
 */

namespace console\controllers;

use service\tasks\calculateScore;
use Yii;
use yii\console\Controller;
use framework\components\ToolsAbstract;

class TaskController extends Controller
{
    /**
     * ManageCrontab
     */
    public function actionManagecrontab()
    {
        $redis = ToolsAbstract::getRedis();
        $timer_key = ToolsAbstract::getCrontabKey();
        echo $timer_key.PHP_EOL;
        $redis->del($timer_key);
        $data1 = [
            'type' => 2,
            'time' => '30 1 * * *',
            'data' => [
                'route' => 'task.calculateScore',
                'params' => '',
            ]
        ];
        $data2 = [
            'type' => 2,
            'time' => '0 3 * * *',
            'data' => [
                'route' => 'task.updateCustomerContractor',
                'params' => '',
            ]
        ];

        $data3 = [
            'type' => 2,
            'time' => '0 2 * * *',
            'data' => [
                'route' => 'task.updateEsProduct',
                'params' => '',
            ]
        ];

        $data4 = [
            'type' => 2,
            'time' => '* * * * *',
            'data' => [
                'route' => 'task.seckillPush',
                'params' => '',
            ]
        ];

        $data5 = [
            'type' => 2,
            'time' => '0 0 * * *',
            'data' => [
                'route' => 'task.generateGreyList',
                'params' => '',
            ]
        ];
        $redis->sAdd($timer_key, json_encode($data1));
        $redis->sAdd($timer_key, json_encode($data2));
        $redis->sAdd($timer_key, json_encode($data3));
        $redis->sAdd($timer_key, json_encode($data4));
        $redis->sAdd($timer_key, json_encode($data5));
        $list = $redis->sMembers($timer_key);
        echo print_r($list, true);
    }

    public function actionAddCrontab(){
        $redis = ToolsAbstract::getRedis();
        $timer_key = ToolsAbstract::getCrontabKey();
        $data = [
            'type' => 1,
            'time' => '* * * * *',
            'data' => [
                'route' => 'task.updateCustomerContractor',
                'params' => '',
            ]
        ];
        $redis->sAdd($timer_key, json_encode($data));
    }

    public function actionCalculateScore(){
        (new calculateScore())->run('');
    }
}