<?php
namespace service\models;

use framework\components\ToolsAbstract;
use framework\core\ProcessInterface;
use framework\core\SWServer;
use service\components\Tools;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-2
 * Time: 上午11:12
 */
class Process implements ProcessInterface
{
    /**
     * @inheritdoc
     */
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        $redis = Tools::getRedis();
        $key = ToolsAbstract::getRedisMsgQueueKey();
        while (true) {

            if ($redis->lLen($key) == 0) {
                sleep(1);
                continue;
            }

            $value = $redis->rPop($key);
            $event = json_decode($value, true);
            self::log('================================================================================');
            self::log('string:' . $value);

            if ($value && $event && isset($event['name']) && isset($event['data'])) {
                // 记录输入
                Tools::log('data:' . print_r($event, true), $key . '.log');
                // 分发事件
                $serverEvents = isset(\Yii::$app->params['events']) ? \Yii::$app->params['events'] : [];
                $event_name = $event['name'];
                $event_data = $event['data'];
                // 只有设置了的才trigger
                if (isset($serverEvents[$event_name])) {
                    // 多个响应函数
                    $observers = $serverEvents[$event_name];
                    // 分别触发
                    foreach ($observers as $observer) {
                        $class = $observer['class'];
                        $method = $observer['method'];
                        Tools::log('observer:' . print_r($observer, true), $key . '.log');
                        $observerObj = new $class();
                        $result = $observerObj->$method($event_data);
                    }
                } else {
                    // 没处理,记录一下
                    self::log($event_name . '未定义observer,跳过。');
                }

            } else {
                // value在json_decode后数据结构有问题
                self::log('结构有误,未处理。' . print_r($event, true));
            }
            self::log('--------------------------------------------------------------------------------');
        }
    }

    private static function log($msg)
    {
        $key = ToolsAbstract::getRedisMsgQueueKey();
        Tools::log($msg, $key . '.log');
    }
}