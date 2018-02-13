<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/09/13
 * Time: 17:04
 */
namespace service\models\homepage;

use service\models\homepage\config;
use service\components\Tools;
use framework\components\ToolsAbstract;


class topicConfig extends config
{

    public function __construct($customer,$appVersion,$topicId){
        parent::__construct($customer,$appVersion);
        parent::_initConfigData(parent::CONFIG_TYPE_TOPIC_HOME,$topicId);
        //Tools::log($this->_configData,'tc.log');
    }

    public function toArray()
    {
        if(!$this->_configData){
            return $this->_data;
        }

        //热门推荐不在这里返回
        $modules = [
            self::MODULE_TAG,
            self::MODULE_BRAND,
            self::MODULE_PRODUCT,
            self::MODULE_QUICK_ENTRY,
            self::MODULE_TOPIC,
        ];
        foreach ($modules as $module){
            $this->getModuleData($module);
        }

        //ToolsAbstract::log($this->_data,'config.log');
        return $this->_data;
    }

}