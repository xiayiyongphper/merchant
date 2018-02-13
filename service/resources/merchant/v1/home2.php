<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */
namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantStoreExtend;
use common\models\HomePageConfig;
use common\models\LeBanner;
use common\models\LeMerchantDelivery;
use common\models\LeMerchantProductList;
use common\models\Products;
use common\models\RegionArea;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\core\getWholesalerResponse;
use service\message\core\HomeRequest;
use service\message\core\HomeResponse;
use service\message\customer\CustomerResponse;
use service\message\merchant\getStoresByAreaIdsRequest;
//use service\models\HomePageConfig;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class home2 extends MerchantResourceAbstract
{
    protected $_areaId;
    protected $_cityId;
    protected $_isRemote;
    protected $_platform;
    protected $_wholesalerIds;
    protected $_data = [];
    protected $_configDate = [];
    protected $_wholesalerNames;

    /**
     * @param \ProtocolBuffers\Message $data
     * @return HomeResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var HomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customerResponse = $this->_initCustomer($request);
        //接口验证用户
        $response = $this->response();
        $areaId = $customerResponse->getAreaId();
        $city = $customerResponse->getCity();
        //区域内店铺IDs
        $wholesalerIds = $this->getWholesalerIdsByAreaId($areaId);
        if (count($wholesalerIds) == 0) {
            return $response;
        }
        $this->_areaId = $areaId;
        $this->_cityId = $city;
        $this->_wholesalerIds = $wholesalerIds;
        $this->toArray();
        //Tools::log($data,'wangyang.log');
        $response->setFrom(Tools::pb_array_filter($this->_data));
        return $response;
    }

    /**
     * @return mixed
     */
    protected function parseJson()
    {
        $featured = HomePageConfig::find()
            ->where(['refer_id' => $this->_cityId])
            ->andWhere(['type' => HomePageConfig::CONFIG_TYPE_HOME])
            ->andWhere(['<=', 'start_time', ToolsAbstract::getDate()->date()])
            ->andWhere(['like', 'version', '2.0'])
            ->orderBy('start_time DESC');
        $featured = $featured->asArray()->one();
        $json = $featured['content'];
        $json = json_decode($json, true);

        //json新格式转回旧格式
        if(isset($json['quick_entry_blocks']) && isset($json['quick_entry_blocks'][0])){
            $json['quick_entry_blocks'] = $json['quick_entry_blocks'][0];
        }
        if(isset($json['brand_blocks']) && isset($json['brand_blocks'][0])){
            $json['brand_block'] = $json['brand_blocks'][0];
            if(isset($json['brand_blocks'][0]['brands'])){
                $json['brand_block']['brand_id'] = [];
                foreach ($json['brand_blocks'][0]['brands'] as $brand){
                    $json['brand_block']['brand_id'] []= $brand['brand_id'];
                }
            }

            unset($json['brand_blocks']);
        }

        if(isset($json['topic_blocks'])){
            foreach ($json['topic_blocks'] as $k=>$topic_block){
                if(!in_array($this->_areaId,$topic_block['area_ids'])){
                    unset($json['topic_blocks'][$k]);
                }
                if($topic_block['topic_type'] > 4){
                    unset($json['topic_blocks'][$k]);
                }
            }
        }

        if(isset($json['store_blocks']) && !empty($json['store_blocks'][0])){
            $json['store'] = $json['store_blocks'][0];
        }

        return $json;
    }


    protected function toArray()
    {
        $key = 'merchant_home_page_v2_'.$this->_areaId;
        //APP与PC的首页返回区分
        if ($this->getRedisCache()->exists($key)) {
            $this->_data = unserialize($this->getRedisCache()->get($key));
        } else {
            //$this->_configDate = $this->parseJson();
            $this->_configDate = HomePageConfig::parseJson($this->_cityId,$this->_areaId);
            //banner
            $this->getHomeBanner();
            //当日特价等
            $this->getProductBlock();
            //专题
            $this->getTopicBlock();
            //推荐商家
            $this->processStore();
            //分类
            $this->getCategories();
            //热门商品
            $this->getFeaturedBlock();
            $this->getRedisCache()->set($key, serialize($this->_data),3600); //1小时缓存过期
        }
    }

    protected function getHomeBanner()
    {
        $date = new Date();
        $now = $date->gmtDate();
        // 返回的
        $banner = array();
        // 加上店铺banner逻辑
        $banners = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ]
        )->joinWith('areabanner')
            ->andWhere(['le_area_banner.area_id' => $this->_areaId,])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc')
            ->asArray()->all();
        if (count($banners) > 0) {
            foreach ($banners as $item) {
                $addImg = [
                    'href' => $item['url'],
                    'src' => $item['image'],
                ];
                array_unshift($banner, $addImg);
            }
        } else {
            // 默认的
            $banner = [
                [
                    'href' => '',
                    'src' => self::$homeBannerDefault,
                ]
            ];
        }
        $this->_data['banner'] = $banner;
        return $this;
    }


    protected function getProductBlock()
    {

        $product_blocks = isset($this->_configDate['product_blocks']) ? $this->_configDate['product_blocks'] : '';
        //Tools::log($product_blocks,'wangyang.log');
        if (!$product_blocks) {
            return $this;
        }
        foreach ($product_blocks as $product_block) {
            $block = [];
            $block['title'] = isset($product_block['title']) ? $product_block['title'] : '';
            $productIds = isset($product_block['products']) ? $product_block['products'] : [];
            if (!$productIds || count($productIds) == 0) {
                continue;
            }
            $limit = isset($product_block['size']) ? $product_block['size'] : 30;
            $products = (new ProductHelper())
                ->initWithProductIds($productIds, $this->_cityId)
                ->getTags()
                ->getData();

            $block['products'] = array_slice($products, 0, $limit);
//            $block['products'] = $this->getProductsArrayPro2($productIds,$this->_cityId,$limit);
            $this->_data['product_blocks'][] = $block;
        }
        //Tools::log($this->_data['product_blocks'],'wangyang.log');
        return $this;
    }

    protected function getTopicBlock()
    {
        $topicBlocks = isset($this->_configDate['topic_blocks']) ? $this->_configDate['topic_blocks'] : '';
        if (!$topicBlocks) {
            return $this;
        }
        //Tools::log($topicBlocks,'wangyang.log');
        $topics = [];
        foreach ($topicBlocks as $topicBlock) {
            //新样式专题使用
            if (isset($topicBlock['type'])) {
                continue;
            }
            $topicBanners['banner'] = $topicBlock['banner'];
            $topics[] = $topicBanners;
        }
        $this->_data['topic_blocks'] = $topics;
        return $this;
    }

    /**
     * @return $this
     */
    protected function processStore()
    {
        $merchantModel = new LeMerchantStoreExtend();
        $wholesalerIds = $merchantModel::find()->where(['like', 'area_id', '|' . $this->_areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0])
            ->orderBy('sort desc')
            ->limit(3)
            ->column();

        if (count($wholesalerIds) == 0) {
            return $this;
        }

        $stores = self::getStoreDetailBrief($wholesalerIds, $this->_areaId, 'sort desc');
        $this->_data['store'] = $stores;
        return $this;
    }

    protected function getCategories()
    {

        $categories = Redis::getCategories($this->ids);
        $_categories = [];
        foreach ($categories as $category) {
            $_categories[] = [
                'category_id' => $category['id'],
                'name' => $category['name'],
                'icon' => Tools::$_categoryIconUrlPre . $category['id'] . '.png',
            ];
        }
        $this->_data['category'] = $_categories;

    }

    protected function getFeaturedBlock()
    {
        $identifier = 'featured_product_list';
        $items = LeMerchantProductList::find()
            ->where(['identifier' => $identifier])
            ->andWhere(['status' => 1])
            ->andWhere(['wholesaler_id' => $this->_wholesalerIds])
            ->all();
        $product_id_collection = [];
        /** @var LeMerchantProductList $item */
        foreach ($items as $item) {
            $product_ids = array_filter(explode(';',$item->product_id));
            $product_id_collection = array_unique(array_merge($product_id_collection,$product_ids));
        }

        if (count($product_id_collection)) {
            $products = (new ProductHelper())->initWithProductIds($product_id_collection,$this->_cityId,$this->_wholesalerIds)
                ->getTags()
                ->getData();
            //按权重排序
            $sort = array(
                'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'sort_weights',       //排序字段
            );
            $productsSort = array();
            foreach($products AS $product_id => $product){
                foreach($product AS $key=>$value){
                    $productsSort[$key][$product_id] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($productsSort[$sort['field']], constant($sort['direction']), $products);
            }
            $products = array_slice($products,0,60);
            $this->_data['featured_block']['products'] = $products;
        }

    }

    public function getWholesalerName($wholesalerId)
    {
        if (!$this->_wholesalerNames) {
            $this->_wholesalerNames = Redis::getWholesalersColumn($this->_wholesalerIds, 'store_name');
        }
        $wholesalerName = '';
        if (isset($this->_wholesalerNames[$wholesalerId])) {
            $wholesalerName = $this->_wholesalerNames[$wholesalerId];
        }
        return $wholesalerName;
    }

    /**
     * @return \framework\redis\Cache
     */
    protected function getRedisCache()
    {
        return \Yii::$app->redisCache;
    }

    public static function request()
    {
        return new HomeRequest();
    }

    public static function response()
    {
        return new HomeResponse();
    }

}
