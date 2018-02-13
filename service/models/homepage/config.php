<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/14
 * Time: 17:04
 */
namespace service\models\homepage;

use common\models\extend\LeMerchantStoreExtend;
use common\models\Brand;
use common\models\HomePageConfig;
use common\models\LeBanner;
use common\models\BestSellingProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use Yii;
use yii\base\Exception;
use service\resources\MerchantResourceAbstract;
use service\models\ProductHelper;
use common\models\LeMerchantStoreCategory;
use common\models\Products;
use framework\components\Date;
use yii\db\Expression;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use service\message\customer\CustomerResponse;
use framework\data\Pagination;

class config
{
    protected $_areaId;
    protected $_cityId;
    protected $_customerId = null;
    protected $_customerBelongGroup = [];
    protected $_wholesalerIds = [];
    protected $_data = [];
    protected $_configData = null;

    const QUICK_ENTRY_TITLE_COLOR = '#000000';
    const CHECK_MORE_IMAGE_PAVE = 'http://assets.lelai.com/images/files/merchant/20170525/source/0_20170525021938file.png?width=120&height=65';
    const CHECK_MORE_IMAGE_SLIDE = 'http://assets.lelai.com/images/files/merchant/20170605/source/0_20170605071544file.png?width=75&height=355';
    const HOME_STORE_BLOCK_BANNER = 'http://assets.lelai.com/images/files/merchant/20170608/source/0_20170608100000file.png?width=640&height=150';
    //TODO 热门推荐默认标题图片待确认
    const HOT_RECOMMEND_DEFAULT_TITLE_IMG = 'http://assets.lelai.com/images/files/merchant/20170915/source/0_20170915082122file.png?width=640&height=108';
    const PRODUCT_BLOCK_MAX_SIZE = 18;

    const CONFIG_TYPE_HOME = 1;
    const CONFIG_TYPE_STORE_HOME = 2;
    const CONFIG_TYPE_TOPIC_HOME = 3;
    const CONFIG_TYPE_CLASS_PAGE = 4;

    const MODULE_TAG = 'tag_blocks';
    const MODULE_QUICK_ENTRY = 'quick_entry_blocks';
    const MODULE_TOPIC = 'topic_blocks';
    const MODULE_PRODUCT = 'product_blocks';
    const MODULE_STORE = 'store_blocks';
    const MODULE_BRAND = 'brand_blocks';
    const MODULE_SECKILL = 'seckill_blocks';
    const MODULE_HOT_RECOMMEND = 'hot_recommend_block';

    /**
     * @param CustomerResponse $customer
     *
     */
    public function __construct($customer,$appVersion)
    {
        $this->_areaId = $customer->getAreaId();
        $this->_cityId = $customer->getCity();
        $this->_customer = $customer;
        $this->_appVersion = $appVersion;
        $this->_customerId = $customer->getCustomerId();
        $this->_customerBelongGroup = Tools::getCustomerBelongGroup($this->_customerId);
        $this->_wholesalerIds = LeMerchantStoreExtend::getWholesalerIdsByAreaId($this->_areaId);

    }

    public function getModuleData($module,$params = []){
        $method = "get";
        $words = explode("_",$module);
        foreach ($words as $word){
            $method .= ucfirst($word);
        }

        $this->$method($params);
        if(isset($this->_data[$module])){
            return $this->_data[$module];
        }else{
            return [];
        }
    }

    /**
     * @return mixed
     */
    protected function _initConfigData($type,$referId)
    {
        if(!is_null($this->_configData)){
            return true;
        }

        $featured = HomePageConfig::find()
            ->where(['type' => $type])
            ->andWhere(['refer_id' => $referId])
            ->andWhere(['<=', 'start_time', ToolsAbstract::getDate()->date()])
            ->andWhere(['like', 'version', '2.0'])
            ->orderBy('start_time DESC');
        $featured = $featured->asArray()->one();
        //Tools::log($featured,'xxx.log');
        if ($featured && isset($featured['content'])) {
            $this->_configData = json_decode($featured['content'], true);
        } else {
            $this->_configData = [];
        }
        //Tools::log($this->_configData,'xxx.log');
    }

    /**
     * 公告模块
     */
    protected function getTagBlocks()
    {
        $tag_blocks = isset($this->_configData['tag_blocks']) ? $this->_configData['tag_blocks'] : [];
        //ToolsAbstract::log($tag_blocks,'config.log');
        $data = [];
        foreach ($tag_blocks as $tag) {
            $data [] = array(
                'url' => isset($tag['url']) && $tag['url'] ? $tag['url'] : '',
                'text' => isset($tag['text']) && $tag['text'] ? $tag['text'] : '',
                'icon' => isset($tag['icon']) && $tag['icon'] ? $tag['icon'] : '',
                'sort' => isset($tag['sort']) ? $tag['sort'] : MerchantResourceAbstract::HOME_TAG_BLOCK_DEFAULT_SORT,//sort可能为0
            );
        }

        $this->_data['tag_blocks'] = $data;
    }

    /**
     * getQuickEntryBlock
     * Author ryan
     * 快捷入口
     */
    protected function getQuickEntryBlocks()
    {
        $quickEntryBlocks = isset($this->_configData['quick_entry_blocks']) ? $this->_configData['quick_entry_blocks'] : '';
        if (!$quickEntryBlocks) {
            return;
        }

        $this->_data['quick_entry_module'] = [];
        foreach ($quickEntryBlocks as $quickEntryBlock) {
            $quickEntryArray = isset($quickEntryBlock['quick_entry']) ? $quickEntryBlock['quick_entry'] : [];
            $quickEntryBlocksImage = !empty($quickEntryBlock['background_img']) ? $quickEntryBlock['background_img'] : '';

            if (!$quickEntryArray) {
                continue;
            }

            $quick_entry_block = [];
            $quickEntries = [];
            $color = !empty($quickEntryBlock['color']) ? $quickEntryBlock['color'] : self::QUICK_ENTRY_TITLE_COLOR;
            //去掉#
            $color = str_replace('#','',$color);
            foreach ($quickEntryArray as $quickEntry) {
                if(empty($quickEntry['src'])) continue;

                //根据版本选用链接
                if (isset($quickEntry['href'])) {
                    if (!empty($quickEntry['href_backup']) && !empty($quickEntry['compare_type']) && !empty($quickEntry['version'])) {
                        if (Tools::compareVersion($this->_appVersion, $quickEntry['version'], $quickEntry['compare_type'])) {
                            $quickEntry['href'] = $quickEntry['href_backup'];
                        }
                    }
                } else {
                    $quickEntry['href'] = '';
                }
                unset($quickEntry['href_backup'],$quickEntry['compare_type'],$quickEntry['version']);

                $quickEntry['href'] = isset($quickEntry['href']) ? $quickEntry['href'] : '';
                $quickEntry['title'] = isset($quickEntry['title']) ? $quickEntry['title'] : '';
                $quickEntry['color'] = $color;
                $quickEntry['tag_param'] = isset($quickEntry['tag_param']) ? $quickEntry['tag_param'] : '';

                $quickEntries[] = array_filter($quickEntry);
            }
            $quick_entry_block['quick_entry_blocks'] = $quickEntries;

            // remark
            if (isset($quickEntryBlock['remark'])) {
                $quick_entry_block['remark'] = $quickEntryBlock['remark'];
            }
            $quick_entry_block['sort'] = isset($quickEntryBlock['sort']) ? $quickEntryBlock['sort'] : MerchantResourceAbstract::HOME_ENTRY_BLOCK_DEFAULT_SORT;

            //首页快捷入口背景图
            if ($quickEntryBlocksImage) {
                $height = Tools::getImageHeightByUrl($quickEntryBlocksImage);
                $quick_entry_block['background_img']['height'] = $height;
                $quick_entry_block['background_img']['src'] = $quickEntryBlocksImage;
            }
            $this->_data['quick_entry_blocks'] [] = $quick_entry_block;
        }
    }

    protected function getProductBlocks()
    {
        $product_blocks = isset($this->_configData['product_blocks']) ? $this->_configData['product_blocks'] : '';
        if (!$product_blocks) {
            return;
        }

        $this->_data['product_blocks'] = [];
        //ToolsAbstract::log($product_blocks,'config.log');
        foreach ($product_blocks as $product_block) {
            if (!$this->timeRangeValidate($product_block)) {
                //ToolsAbstract::log('out of time==========','config.log');
                //ToolsAbstract::log($product_block,'config.log');
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
            //ToolsAbstract::log($productIds,'config.log');
            if (!$productIds || count($productIds) == 0) {
                continue;
            }
            $limit = isset($product_block['size']) && $product_block['size'] <= self::PRODUCT_BLOCK_MAX_SIZE ? $product_block['size'] : self::PRODUCT_BLOCK_MAX_SIZE;
            $products = (new ProductHelper())->initWithProductIds($productIds, $this->_cityId, $this->_wholesalerIds)
                ->getTags()
                ->getData();
            //ToolsAbstract::log($products,'config.log');

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
        //ToolsAbstract::log($this->_data['product_blocks'],'config.log');
    }

    protected function getTopicBlocks()
    {
        $topicBlocks = isset($this->_configData['topic_blocks']) ? $this->_configData['topic_blocks'] : '';
        if (!$topicBlocks) return;

        $topics = [];
        foreach ($topicBlocks as $topicBlock) {
            if (!$topicBlock || !isset($topicBlock['banner']) || count($topicBlock['banner']) == 0) {
                continue;
            }

            if (!$this->timeRangeValidate($topicBlock)) {
                continue;
            }

            if (isset($topicBlock['area_ids']) && !empty($topicBlock['area_ids']) && !in_array($this->_areaId, $topicBlock['area_ids'])) {
                continue;
            }

            //如果设置了customer_group_ids，专题分人群展示
            if (!empty($topicBlock['customer_group_ids'])) {
                if (empty(array_intersect($this->_customerBelongGroup, $topicBlock['customer_group_ids']))) {
                    //如果用户所属分群和专题中设置的分群有交集，则显示该专题
                    continue;
                }
            }

            $topicBanners = [
                'banner' => []
            ];
            $banners = $topicBlock['banner'];
            foreach ($banners as $banner) {
                if(empty($banner['src'])) continue;

                $banner_show = [];
                $img = $banner['src'];
                $height = Tools::getImageHeightByUrl($img);
                $banner_show['height'] = $height;
                $banner_show['src'] = $img;
                //根据版本选用链接
                if (isset($banner['href'])) {
                    if (!empty($banner['href_backup']) && !empty($banner['compare_type']) && !empty($banner['version'])) {
                        if (Tools::compareVersion($this->_appVersion, $banner['version'], $banner['compare_type'])) {
                            $banner['href'] = $banner['href_backup'];
                        }
                    }
                } else {
                    $banner['href'] = '';
                }
                $banner_show['href'] = $banner['href'];
                $banner_show['tag_param'] = isset($banner['tag_param']) ? $banner['tag_param'] : '';

                $topicBanners['banner'][] = $banner_show;
            }

            $topicBanners['topic_type'] = isset($topicBlock['topic_type']) ? $topicBlock['topic_type'] : 1;
            //左右滑动和平铺两种展示模式，要设置是否查看更多和查看更多的链接
            if (in_array($topicBanners['topic_type'], [5, 6]) && isset($topicBlock['check_more']) && $topicBlock['check_more']) {
                $topicBanners['url'] = isset($topicBlock['url']) ? $topicBlock['url'] : '';
                if ($topicBanners['topic_type'] == 5) {
                    $topicBanners['check_more'] = self::CHECK_MORE_IMAGE_SLIDE;
                } elseif ($topicBanners['topic_type'] == 6) {
                    $topicBanners['check_more'] = self::CHECK_MORE_IMAGE_PAVE;
                }
            }

            $title_img = isset($topicBlock['topic_block_title_img']) ? $topicBlock['topic_block_title_img'] : '';
            if($title_img){
                $height = Tools::getImageHeightByUrl($title_img);
                $topicBanners['title_img']['src'] = $title_img;
                $topicBanners['title_img']['height'] = $height;
            }

            $topicBanners['sort'] = isset($topicBlock['sort']) ? $topicBlock['sort'] : MerchantResourceAbstract::HOME_TOPIC_BLOCK_DEFAULT_SORT;
            if (isset($topicBlock['tag_param'])) {
                $topicBanners['tag_param'] = $topicBlock['tag_param'];
            }
            // remark
            if (isset($topicBlock['remark'])) {
                $topicBanners['remark'] = $topicBlock['remark'];
            }
            if (count($topicBanners['banner']) > 0) {
                $topics[] = $topicBanners;
            }
        }
        $this->_data['topic_blocks'] = $topics;
    }

    protected function getBrandBlocks()
    {
        $original_data = isset($this->_configData['brand_blocks']) ? $this->_configData['brand_blocks'] : [];
        if (!is_array($original_data) || !$original_data) {
            return;
        }

        $brand_ids = [];
        $brand_blocks = [];
        foreach ($original_data as $block) {
            if (!$this->timeRangeValidate($block)) {
                continue;
            }

            $brands = isset($block['brands']) ? $block['brands'] : [];
            if (!$brands) continue;

            $brand_block = array(
                'title' => isset($block['title']) && $block['title'] ? $block['title'] : '',
                'remark' => isset($block['remark']) && $block['remark'] ? $block['remark'] : '',
                'sort' => isset($block['sort']) ? $block['sort'] : MerchantResourceAbstract::HOME_BRAND_BLOCK_DEFAULT_SORT,
                'brands' => []
            );

            //标题图片
            if(!empty($block['title_img'])){
                $height = Tools::getImageHeightByUrl($block['title_img']);
                $brand_block['title_img']['src'] = $block['title_img'];
                $brand_block['title_img']['height'] = $height;
            }

            foreach ($brands as $brand) {
                if (!isset($brand['brand_id'])) continue;

                $item = array(
                    'brand_id' => $brand['brand_id'],
                );
                if (isset($brand['url']) && $brand['url']) {
                    $item['url'] = $brand['url'];
                }

                $brand_block['brands'] [] = $item;
                $brand_ids [] = $brand['brand_id'];
            }

            $brand_blocks [] = $brand_block;
        }

        if (empty($brand_blocks) || empty($brand_ids)) {
            return $this;
        }

        $brand_map = [];
        $brand_array = Brand::find()->where(['entity_id' => $brand_ids])->asArray()->all();
        if ($brand_array) {
            foreach ($brand_array as $brand) {
                $brand_map[$brand['entity_id']] = $brand;
            }
        }

        foreach ($brand_blocks as $k => $block) {
            foreach ($block['brands'] as $j => $brand) {
                if (isset($brand_map[$brand['brand_id']])) {
                    $brand_blocks[$k]['brands'][$j]['name'] = $brand_map[$brand['brand_id']]['name'];
                    $brand_blocks[$k]['brands'][$j]['icon'] = $brand_map[$brand['brand_id']]['icon'];
                }
            }
        }

        $this->_data['brand_blocks'] = $brand_blocks;
    }

    /**
     * Author ryan
     * 供应商模块
     * @return $this
     */
    protected function getStoreBlocks()
    {
        $storeBlocks = isset($this->_configData['store_blocks']) ? $this->_configData['store_blocks'] : '';
        if (!$storeBlocks) return;

        //现在只会配一个供应商模块
        $storeBlock = $storeBlocks[0];

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
            $store_block_title_img = self::HOME_STORE_BLOCK_BANNER;
        }

        $sort = isset($storeBlock['sort']) ? $storeBlock['sort'] : MerchantResourceAbstract::HOME_STORE_BLOCK_DEFAULT_SORT;

        $all_merchant_store_category = LeMerchantStoreCategory::find()->orderBy('sort asc')->all();
        $store_ids = [];
        $store_list = [];

        //每个分类取2个，不用配置中的数字
        /** @var LeMerchantStoreCategory $one_merchant_store_category */
        foreach ($all_merchant_store_category as $one_merchant_store_category) {
            $merchant_store_category_id = $one_merchant_store_category->entity_id;

            //推荐供应商，供应商权重排序,并且去重，前面分类出现过的店铺过滤掉
            $recommendWholesalerIds = MerchantResourceAbstract::getWholesalerIdsByMerchantCategory($this->_areaId, MerchantResourceAbstract::HOME_STORE_BLOCK_DEFAULT_COUNT, $merchant_store_category_id, $store_ids);
            $store_ids = array_merge($store_ids, $recommendWholesalerIds);

            $wholesalers = MerchantResourceAbstract::getStoreDetailBrief($recommendWholesalerIds, $this->_areaId, '', true);
            foreach ($wholesalers as $k => $v) {
                $wholesalers[$k]['category_icon'] = $one_merchant_store_category->icon;
            }
            $store_list = array_merge($store_list, $wholesalers);
        }

        if (count($store_list)) {
            $store_block['store_list'] = $store_list;
            //全部店铺数量
            $store_block['store_count'] = count($this->_wholesalerIds);
            //排序
            if ($sort) {
                $store_block['sort'] = $sort;
            }
            //block上方图片
            if ($store_block_title_img) {
                $store_block['store_block_title_img']['src'] = $store_block_title_img;
                $store_block['store_block_title_img']['height'] = Tools::getImageHeightByUrl($store_block_title_img);
            }

            $this->_data['store_blocks'] = [$store_block];
        }
    }

    //秒杀模块
    protected function getSeckillBlocks()
    {
        $seckillBlocks = isset($this->_configData['seckill_blocks']) ? $this->_configData['seckill_blocks'] : '';
        if (!$seckillBlocks) return;

        $seckillBlock = $seckillBlocks[0];
        //ToolsAbstract::log($seckillBlock,'config.log');

        /** @var $activity SecKillActivity */
        if (!$activity = SecKillActivity::getCityActiveActivity($this->_cityId, SeckillHelper::IS_CACHE)) {
            return;
        }

        if (!SeckillHelper::checkAccess($activity, $this->_customerId, $this->_cityId, $this->_areaId)) {
            return;
        }

        /** @var $product SpecialProduct */
        $products = (new SeckillHelper($this->_customer))->getProducts($activity['entity_id']);
        if (!$products || !is_array($products[1])) {
            return;
        }

        $status = SecKillActivity::getStatusInfo($activity);
        $seckill_block = [
            'title' => '限时抢购',
            'subtitle' => $status == SecKillActivity::INT_STATUS_STARTED ? '距离结束' : '距离开始',
            'url' => 'lelaishop://page/seckill?actId=' . $activity['entity_id'],
            'left_time' => SecKillActivity::getLeftTime($activity, $status),
            'product' => current($products[1]),
            'sort' => isset($seckillBlock['sort']) ? $seckillBlock['sort'] : MerchantResourceAbstract::HOME_SECKILL_BLOCK_DEFAULT_SORT,
        ];

        $this->_data['seckill_blocks'] = [$seckill_block];
    }

    //热门推荐商品模块
    protected function getHotRecommendBlock($params = [])
    {
        //ToolsAbstract::log($this->_configData,'xxx.log');
        $hotRecommendBlock = isset($this->_configData['hot_recommend_block']) ? $this->_configData['hot_recommend_block'] : '';
        if (!$hotRecommendBlock) return;

        if (!$this->timeRangeValidate($hotRecommendBlock)) return;

        $titleImg = !empty($hotRecommendBlock['title_img']) ? $hotRecommendBlock['title_img'] : self::HOT_RECOMMEND_DEFAULT_TITLE_IMG;
        if ($titleImg) {
            $height = Tools::getImageHeightByUrl($titleImg);
            $blockData['title_img']['src'] = $titleImg;
            $blockData['title_img']['height'] = $height;
        }

        $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
        $pageSize = isset($params['page_size']) && $params['page_size'] > 0 ? $params['page_size'] : 30;

        list($totalCount, $rows) = $this->getBestSellingProduct($page, $pageSize);

        $pages = new Pagination(['totalCount' => $totalCount]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);
        $pagination = $pages;

        if ($pagination) {
            $blockData['pages'] = [
                'total_count' => $pagination->getTotalCount(),
                'page' => $pagination->getCurPage(),
                'last_page' => $pagination->getLastPageNumber(),
                'page_size' => $pagination->getPageSize(),
            ];
        }

        if (!$rows) return;

//        $productIds = array();
//        foreach ($rows as $row) {
//            $productIds [] = $row['product_id'];
//        }

        $products = (new ProductHelper())->initWithProductArray($rows, $this->_cityId, '388x388',$this->_wholesalerIds)
            ->getTags()
            ->getData();

        $blockData['product_list'] = $products;

        $this->_data['hot_recommend_block'] = $blockData;
    }

    protected function getBestSellingProduct($page, $pageSize)
    {
        $model = BestSellingProduct::find()->select('b.order_num,p.*')->alias('b');
        $model->where(['b.wholesaler_id' => $this->_wholesalerIds]);

        $productModel = new Products($this->_cityId);
        $model->leftJoin(['p' => 'lelai_booking_product_a.' . $productModel->tableName()], 'b.product_id = p.entity_id');
        $model->andWhere([
            'p.status' => 1,
            'p.state' => 2
        ]);
        $date = new Date();
        $now = $date->date("Y-m-d H:i:s");
        $model->andWhere(['<=','p.shelf_from_date',$now]);
        $model->andWhere(['>','p.shelf_to_date',$now]);

        //专区首页会配置
        ToolsAbstract::log($this->_configData['hot_recommend_block'],'topic.log');
        if (isset($this->_configData['hot_recommend_block']) && !empty($this->_configData['hot_recommend_block']['sales_type'])) {
            //ToolsAbstract::log($this->_configData['hot_recommend_block']['sales_type'],'topic.log');
            //ToolsAbstract::log('p.sales_type & '.(1 << $this->_configData['hot_recommend_block']['sales_type']).' > 0','topic.log');
            $model->andWhere('p.sales_type & '.(1 << $this->_configData['hot_recommend_block']['sales_type']).' > 0');
        }

        $model->orderBy('b.order_num desc');
        Tools::log($model->createCommand()->getRawSql(),'topic.log');

        $totalCount = $model->count();
        //产品要求，只有给最多30条产品，总数设置最大30，翻页只到第一页
        $totalCount = $totalCount > 30 ? 30 : $totalCount;

        $model->limit($pageSize)->offset($pageSize * ($page - 1));
        $rows = $model->asArray()->all();
        //Tools::log($rows,'topic.log');

        return [$totalCount, $rows];
    }

    //是否在有效时间范围内
    protected function timeRangeValidate($config)
    {
        $date = new Date();
        $now = $date->date();
        if (!empty($config['start_time']) && strtotime($now) < strtotime($config['start_time'])) {
            return false;
        }

        if (!empty($config['end_time']) && strtotime($now) > strtotime($config['end_time'])) {
            return false;
        }

        return true;
    }
}