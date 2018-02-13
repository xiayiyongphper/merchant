<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */
namespace service\resources\merchant\v1;

use common\models\HomePageConfig;
use common\models\LeBanner;
use common\models\LeMerchantProductList;
use common\models\Products;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\core\HomeRequest;
use service\message\core\HomeResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class home4 extends MerchantResourceAbstract
{
    protected $_areaId;
    protected $_cityId;
    protected $_customerId;
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
        $customerId = $customerResponse->getCustomerId();
        //区域内店铺IDs
        $wholesalerIds = $this->getWholesalerIdsByAreaId($areaId);
        //Tools::log($wholesalerIds,'wangyang.log');
        if(count($wholesalerIds) == 0){
            return $response;
        }
        $this->_customerId = $customerId;
        $this->_areaId = $areaId;
        $this->_cityId = $city;
        $this->_wholesalerIds = $wholesalerIds;
        $this->toArray();
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
        $key = 'merchant_home_page_v4_' . $this->_areaId;
        //APP与PC的首页返回区分
        if ($this->getRedisCache()->exists($key)) {
            $this->_data = unserialize($this->getRedisCache()->get($key));
        } else {
            //$this->_configDate = $this->parseJson();
            $this->_configDate = HomePageConfig::parseJson($this->_cityId,$this->_areaId);
            //banner
            $this->getHomeBanner();
            //tag
            $this->getTag();
            //QuickEntryBlock
            $this->getQuickEntryBlock();
            //当日特价等
            $this->getProductBlock();
            //专题
            $this->getTopicBlock();
            //推荐商家
            $this->processStore();
            //分类
            //$this->getCategories();
            //热门商品 2.3APP 后先下掉这个模块
            $this->getFeaturedBlock();
            $this->getRedisCache()->set($key, serialize($this->_data), 3600);//1小时缓存过期
        }
    }

    /**
     * 只要区域内有供应商支持三赔，就显示三赔
     */
    protected function getTag()
    {

        if (self::getCompensationWholesalerCountByAreaId($this->_areaId) > 0) {
            $this->_data['tag'] = [
                'text' => '送货慢立即现金赔偿，点击查看',
                'icon' => 'http://assets.lelai.com/assets/secimgs/homepei.png',
                'url' => 'http://assets.lelai.com/assets/h5/security/?aid=' . $this->_areaId,
            ];
        }
    }

    /**
     * getQuickEntryBlock
     * Author Jason Y. wang
     * 首页快捷入口
     * @return $this
     */
    protected function getQuickEntryBlock()
    {
        $quickEntryBlocks = isset($this->_configDate['quick_entry_blocks']) ? $this->_configDate['quick_entry_blocks'] : '';
//        Tools::log($quickEntryBlocks,'wangyang.log');
        if (!$quickEntryBlocks) {
            return $this;
        }
        $quickEntry = isset($quickEntryBlocks['quick_entry']) ? $quickEntryBlocks['quick_entry'] : [];

        if (!$quickEntry) {
            return $this;
        }

        $quickEntries = [];
        foreach ($quickEntry as $quickEntryBlock) {
            $quickEntry['src'] = isset($quickEntryBlock['src']) ? $quickEntryBlock['src'] : '';
            $quickEntry['href'] = isset($quickEntryBlock['href']) ? $quickEntryBlock['href'] : '';
            $quickEntry['title'] = isset($quickEntryBlock['title']) ? $quickEntryBlock['title'] : '';

            $quickEntries[] = array_filter($quickEntry);
        }
        $this->_data['quick_entry_blocks'] = $quickEntries;
        return $this;
    }

    protected function getHomeBanner()
    {
        $date = new Date();
        $now = $date->date();
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
            ->andWhere(['le_area_banner.area_id' => $this->_areaId])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc');
        //Tools::log($banners->createCommand()->getRawSql(),'wangyang.log');
        $banners = $banners->asArray()->all();
        if (count($banners) > 0) {
            foreach ($banners as $item) {
                //有图片地址时才会展示
                if ($item['image']) {
                    $addImg = [
                        'href' => $item['url'],
                        'src' => $item['image'],
                    ];
                    array_unshift($banner, $addImg);
                }
            }
        }

        if(empty($banner)){
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
            $productIds = isset($product_block['products']) ? $product_block['products'] : '';
            if (!$productIds || count($productIds) == 0) {
                continue;
            }
            $limit = isset($product_block['size']) ? $product_block['size'] : 30;
//            Tools::log('==============','wangyang.log');
//            Tools::log($this->_cityId,'wangyang.log');
//            Tools::log($product_block['title'],'wangyang.log');
//            Tools::log($productIds,'wangyang.log');
//            Tools::log($this->_wholesalerIds,'wangyang.log');
            $products = (new ProductHelper())->initWithProductIds($productIds,$this->_cityId,$this->_wholesalerIds)
                ->getTags()
                ->getData();
            $block['products'] = array_slice($products,0,$limit);
            //$block['products'] = $this->getProductsArrayPro2($products,$this->_cityId,$limit,$this->_wholesalerIds);
            $block['sort'] = isset($product_block['sort']) ? $product_block['sort'] : 0;
            $this->_data['product_blocks'][] = $block;
        }

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
            if(isset($topicBlock['banner'])){
                $topicBanners['banner'] = $topicBlock['banner'];
            }else{
                continue;
            }

            if (!$topicBanners || count($topicBlock['banner']) == 0) {
                continue;
            }
            $topicBanners['sort'] = isset($topicBlock['sort']) ? $topicBlock['sort'] : 0;
            $version = isset($topicBlock['version']) ? $topicBlock['version'] : 0;
            if ($version >= 2.6) {
                continue;
            }
            if (count($topicBanners['banner']) > 0) {
                $topics[] = $topicBanners;
            }

        }
        //Tools::log($topics,'wangyang.log');
        if (count($topics)) {
            $this->_data['topic_blocks'] = $topics;
        }
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 供应商列表
     * @return $this
     */
    protected function processStore()
    {
        $wholesalerCount = 5;
        //推荐供应商，优先展示白名单供应商
        $recommendWholesalerIds = self::getWhiteListWholesalerIds($this->_areaId, $wholesalerCount);
        $recommendWholesalerCount = count($recommendWholesalerIds);
        //数量小于N时，获取最近购买供应商
        if ($recommendWholesalerCount < $wholesalerCount) {
            $recentBuyWholesalerIds = Proxy::getRecentlyBuyWholesalerIds($this->_customerId);
            //将最近购买供应商加入推荐供应商
            foreach ($recentBuyWholesalerIds as $key => $recentBuyWholesalerId) {
                if (in_array($recentBuyWholesalerId, $this->_wholesalerIds)) {
                    if (!in_array($recentBuyWholesalerId, $recommendWholesalerIds)) {
                        array_push($recommendWholesalerIds, $recentBuyWholesalerId);
                        if (count($recommendWholesalerIds) == $wholesalerCount) {
                            break;
                        }
                    }
                } else {
                    //去除不在配送区域的供应商
                    unset($recentBuyWholesalerIds[$key]);
                    continue;
                }

            }
            //还是小于N个则用普通供应商填充
            if (count($recommendWholesalerIds) < $wholesalerCount) {
                //将最近购买供应商加入推荐供应商
                foreach ($this->_wholesalerIds as $wholesalerId) {
                    if (!in_array($wholesalerId, $recommendWholesalerIds)) {
                        array_push($recommendWholesalerIds, $wholesalerId);
                        if (count($recommendWholesalerIds) == $wholesalerCount) {
                            break;
                        }
                    }
                }
            }
        }
        //Tools::log('recommendWholesalerIds:'.count($recommendWholesalerIds),'wangyang.log');
        $wholesalers = self::getStoreDetailBrief($recommendWholesalerIds, $this->_areaId);
        $this->_data['store'] = $wholesalers;
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

        //Tools::log($product_id_collection,'wangyang.log');
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