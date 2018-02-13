<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php'),
    require(__DIR__ . '/server-config.php'),
    require(__DIR__ . '/events.php'),
    require(__DIR__ . '/soap.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'service\controllers',
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'request' => ['class' => 'framework\core\SOARequest'],
        'response' => ['class' => 'framework\core\Response'],
        'errorHandler' => [
            'class' => 'framework\ErrorHandler',
        ],
    ],
    'resources' => [
        'merchant' => 'service\resources\merchant',
    ],
    'params' => $params,
];
