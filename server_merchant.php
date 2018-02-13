<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/17
 * Time: 11:46
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/common/config/env.php');
require(__DIR__ . '/vendor/lelaisoft/framework/autoload.php');
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/common/config/bootstrap.php');
require(__DIR__ . '/service/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/common/config/main-local.php'),
    require(__DIR__ . '/service/config/main.php'),
    require(__DIR__ . '/service/config/main-local.php')
);


global $argv;
$startFile = $argv[0];

if (!isset($argv[1])) {
    exit("Usage: php {$startFile} {start|stop|reload}\n");
}

$cmd = $argv[1];
try {
    if ($cmd == 'start') {
        $application = new \framework\core\SOAServer($config);
        $application->serve();
    } else {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $client->connect('127.0.0.1', 9502);
        $send = pack('N', \framework\components\TStringFuncFactory::create()->strlen($cmd)) . $cmd;
        $client->send($send);
        $result = $client->recv();
        echo $result;
        $client->close();
    }
} catch (\Exception $e) {
    echo $e;
} catch (\Error $e) {
    echo $e;
}