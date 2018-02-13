<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */

namespace service\resources\merchant\v1;

use common\models\Brand;
use common\models\HomePageConfig;
use common\models\LeBanner;
use common\models\LeMerchantStoreCategory;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\message\core\HomeRequest;
use service\message\core\HomeResponse2;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

class home5 extends MerchantResourceAbstract
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
     * @return HomeResponse2
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var HomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        //接口验证用户
        $customerResponse = $this->_initCustomer($request);
        $this->_areaId = $customerResponse->getAreaId();
        $this->_cityId = $customerResponse->getCity();
        $this->_customerId = $customerResponse->getCustomerId();
        //区域内店铺IDs
        $this->_wholesalerIds = $this->getWholesalerIdsByAreaId($this->_areaId);

        $response = $this->response();
        //无供应商时
        if (count($this->_wholesalerIds) == 0) {
            return $response;
        }
        $this->toArray();
//        Tools::log(Tools::pb_array_filter($this->_data), 'wangyang.log');
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
        $key = 'merchant_home_page_v5_' . $this->_areaId;
        //APP与PC的首页返回区分
        if (false && $this->getRedisCache()->exists($key)) {
            $this->_data = unserialize($this->getRedisCache()->get($key));
        } else {
            //$this->_configDate = $this->parseJson();
            $this->_configDate = HomePageConfig::parseJson($this->_cityId,$this->_areaId);
//            Tools::log($this->_configDate,'wangyang.log');
            //最上方banners
            $this->getHomeBanner();
            //弧形
            $this->getHomeCamberBanner();
            //最上方banner，下方的banner
            $this->getHomeSecondBanner();
            //三赔入口
            $this->getTag();
            //快捷入口
            $this->getQuickEntryBlock();
            //当日特价等
            $this->getProductBlock();
            //专题
            $this->getTopicBlock();
            //推荐商家
            $this->processStore();
            //推荐品牌
            $this->processBrand();
            $this->getRedisCache()->set($key, serialize($this->_data), 3600);//1小时缓存过期
        }
    }

    protected function processBrand()
    {
        $brand = isset($this->_configDate['brand_block']['brand_id']) ? $this->_configDate['brand_block']['brand_id'] : [];
//        Tools::log($brand, 'wangyang.log');
        if (!is_array($brand) || !$brand) {
            return $this;
        }

        $brands = Brand::find()->where(['entity_id' => $brand])->all();

        $brand_data = [];
        /** @var Brand $one_brand */
        foreach ($brands as $one_brand) {
            $brand_tmp['brand_id'] = $one_brand->entity_id;
            $brand_tmp['name'] = $one_brand->name;
            $brand_tmp['icon'] = $one_brand->icon;
            $brand_data['brands'][] = $brand_tmp;
        }
//        Tools::log($brand_data, 'wangyang.log');
        if (count($brand_data) > 0) {
            $brand_data['sort'] = isset($this->_configDate['brand_block']['sort']) ? $this->_configDate['brand_block']['sort'] : self::HOME_BRAND_BLOCK_DEFAULT_SORT;
            $this->_data['brand_block'] = $brand_data;
        }
        return $this;
    }

    /**
     * 只要区域内有供应商支持三赔，就显示三赔
     */
    protected function getTag()
    {

        if (self::getCompensationWholesalerCountByAreaId($this->_areaId) > 0) {
            $this->_data['tag'] = [
                'text' => '送货慢立即现金赔偿，点击查看',
                'icon' => 'http://assets.lelai.com/assets/secimgs/homepei1.png',
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
        $quickEntryArray = isset($quickEntryBlocks['quick_entry']) ? $quickEntryBlocks['quick_entry'] : [];
        $quickEntryBlocksImage = isset($quickEntryBlocks['background_img']) ? $quickEntryBlocks['background_img'] : '';

        if (!$quickEntryArray) {
            return $this;
        }

        $quickEntries = [];
        foreach ($quickEntryArray as $quickEntryBlock) {
            $quickEntry['src'] = isset($quickEntryBlock['src']) ? $quickEntryBlock['src'] : '';
            $quickEntry['href'] = isset($quickEntryBlock['href']) ? $quickEntryBlock['href'] : '';
            $quickEntry['title'] = isset($quickEntryBlock['title']) ? $quickEntryBlock['title'] : '';

            $quickEntries[] = array_filter($quickEntry);
        }
        $quick_entry_blocks['quick_entry_blocks'] = $quickEntries;

        if (count($quick_entry_blocks['quick_entry_blocks']) > 0) {
            //首页快捷入口背景图
            if ($quickEntryBlocksImage) {
                $height = Tools::getImageHeightByUrl($quickEntryBlocksImage);
                $quick_entry_blocks['background_img']['height'] = $height;
                $quick_entry_blocks['background_img']['src'] = $quickEntryBlocksImage;
            }
            $this->_data['quick_entry_module'] = $quick_entry_blocks;
        }

        return $this;
    }


    /**
     * Author Jason Y. wang
     * app中top_banner下方的banner
     * @return $this
     */
    protected function getHomeSecondBanner()
    {
        $date = new Date();
        $now = $date->gmtDate();
        // 返回的
        $banner = array();
        // 加上店铺banner逻辑
        $banners = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_second_banner',
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
                //传2.6专用图片
                if ($item['image']) {
                    $height = Tools::getImageHeightByUrl($item['image']);
                    if ($height) {
                        $addImg['height'] = $height;
                    }
                    $addImg['href'] = $item['url'];
                    $addImg['src'] = $item['image'];
                    array_unshift($banner, $addImg);
                }
            }
            $this->_data['second_fixed_banner'] = $banner;
        }
        return $this;
    }

    /**
     * Author Jason Y. wang
     * app中top_banner下方的banner
     * @return $this
     */
    protected function getHomeCamberBanner()
    {
        $date = new Date();
        $now = $date->gmtDate();
        // 加上店铺banner逻辑
        /** @var LeBanner $banner */
        $banner = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_camber_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ]
        )->joinWith('areabanner')
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->one();

        if ($banner) {
            $image = $banner->image;
        } else {
            $image = 'http://assets.lelai.com/images/files/merchant/20170119/source/0_20170119055632file.png?width=640&height=20';
        }

        $addImg = [
            'src' => $image,
        ];
        $this->_data['camber_banner'] = $addImg;
        return $this;
    }


    /**
     * Author Jason Y. wang
     * APP首页BANNER
     * @return $this
     */
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
                //传2.6专用图片
                if ($item['image_big']) {
                    $height = Tools::getImageHeightByUrl($item['image_big']);
                    if ($height) {
                        $addImg['height'] = $height;
                    }

                    //根据版本选用链接
                    //Tools::log($item,'xxx.log');
                    if(isset($item['url'])){
                        if(!empty($item['url_backup']) && !empty($item['compare_type']) && !empty($item['version'])){
                            if(Tools::compareVersion($this->getAppVersion(),$item['version'],$item['compare_type'])){
                                $item['url'] = $item['url_backup'];
                            }
                        }
                    }else{
                        $item['url'] = '';
                    }

                    $addImg = [
                        'href' => $item['url'],
                        'src' => $item['image_big'],
                    ];
                    array_unshift($banner, $addImg);
                }

            }
        }

        if (empty($banner)) {
            $banner = [
                [
                    'href' => '',
                    'src' => self::$homeBannerDefault,
                ]
            ];
        }

        $this->_data['top_fixed_banner'] = $banner;
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
            $block['subtitle'] = isset($product_block['subtitle']) ? $product_block['subtitle'] : '';
            $product_block_title_img = isset($product_block['product_block_title_img']) ? $product_block['product_block_title_img'] : '';
            $height = Tools::getImageHeightByUrl($product_block_title_img);
            $block['product_block_title_img']['src'] = $product_block_title_img;
            $block['product_block_title_img']['height'] = $height;
            $productIds = isset($product_block['products']) ? $product_block['products'] : '';
            if (!$productIds || count($productIds) == 0) {
                continue;
            }
            $limit = isset($product_block['size']) ? $product_block['size'] : 30;
            $products = (new ProductHelper())->initWithProductIds($productIds, $this->_cityId, $this->_wholesalerIds)
                ->getTags()
                ->getData();
            $block['products'] = array_slice($products, 0, $limit);
            $block['sort'] = isset($product_block['sort']) ? $product_block['sort'] : self::HOME_PRODUCT_BLOCK_DEFAULT_SORT;
            if (count($block['products']) > 0) {
                $this->_data['product_blocks'][] = $block;
            }
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

            if (isset($topicBlock['banner'])) {
                $topicBanners['banner'] = $topicBlock['banner'];
            } else {
                continue;
            }


            if (!$topicBlock || count($topicBlock['banner']) == 0) {
                continue;
            }
            $topicBanners = [];
            if (count($topicBlock['banner'])) {
                $banners = $topicBlock['banner'];
                foreach ($banners as $banner) {
                    $banner_show = [];
                    $img = isset($banner['src']) ? $banner['src'] : '';
                    $height = Tools::getImageHeightByUrl($img);
                    $banner_show['height'] = $height;
                    $banner_show['src'] = $img;
                    $banner_show['href'] = isset($banner['href']) ? $banner['href'] : '';
                    $topicBanners['banner'][] = $banner_show;
                }
            }
            $topicBanners['topic_type'] = isset($topicBlock['topic_type']) ? $topicBlock['topic_type'] : 1;
            $title_img = isset($topicBlock['topic_block_title_img']) ? $topicBlock['topic_block_title_img'] : '';
            $height = Tools::getImageHeightByUrl($title_img);
            $topicBanners['title_img']['src'] = $title_img;
            $topicBanners['title_img']['height'] = $height;
            $topicBanners['sort'] = isset($topicBlock['sort']) ? $topicBlock['sort'] : self::HOME_TOPIC_BLOCK_DEFAULT_SORT;
            if (count($topicBanners['banner']) > 0) {
                $topics[] = $topicBanners;
            }
        }
        $this->_data['topic_blocks'] = $topics;
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 供应商列表
     * @return $this
     */
    protected function processStore()
    {
        // 是否按照配置的商家查找
        /** @var  LeBanner $banner */
        $banner = LeBanner::find()
            ->where([
                'le_banner.position' => 'app_home_wholesaler_title_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app'])
            ->one();
        if ($banner) {
            $store_block_title_img = $banner->image;
        } else {
            $store_block_title_img = '';
        }


        $all_merchant_store_category = LeMerchantStoreCategory::find()->all();

        $wholesalerCount = isset($this->_configDate['store']['count']) ? $this->_configDate['store']['count'] : self::HOME_STORE_BLOCK_DEFAULT_COUNT;
        $sort = isset($this->_configDate['store']['sort']) ? $this->_configDate['store']['sort'] : self::HOME_STORE_BLOCK_DEFAULT_SORT;


        $merchant_group_collection = [];
        /** @var LeMerchantStoreCategory $one_merchant_store_category */
        foreach ($all_merchant_store_category as $one_merchant_store_category) {
            $merchant_store_category_id = $one_merchant_store_category->entity_id;
            $merchant_group = [];
            //推荐供应商，供应商权重排序
            $recommendWholesalerIds = self::getWholesalerIdsByMerchantCategory($this->_areaId, $wholesalerCount, $merchant_store_category_id);

//            Tools::log($recommendWholesalerIds,'wangyang.log');
            $wholesalers = self::getStoreDetailBrief($recommendWholesalerIds, $this->_areaId);
            if (count($wholesalers) > 0) {
                $merchant_group['store_list'] = $wholesalers;
                $merchant_group['category'] = $one_merchant_store_category->name;
                $merchant_group_collection[] = $merchant_group;
            }
        }

        if (count($merchant_group_collection)) {
            $store_block['store_group'] = $merchant_group_collection;
            //全部店铺数量
            $store_block['store_count'] = count($this->_wholesalerIds);
            //排序
            if ($sort) {
                $store_block['sort'] = $sort;
            }
            //block上方图片
            if ($store_block_title_img) {
                $store_block['store_block_title_img']['src'] = $store_block_title_img;
            }

            $this->_data['store_block'] = $store_block;
        }

        return $this;
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
        return new HomeResponse2();
    }

}