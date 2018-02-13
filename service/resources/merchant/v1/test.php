<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */

namespace service\resources\merchant\v1;

use framework\components\ToolsAbstract;
use service\message\customer\TestReportRequest;
use service\resources\MerchantResourceAbstract;

class test extends MerchantResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param $data
     * @return mixed|void
     */
    public function run($data)
    {
        /** @var TestReportRequest $request */
        $request = new TestReportRequest();
        $request->parseFromString($data);
        $a = sleep(15);
        ToolsAbstract::log('sleep:' . $a, 'kill_task_worker.log');
    }

    public static function request()
    {
        return new TestReportRequest();
    }

    public static function response()
    {
        return true;
    }

}