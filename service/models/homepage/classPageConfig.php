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


class classPageConfig extends config
{
    public function __construct($customer,$appVersion,$classPageId){
        parent::__construct($customer,$appVersion);
        parent::_initConfigData(parent::CONFIG_TYPE_CLASS_PAGE,$classPageId);
    }

    public function toArray()
    {
        if(!$this->_configData){
            return $this->_data;
        }

        //热门推荐不在这里返回
        $modules = [
            self::MODULE_BRAND,
            self::MODULE_PRODUCT,
            self::MODULE_TOPIC,
        ];
        foreach ($modules as $module){
            $this->getModuleData($module);
        }

        //ToolsAbstract::log($this->_data,'config.log');
        return $this->_data;
    }

    protected function getBrandBlocks()
    {
        parent::getBrandBlocks();
        //返回文字title
        if(!empty($this->_data['brand_blocks'])){
            foreach ($this->_data['brand_blocks'] as &$brand_block){
                $brand_block['title'] = '品牌精选';
            }
        }

    }

    protected function getProductBlocks()
    {
        parent::getProductBlocks();
        //返回文字title
        if(!empty($this->_data['product_blocks'])){
            foreach ($this->_data['product_blocks'] as &$product_block){
                $product_block['title'] = '商品模块';
            }
        }

    }

}