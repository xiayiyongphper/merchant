<?php

namespace console\controllers;

use service\message\contractor\PlanGroupEditRequest;
use yii\console\Controller;
use service\components\Tools;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-2-9
 * Time: 上午10:16
 */
class TestController extends Controller
{
    public function actionIndex()
    {
        $plan = new PlanGroupEditRequest();
        $plan->setFrom([
                'contractor_id' => 1,
                'auth_token' => '123',
                'city' => 441800,
                'plan_group' => [
                    'name' => 123
                ]
            ]
        );
        print_r($plan->toArray());
    }

    public function actionRun()
    {
        $version1 = '2.9.0';
        $version2 = '2.9';
        if (Tools::compareVersion($version1, $version2, 'eq')) {
            Tools::log('=', 'test.log');
        } elseif (Tools::compareVersion($version1, $version2, 'gt')) {
            echo '>';
            Tools::log('>', 'test.log');
        } elseif (Tools::compareVersion($version1, $version2, 'lt')) {
            echo '<';
            Tools::log('<', 'test.log');
        }
    }

}