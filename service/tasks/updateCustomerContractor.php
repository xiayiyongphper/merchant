<?php
/**
 * 定时更新超市业务员名字
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use framework\tasks\TaskAbstract;
use service\components\Tools;

class updateCustomerContractor extends TaskAbstract
{

    public function run($data)
    {
        Tools::log('updateCustomerContractor','updateCustomerContractor.log');
        /** @var \yii\db\Connection $customerDb */
        $customerDb = \Yii::$app->get('customerDb');
        $sql = 'UPDATE `le_customers` as c set contractor = (SELECT name FROM contractor WHERE entity_id = c.contractor_id)';
        $command  = $customerDb->createCommand($sql);
        $result = $command->execute();
        Tools::log($result,'updateCustomerContractor.log');
    }
}