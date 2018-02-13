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


class homeConfig extends config
{

    public function __construct($customer,$appVersion,$syncToRecommend=false){
        parent::__construct($customer,$appVersion);
        parent::_initConfigData(parent::CONFIG_TYPE_HOME,$customer->getCity());

        //为您推荐模块只要首页配置中同步的专题模块
        if($syncToRecommend && isset($this->_configData['topic_blocks'])){
            $has_sync_model = false;
            foreach ($this->_configData['topic_blocks'] as $topicBlock){
                if(!empty($topicBlock['sync_to_recommend'])){
                    $has_sync_model = true;
                    $this->_configData['topic_blocks'] = [$topicBlock];
                    break;
                }
            }

            if(!$has_sync_model){
                $this->_configData['topic_blocks'] = [];
            }
        }

        //Tools::log($this->_configData['topic_blocks'],'xxx.log');
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
            self::MODULE_SECKILL,
            self::MODULE_STORE,
            self::MODULE_TOPIC,
        ];
        foreach ($modules as $module){
            $this->getModuleData($module);
        }

        //ToolsAbstract::log($this->_data,'config.log');
        return $this->_data;
    }

    //首页公告模块，如果没有连接使用默认连接
    protected function getTagBlocks()
    {
        parent::getTagBlocks();
        if(empty($this->_data['tag_blocks'])) return;

        foreach ($this->_data['tag_blocks'] as $k=>$v){
            if(empty($v['url'])){
                $this->_data['tag_blocks'][$k]['url'] = "http://assets.lelai.com/assets/h5/security/?aid=".$this->_areaId;
            }
        }
    }

}