<?php

namespace service\tasks;

use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class test extends TaskAbstract
{
    public function run($data)
    {
        ToolsAbstract::log($data, 'test.log');
    }
}