<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/14
 * Time: 17:04
 */
namespace service\models;

use common\models\extend\LeMerchantStoreExtend;
use common\models\LeBanner;
use common\models\LeHomepageConf;
use common\models\Products;
use common\models\RegionArea;
use framework\components\Date;
use service\components\Redis;
use service\components\Tools;
use Yii;
use yii\db\Expression;

class HomePageConfig
{
    public static $_categoryIcons = array(
        2 => 'http://assets.lelai.com/images/booking/category/icon/binggangaodian.png',
        31 => 'http://assets.lelai.com/images/booking/category/icon/xiuxianlingshi.png',
        80 => 'http://assets.lelai.com/images/booking/category/icon/jiushuiyinliao.png',
        103 => 'http://assets.lelai.com/images/booking/category/icon/rupinchachong.png',
        127 => 'http://assets.lelai.com/images/booking/category/icon/fangbiansushi.png',
    );
    protected $_areaId;
    protected $_cityId;
    protected $_isRemote;
    protected $_platform;
    protected $_wholesalerIds;
    protected $_data;
    protected $_wholesalerNames;

    public function __construct($areaId, $cityId, $wholesalerIds, $isRemote = false)
    {
        $this->_areaId = $areaId;
        $this->_cityId = $cityId;
        $this->_isRemote = $isRemote;
        //APP与PC的首页返回区分
        if ($this->_isRemote) {
            $this->_platform = 'app';
        } else {
            $this->_platform = 'pc';
        }
        $this->_wholesalerIds = $wholesalerIds;
        return $this;
    }

    public function toArray()
    {
        $key = 'slim_merchant_home_page';
        //APP与PC的首页返回区分
        if ($this->getRedisCache()->hExists($key, $this->_platform . $this->_areaId)) {
            $this->_data = unserialize($this->getRedisCache()->hGet($key, $this->_platform . $this->_areaId));
        } else {
            $this->_data = $this->parseJson();
            if($this->_platform=='app'){
                $this->_data['banner'] = $this->getHomeBanner();
            }
            if (isset($this->_data['discount_block'])) {
                $this->processDiscountBlock();
            }
            unset($this->_data['new_arrival_block']);
            if (isset($this->_data['new_arrival_block'])) {
                $this->processNewArrivalBlock();
            }
            if (isset($this->_data['category'])) {
                $this->processCategories();
            }
            $this->processStore();
            if ($this->_isRemote) {
                unset($this->_data['right_ad']);
                //unset($this->_data['discount_block']);
                unset($this->_data['new_arrival_block']['title_bg']);
            }
            //APP与PC的首页返回区分
            $this->getRedisCache()->hSet($key, $this->_platform . $this->_areaId, serialize($this->_data));
            $this->getRedisCache()->expire($key, 3600); //1小时缓存过期
        }

        return $this->_data;
    }

    /**
     * @return \framework\redis\Cache
     */
    protected function getRedisCache()
    {
        return Yii::$app->redisCache;
    }

    /**
     * @return mixed
     */
    protected function parseJson()
    {
        $featured = LeHomepageConf::find()->where(['city' => $this->_cityId])
            ->andWhere(['like','version','1'])->asArray()->one();
        $json = $featured['content'];
        return json_decode($json, true);
    }

    public function getHomeBanner()
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
            ->andWhere(['<=','start_date',$now])
            ->andWhere(['>=','end_date',$now])
            ->orderBy('sort')
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
                    'src' => 'http://assets.lelai.com/images/booking/home/banner/1.jpg',
                ]
            ];
        }
        return $banner;
    }

    protected function processDiscountBlock()
    {
        $blocks = [$this->_data['discount_block']];
        $_blocks = [];
        foreach ($blocks as $block) {
            $products = $block['products'];
            $size = $block['size'];
            //$left = $size - count($products);
            $left = 0;// 改为不自动填充 2016年04月21日16:57:37
            $productModel = new Products($this->_cityId);
            $_ruleProducts = [];
            $_products = [];
            if (count($products) > 0) {
                $_products = $productModel::find()
                    ->where(['entity_id' => $products, 'wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->andWhere(['>', 'price', 0])
                    ->andWhere(['>', 'special_price', 0])
                    ->limit($size)
                    ->asArray()->all();
            }
            if ($left > 0) {
                $_ruleProducts = $productModel::find()
                    ->addSelect('*')
                    ->addSelect("(price-special_price) as discount")
                    ->where(['wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->andWhere(['>', 'price', 0])
                    ->andWhere(['>', 'special_price', 0])
                    ->orderBy('discount desc')
                    ->limit($left)->asArray()->all();
            }
            $mrgProducts = array_merge($_products, $_ruleProducts);
            $block['products'] = [];
            foreach ($mrgProducts as $mrgProduct) {
                $_mrgProduct = Products::convertArray($mrgProduct);
                $_mrgProduct['wholesaler_name'] = $this->getWholesalerName($mrgProduct['wholesaler_id']);
                $_mrgProduct['wholesaler_url'] = $this->getWholesalerUrl($mrgProduct['wholesaler_id']);
                $block['products'][] = $_mrgProduct;
            }
            unset($block['rule'], $block['size'], $block['title_bg'], $block['images']);
            $_blocks = $block;
        }

        $this->_data['discount_block'] = $_blocks;
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

    public function getWholesalerUrl($wholesalerId)
    {
        if ($this->_platform == 'app') {
            return sprintf('lelaishop://shop/info?%s', http_build_query(array('wid' => $wholesalerId)));
        } else {
            return sprintf('wholesaler/index/index?sid=%s', $wholesalerId);
        }
    }

    protected function processNewArrivalBlock()
    {
        $blocks = [$this->_data['new_arrival_block']];
        $_blocks = [];
        foreach ($blocks as $block) {
            $products = $block['products'];
            $size = $block['size'];
            $left = $size - count($products);
            $left = 0;// 改为不自动填充 2016年04月21日16:57:37
            $productModel = new Products($this->_cityId);
            $_ruleProducts = [];
            $_products = [];
            if (count($products) > 0) {
                $_products = $productModel::find()
                    ->where(['entity_id' => $products, 'wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->andWhere(['>', 'price', 0])
                    ->orderBy('shelf_time,updated_at desc')
                    ->limit($size)->asArray()->all();
            }
            if ($left > 0) {
                $_ruleProducts = $productModel::find()
                    ->where(['wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->andWhere(['>', 'price', 0])
                    ->orderBy('shelf_time,updated_at desc')
                    ->limit($left)
                    ->asArray()->all();
            }
            $mrgProducts = array_merge($_products, $_ruleProducts);
            $block['products'] = [];
            foreach ($mrgProducts as $mrgProduct) {
                $_mrgProduct = Products::convertArray($mrgProduct);
                $_mrgProduct['wholesaler_name'] = $this->getWholesalerName($mrgProduct['wholesaler_id']);
                $_mrgProduct['wholesaler_url'] = $this->getWholesalerUrl($mrgProduct['wholesaler_id']);
                $block['products'][] = $_mrgProduct;
            }
            unset($block['rule'], $block['size'], $block['title_bg']);
            $_blocks = $block;
        }
        $this->_data['new_arrival_block'] = $_blocks;
    }

    protected function processCategories()
    {
        $categories = $this->_data['category'];
        $_categories = [];
        $tree = Tools::proCate();
        $productModel = new Products($this->_cityId);
        foreach ($categories as $category) {
            $category['category_id'] = $category['id'];
            $products = $category['products'];
            $size = $category['size'];
            $left = $size - count($products);
            $_ruleProducts = [];
            $_products = [];
            if (count($products) > 0) {
                $_products = $productModel::find()
                    ->where(['entity_id' => $products, 'first_category_id' => $category['category_id'], 'wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->andWhere(['>', 'price', 0])
                    ->orderBy('sort_weights desc')
                    ->limit($size)->asArray()->all();
            }
            if ($left > 0) {
                $_ruleProducts = $productModel::find()
                    ->where(['first_category_id' => $category['id'], 'wholesaler_id' => $this->_wholesalerIds])
                    ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                    ->orderBy('sort_weights desc')
                    ->limit($left)
                    ->asArray()->all();
            }
            $mrgProducts = array_merge($_products, $_ruleProducts);
            $_block = [];
            foreach ($mrgProducts as $mrgProduct) {
                $_mrgProduct = Products::convertArray($mrgProduct);
                $_mrgProduct['wholesaler_name'] = $this->getWholesalerName($mrgProduct['wholesaler_id']);
                $_mrgProduct['wholesaler_url'] = $this->getWholesalerUrl($mrgProduct['wholesaler_id']);
                $_block[] = $_mrgProduct;
            }


            $category['products'] = $_block;
            $category['icon'] = '';
            $category['text_color'] = '000000';
            foreach ($tree as $_category) {
                if ($_category['id'] == $category['category_id']) {
                    $category['name'] = $_category['name'];
                    $category['child_category'] = $_category['child_category'];
                }
            }
            if (isset(self::$_categoryIcons[$category['category_id']])) {
                $category['icon'] = self::$_categoryIcons[$category['category_id']];
            }
            if ($this->_isRemote) {
                unset($category['right_images'], $category['left_images'], $category['rule'], $category['size'], $category['id']);
            } else {
                unset($category['rule'], $category['size'], $category['id']);
            }

            $_categories[] = $category;
        }
        $this->_data['category'] = $_categories;
    }

    protected function processStore()
    {

        $merchantModel = new LeMerchantStoreExtend();
        // 是否按照配置的商家查找
        if (isset($this->_data['store']) && is_array($this->_data['store']) && count($this->_data['store'])) {
            $wholesalerIds = $this->_data['store'];
            $wholesalerIdsStr = join(',', $wholesalerIds);
            $merchantList = $merchantModel->find()->where(['like', 'area_id', '|' . $this->_areaId . '|'])
                ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
                ->andWhere(['entity_id' => $wholesalerIds])
                ->addOrderBy([new Expression("find_in_set(`entity_id`, '{$wholesalerIdsStr}')")])
                ->all();
        }else{
            $merchantList = $merchantModel->find()->where(['like', 'area_id', '|' . $this->_areaId . '|'])
                ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
                ->all();
        }
        $stores = array();
        if ($merchantList) {
            foreach ($merchantList as $item) {
                $merchantInfo = $item->getAttributes();
                $store = [
                    'wholesaler_id' => $merchantInfo['entity_id'],
                    'wholesaler_name' => $merchantInfo['store_name'],
                    'image' => explode(';', $merchantInfo['shop_images']),
                    'phone' => [$merchantInfo['customer_service_phone']],
                    'address' => $merchantInfo['store_address'],
                    //'description'			=>	json_encode(explode(';', $merchantInfo['shop_images'])),
                    'area' => RegionArea::regionNames(array_filter(explode('|',$merchantInfo['area_id']))),
                    //'delivery_time' => '3小时接单，'.($merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : ''),
                    'delivery_time' => $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '',
                    'min_trade_amount' => $merchantInfo['min_trade_amount'],
                    'business_license_img' => $merchantInfo['business_license_img'],
                ];
                $stores[] = array_filter($store);
            }
        }
        $this->_data['store'] = $stores;
    }
}