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
use service\models\ProductHelper;
use yii\db\Expression;
use common\models\Products;
use framework\components\Date;
use common\models\BestSellingProduct;
use service\resources\MerchantResourceAbstract;

class storeHomeConfig extends config
{
    protected $_storeId;

    public function __construct($customer,$appVersion,$storeId){
        $this->_storeId = $storeId;
        parent::__construct($customer,$appVersion);
        parent::_initConfigData(parent::CONFIG_TYPE_STORE_HOME,$storeId);

        //供货商首页，如果没有配置则使用默认配置
        //Tools::log($this->_configData,'aaa.log');
        if(empty($this->_configData)){
//            Tools::log("get default config data==========",'aaa.log');
            $default_json = Tools::getSystemConfigByPath('merchant_config/homepagenew/default_json');
            if($default_json){
                $this->_configData = json_decode($default_json, true);
            }
        }

//        Tools::log($this->_configData,'aaa.log');
    }

    public function toArray()
    {
        if(!$this->_configData){
            return $this->_data;
        }

        //热门推荐不在这里返回
        $modules = [
            //parent::MODULE_TAG,
            parent::MODULE_BRAND,
            parent::MODULE_PRODUCT,
            parent::MODULE_QUICK_ENTRY,

            //供应商首页没有推荐供应商模块
            //parent::MODULE_STORE,
            parent::MODULE_TOPIC,
        ];
        foreach ($modules as $module){
            $this->getModuleData($module);
        }

        //ToolsAbstract::log($this->_data,'config.log');
        return $this->_data;
    }

    protected function getProductBlocks(){
        $product_blocks = isset($this->_configData['product_blocks']) ? $this->_configData['product_blocks'] : '';
        if (!$product_blocks) {
            return;
        }

        $this->_data['product_blocks'] = [];
        foreach ($product_blocks as $product_block) {
            if (!$this->timeRangeValidate($product_block)) {
                continue;
            }

            $block = [];
            $block['subtitle'] = isset($product_block['subtitle']) ? $product_block['subtitle'] : '';
            $product_block_title_img = !empty($product_block['product_block_title_img']) ? $product_block['product_block_title_img'] : '';
            if($product_block_title_img){
                $height = Tools::getImageHeightByUrl($product_block_title_img);
                $block['product_block_title_img']['src'] = $product_block_title_img;
                $block['product_block_title_img']['height'] = $height;
            }

            $productIds = !empty($product_block['products']) ? explode(',',$product_block['products']) : [];

            //因为供应商首页商品模块 每日特价要填充特价商品，这里没配置商品id时，不能直接跳过
//            if (!$productIds || count($productIds) == 0) {
//                continue;
//            }

            $limit = isset($product_block['size']) && $product_block['size'] <= self::PRODUCT_BLOCK_MAX_SIZE ? $product_block['size'] : self::PRODUCT_BLOCK_MAX_SIZE;
            $products = (new ProductHelper())->initWithProductIds($productIds, $this->_cityId, $this->_wholesalerIds)
                ->getTags()
                ->getData();

            //过滤掉不属于此供应商的商品
            $productIds = [];
            foreach ($products as $k=>$v){
                if($v['wholesaler_id'] != $this->_storeId){
                    unset($products[$k]);
                }else{
                    $productIds []= $v['product_id'];
                }
            }

            if(isset($product_block['title']) && $product_block['title'] == '每日特价'){
                $fill_count = $limit - count($products);
                if($fill_count > 0){
                    $fill_products = $this->_getFillProducts($fill_count,$productIds);
                    $products = array_merge($products,$fill_products);
                }
            }

            $block['sort'] = isset($product_block['sort']) ? $product_block['sort'] : MerchantResourceAbstract::HOME_PRODUCT_BLOCK_DEFAULT_SORT;
            // remark
            if (isset($product_block['remark'])) {
                $block['remark'] = $product_block['remark'];
            }
            $block['title'] = isset($product_block['title']) ? $product_block['title'] : '';
            $block['url'] = isset($product_block['url']) ? $product_block['url'] : '';

            $block['show_type'] = isset($product_block['show_type']) ? $product_block['show_type'] : 0;

            if (count($products) <= $limit) {
                $block['check_more'] = '';
            } else {
                if ($block['show_type'] == 1) {//平铺
                    //多行排列，如果要显示查看更多，如果是3的倍数，需要腾出一个位置给 查看更多，所以 $limit 要减 1
                    if ($limit % 3 == 0) {
                        $products = array_slice($products, 0, $limit - 1);
                    } else {
                        $products = array_slice($products, 0, $limit);
                    }
                    $block['check_more'] = self::CHECK_MORE_IMAGE_PAVE;
                } elseif ($block['show_type'] == 2) {//左右滑动
                    $block['check_more'] = self::CHECK_MORE_IMAGE_SLIDE;
                } else {
                    $block['check_more'] = 'xxxxxxxxx';//不为空就行，前端判断是否为空
                }
            }

            $block['products'] = array_slice($products, 0, $limit);
            if (count($block['products']) > 0) {
                $this->_data['product_blocks'][] = $block;
            }
        }
    }

    protected function getTopicBlocks()
    {
        parent::getTopicBlocks();
        if(empty($this->_data['topic_blocks'])) return;

        //供应商首页 href 特殊处理
        foreach ($this->_data['topic_blocks'] as &$topicBlock){
            foreach ($topicBlock['banner'] as &$banner) {
                if(!empty($banner['href'])){
                    $banner['href'] = str_replace('$wholesaler_id', $this->_storeId, $banner['href']);
                }
            }
        }
    }

    //复写父类函数，只要当前供应商的
    protected function getBestSellingProduct($page, $pageSize)
    {
        $model = BestSellingProduct::find()->select('b.order_num,p.*')->alias('b');
        $model->where(['b.wholesaler_id' => $this->_storeId]);
        $productModel = new Products($this->_cityId);
        $model->leftJoin(['p' => 'lelai_booking_product_a.'.$productModel->tableName()],'b.product_id = p.entity_id');
        $model->andWhere([
            'p.status' => 1,
            'p.state' => 2
        ]);
        $date = new Date();
        $now = $date->date("Y-m-d H:i:s");
        $model->andWhere(['<=','p.shelf_from_date',$now]);
        $model->andWhere(['>','p.shelf_to_date',$now]);

        $model->orderBy('b.order_num desc');
        Tools::log($model->createCommand()->getRawSql(),'topic.log');

        $totalCount = $model->count();
        //产品要求，只有给最多30条产品，总数设置最大30，翻页只到第一页
        $totalCount = $totalCount > 30 ? 30 : $totalCount;

        $model->limit($pageSize)->offset($pageSize * ($page - 1));
        $rows = $model->asArray()->all();

        return [$totalCount,$rows];
    }

    private function _getFillProducts($fill_count,$selected_pro_ids){
        $date = new Date();
        $now = $date->date();
        $model = new Products($this->_cityId);
        $productIds = $model->find()->select(['entity_id'])->where(['wholesaler_id'=>$this->_storeId])
            ->andWhere(['NOT IN', 'entity_id', $selected_pro_ids])
            ->andWhere(new Expression('special_price<price'))
            ->andWhere(['>', 'special_price', 0])
            ->andWhere(['<', 'special_from_date', $now])
            ->andWhere(['>', 'special_to_date', $now])
            ->andWhere(['state'=>2])
            ->andWhere(['status'=>1])
            ->limit($fill_count)
            ->all();

        $fillProducts = [];
        if(!empty($productIds)){
            $fillProIds = [];
            foreach ($productIds as $pro){
                $fillProIds[] = $pro['entity_id'];
            }

            $fillProducts = (new ProductHelper())->initWithProductIds($fillProIds, $this->_cityId, $this->_wholesalerIds)
                ->getTags()
                ->getData();
        }

        return $fillProducts;
    }
}