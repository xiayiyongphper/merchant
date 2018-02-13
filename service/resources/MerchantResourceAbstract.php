<?php

namespace service\resources;

use common\models\extend\LeMerchantExtend;
use common\models\extend\LeMerchantStoreExtend;
use common\models\LeBanner;
use common\models\LeMerchant;
use common\models\LeMerchantDelivery;
use common\models\LeMerchantStore;
use common\models\ProductGroupDetail;
use common\models\Products;
use common\models\RegionArea;
use common\models\Tags;
use framework\components\Date;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\common\Merchant;
use service\message\common\Rule;
use service\message\common\Store;
use service\message\customer\CustomerResponse;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\models\CoreConfigData;
use service\resources\merchant\v1\getStoresByAreaIds;
use service\resources\merchant\v1\merchantAuthentication;
use yii\db\ActiveQuery;
use framework\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use service\models\ProductHelper;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:10
 */
abstract class MerchantResourceAbstract extends ResourceAbstract
{
    //首页位置
    const HOME_ENTRY_BLOCK_DEFAULT_SORT = 1;
    const HOME_TAG_BLOCK_DEFAULT_SORT = 2;
    const HOME_SECKILL_BLOCK_DEFAULT_SORT = 3;
    const HOME_PRODUCT_BLOCK_DEFAULT_SORT = 4;
    const HOME_TOPIC_BLOCK_DEFAULT_SORT = 5;
    const HOME_STORE_BLOCK_DEFAULT_SORT = 6;
    const HOME_BRAND_BLOCK_DEFAULT_SORT = 7;
    //展示方式
    const HOME_PRODUCT_BLOCK_SHOW_TYPE_1 = 1;//多行排列
    const HOME_PRODUCT_BLOCK_SHOW_TYPE_2 = 2;//左右滑动

    const HOME_STORE_PRODUCT_NUM = 3;//供应商展示的商品数量

    const RULE_COUPON = 2;  //只能填优惠码领取或主动发放
    const RULE_PROMOTION = 1;
    const RULE_COUPON_SHOW = 5; //展示在前端，只可页面领取或主动发放
    const RULE_COUPON_SEND = 4; //只可主动发放

    const RULE_TAG_URL_PREFIX = "lelaishop://topicV3/list?rid=";

    //首页每个分类展示的供应商数量
    const HOME_STORE_BLOCK_DEFAULT_COUNT = 2;
    //全场最惠  掩码
    const PRODUCT_FAVOURABLE_TAG = 1;

    const COMMON_CONTRACTOR = '普通业务员';

    protected $_wholesaler = [];

    protected $ids = [484, 485, 486, 487, 488, 491, 490, 489, 492, 493, 494];
    protected static $bannerUrl = 'http://assets.lelai.com/assets/img/topic/default.jpg';
    protected static $homeBannerDefault = 'http://assets.lelai.com/images/files/merchant/20170306/source/0_20170306112251834257052.jpg?width=640&height=340';

    public static $wholesalerCoupon = false;

    /**
     * getWholesalerIdsByAreaId
     * Author Jason Y. wang
     *
     * @param $areaId
     * @return array
     */
    public static function getWholesalerIdsByAreaId($areaId, $orderBy = 'sort desc')
    {
        //获取所有区域内店铺列表ID
        $merchantModel = new LeMerchantStoreExtend();
        $query = $merchantModel::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0]);
        $query->orderBy($orderBy);
        $wholesalerIds = $query->column();
        return $wholesalerIds;
    }

    public static function getWhiteListWholesalerIds($areaId, $num = 3, $merchant_category = 0)
    {
        //获取所有区域内店铺列表ID
        $merchantModel = new LeMerchantStoreExtend();
        $wholesalerModels = $merchantModel::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['between', 'sort', 1000, 2000])
            ->orderBy('sort desc')
            ->limit($num);
        if ($merchant_category > 0) {
            $wholesalerModels = $wholesalerModels->andWhere(['like', 'store_category', '|' . $merchant_category . '|']);
        }
        $wholesalerIds = $wholesalerModels->column();
        return $wholesalerIds;
    }

    public static function getWholesalerIdsByMerchantCategory($areaId, $num = 3, $merchant_category = 0, $filtered_store = null)
    {
        //获取所有区域内店铺列表ID
        $merchantModel = new LeMerchantStoreExtend();
        $wholesalerModels = $merchantModel::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0])
            ->orderBy('sort_score desc')
            ->limit($num);
        if ($merchant_category > 0) {
            $wholesalerModels = $wholesalerModels->andWhere(['like', 'store_category', '|' . $merchant_category . '|']);
        }
        if ($filtered_store) {
            $wholesalerModels->andWhere(['not in', 'entity_id', $filtered_store]);
        }
        $wholesalerIds = $wholesalerModels->column();
        return $wholesalerIds;
    }

    /**
     * 拉取所有供货商id集合
     * @param int $categoryId
     * @param int $areaId
     * @return $this|array
     */
    public static function getAllWholesalerIds($categoryId = 0, $areaId = 0)
    {
        $merchantModel = new LeMerchantStoreExtend();
        $wholesalerIds = $merchantModel::find()->where(['status' => LeMerchantStoreExtend::STATUS_NORMAL]);
        if ($areaId > 0) {
            $wholesalerIds->andWhere(['like', 'area_id', '|' . $areaId . '|']);
        }
        if ($categoryId > 0) {
            $wholesalerIds->andWhere(['like', 'store_category', '|' . $categoryId . '|']);
        }
        $wholesalerIds->andWhere(['>=', 'sort', 0])->orderBy('sort_score desc, sort desc');
        $wholesalerIds = $wholesalerIds->column();
        //Tools::log('-----------$wholesalerIds:' . print_r($wholesalerIds, true), 'debug.txt');
        return $wholesalerIds;
    }

    /**
     * @param $areaId
     * @return array
     */
    public static function getCompensationWholesalerCountByAreaId($areaId)
    {
        //获取所有区域内店铺列表ID
        $merchantModel = new LeMerchantStoreExtend();
        $wholesalerCount = $merchantModel::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0])
            ->andWhere(['compensation_service' => 1])
            ->count();
        return $wholesalerCount;
    }

    /**
     * @param \framework\protocolbuffers\Message $data
     *
     * @return Merchant
     * @throws \Exception
     */
    protected function _initMerchant($data)
    {
        $object = new merchantAuthentication();
        $request = $object->request();
        $request->setWholesalerId($data->getWholesalerId());
        $request->setAuthToken($data->getAuthToken());
        $response = $object->run($request->serializeToString());
        return $response;
    }

    /**
     * 根据$storeModel返回商家详情数组
     *
     * @param \yii\base\Model|array $storeModel
     * @param int $areaId
     * @return
     * @throws \Exception
     */
    public static function getStoreDetail($storeModel, $areaId = 0)
    {
        // 是数组就不用转了
        $merchantInfo = null;
        if (is_array($storeModel)) {
            $merchantInfo = $storeModel;
        } elseif (method_exists($storeModel, 'getAttributes')) {
            $merchantInfo = $storeModel->getAttributes();
        } else {
            Exception::storeNotExisted();
        }
        //Tools::log($merchantInfo,'wangyang.log');
        /** @var LeMerchantDelivery $merchant_area_setting */
        $merchant_area_setting = LeMerchantDelivery::find()->where(['store_id' => $merchantInfo['entity_id']])
            ->andWhere(['delivery_region' => $areaId])->one();
        if ($merchant_area_setting) {
            $promised_delivery_text = $merchant_area_setting->note;
            $min_trade_amount = $merchant_area_setting->delivery_lowest_money;
        } else {
            $min_trade_amount = $merchantInfo['min_trade_amount'];
            $promised_delivery_text = $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '';
        }

        list($tags, $marketing_tags, $category_tags) = self::getMerchantTags($merchantInfo);

        $rules = Tools::getWholesalerPromotions($merchantInfo['entity_id']);
        /** @var \service\message\common\PromotionRule $rule */
        //去掉优惠券，只保留无优惠券活动
        foreach ($rules as $k => $rule) {
            if ($rule->getCouponType() != self::RULE_PROMOTION) {
                unset($rules[$k]);
            }
        }
        //Tools::log("rules==========",'hl.log');
        //Tools::log($rules,'hl.log');
        $promotion_message = self::getWholesalerPromotionMessage($rules, $merchantInfo['entity_id']);
        $promotion_message_in_tag = self::getWholesalerPromotionMessageInTag($rules, $merchantInfo['entity_id'], $merchantInfo['store_name']);

        //Tools::log($promotion_message,'wangyang.log');
        $data = [
            'wholesaler_id' => $merchantInfo['entity_id'],
            'wholesaler_name' => $merchantInfo['short_name'] ?: $merchantInfo['store_name'],
            'icon' => $merchantInfo['icon'],
            'logo' => $merchantInfo['logo'],
            'image' => explode(';', $merchantInfo['shop_images']),
            'phone' => [$merchantInfo['customer_service_phone']],
            'address' => $merchantInfo['store_address'],
            //'description'				=>	json_encode(explode(';', $merchantInfo['shop_images'])),
            'city' => $merchantInfo['city'],
            'area' => RegionArea::regionNames(array_filter(explode('|', $merchantInfo['area_id']))),
            //'delivery_time'        		=> '3小时内接单,'.($merchantInfo['promised_delivery_time']?$merchantInfo['promised_delivery_time'].'小时送达':''),
            'delivery_time' => $promised_delivery_text,

            'min_trade_amount' => round($min_trade_amount), //最低起送价取整
            'business_license_img' => $merchantInfo['business_license_img'],
            'business_license_code' => $merchantInfo['business_license_code'],
            'tax_registration_certificate_img' => $merchantInfo['tax_registration_certificate_img'],
            'organization_code_certificate_img' => $merchantInfo['organization_code_certificate_img'],
            'operate_time' => $merchantInfo['operate_time'],
            'customer_service_phone' => $merchantInfo['customer_service_phone'],
            'business_category' => $merchantInfo['business_category'],
            'rebates' => $merchantInfo['rebates'],
            'rebates_text' => $merchantInfo['rebates'] ? '全场返现' . $merchantInfo['rebates'] . '%' : '',
            'delivery_text' => $promised_delivery_text,
            'tags' => $tags,
            'marketing_tags' => array_filter($marketing_tags),
            'category_tags' => array_filter($category_tags),
            'compensation_service' => $merchantInfo['compensation_service'],
            'promotion_message' => $promotion_message,
            'promotion_message_in_tag' => $promotion_message_in_tag,
            'status' => $merchantInfo['status'],
            'merchant_type_id' => $merchantInfo['store_type'],
            'short_name' => isset($merchantInfo['short_name']) ? $merchantInfo['short_name'] : '',
        ];

        /*$data['promotion_rule_list'] = [];
        foreach ($rules as $rule){
            $rule = $rule->toArray();
            if($rule['type'] == 3){
                $rule['rule_detail_list'] = [
                    ['title' => '活动名称','content' => $rule['name']],
                ];

                $promotion_range = $merchantInfo['store_name']."全部商品";
                if($rule['subsidies_lelai_included'] == 0){
                    $promotion_range .= "<br/>特价商品不参与该活动";
                }
                $rule['rule_detail_list'] []= ['title' => '活动范围','content' => $promotion_range];

                $rule['rule_detail_list'] []= ['title' => '活动时间','content' => $rule['from_date']."至<br/>".$rule['to_date']];
                $rule['rule_detail_list'] []= ['title' => '参与次数','content' => '每人限制'.$rule['rule_uses_limit'].'次'];
                $rule['rule_detail_list'] []= ['title' => '活动详情','content' => isset($rule['rule_detail']) ? $rule['rule_detail'] : ''];
            }

            $data['promotion_rule_list'] []= $rule;
        }*/

        // 店铺banner逻辑
        $date = new Date();
        $now = $date->date();
        $banners = LeBanner::find()
            ->where([
                'le_banner.position' => 'app_store_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ])
            ->andWhere(['like', 'wholesaler_id', '|' . $merchantInfo['entity_id'] . '|'])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc')
            ->asArray()->all();
        if (count($banners) > 0) {
            foreach ($banners as $item) {
                //ios显示高度不正确，先去掉
                $height = Tools::getImageHeightByUrl($item['image']);
                $data['banner'][] = [
                    'src' => $item['image'],
                    'href' => $item['url'],
                    //'height' => $height
                ];
            }
        } else {
            // 默认的
            $data['banner'][] = [
                'href' => '',
                'src' => self::$bannerUrl,
            ];
        }
        //Tools::log($data,'wangyang.log');
        return $data;
    }

    /**
     * 根据$storeModel返回商家详情数组
     *
     * @param $wholesalerIds
     * @param $areaId
     * @return array
     */
    public static function getStoreDetailPro($wholesalerIds, $areaId)
    {
        $data = [];
        if (!is_array($wholesalerIds) || count($wholesalerIds) == 0) {
            return $data;
        }
        //查出所有供应商
        $order = implode(',', $wholesalerIds);
        $order_by = [new Expression("FIELD (`entity_id`," . $order . ")")];
        $wholesalers = LeMerchantStore::find()->where(['in', 'entity_id', $wholesalerIds])
            ->orderBy($order_by)->asArray()->all();

        //查出所有供应商
        $deliveryArray = LeMerchantDelivery::find()->where(['in', 'store_id', $wholesalerIds])
            ->andWhere(['delivery_region' => $areaId])->asArray()->all();
        $deliveryArray = Tools::conversionKeyArray($deliveryArray, 'store_id');
        //返回城市所有区域
        /** @var RegionArea $regionArea */
        $regionArea = RegionArea::find()->where(['entity_id' => $areaId])->one();
        // 传错误的areaId则直接返回空
        if (!$regionArea) {
            return [];
        }
        $region_areas = RegionArea::regionNamesArray(['city' => $regionArea->city]);
        // 店铺banner逻辑
        $banners = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_store_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app'])
            ->andWhere(['in', 'wholesaler_id', $wholesalerIds])->asArray()->all();
        $banners = Tools::assortmentArray($banners, 'wholesaler_id');
        //组织数据
        foreach ($wholesalers as $merchantInfo) {
            //Tools::log($merchantInfo,'wangyang.log');
            //配送区域送达时间说明
            $merchant_area_setting = isset($deliveryArray[$merchantInfo['entity_id']]) ? $deliveryArray[$merchantInfo['entity_id']] : null;
            if ($merchant_area_setting) {
                $promised_delivery_text = $merchant_area_setting['note'];
                $min_trade_amount = $merchant_area_setting['delivery_lowest_money'];
            } else {
                $min_trade_amount = $merchantInfo['min_trade_amount'];
                $promised_delivery_text = $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '';
            }
            //配送区域字符串
            $area_ids = array_filter(explode('|', $merchantInfo['area_id']));
            $area = Tools::array_values($region_areas, $area_ids);

            list($tags, $marketing_tags, $category_tags) = self::getMerchantTags($merchantInfo);


            $data[$merchantInfo['entity_id']] = [
                'wholesaler_id' => $merchantInfo['entity_id'],
                'wholesaler_name' => $merchantInfo['short_name'] ?: $merchantInfo['store_name'],
                'icon' => $merchantInfo['icon'],
                'logo' => $merchantInfo['logo'],
                'image' => explode(';', $merchantInfo['shop_images']),
                'phone' => [$merchantInfo['customer_service_phone']],
                'address' => $merchantInfo['store_address'],
                //'description'				=>	json_encode(explode(';', $merchantInfo['shop_images'])),
                'city' => $merchantInfo['city'],
                'area' => implode(',', $area),
                //'delivery_time'        		=> '3小时内接单,'.($merchantInfo['promised_delivery_time']?$merchantInfo['promised_delivery_time'].'小时送达':''),
                'delivery_time' => $promised_delivery_text,
                'min_trade_amount' => round($min_trade_amount), //最低起送价取整
                'business_license_img' => $merchantInfo['business_license_img'],
                'business_license_code' => $merchantInfo['business_license_code'],
                'tax_registration_certificate_img' => $merchantInfo['tax_registration_certificate_img'],
                'organization_code_certificate_img' => $merchantInfo['organization_code_certificate_img'],
                'operate_time' => $merchantInfo['operate_time'],
                'customer_service_phone' => $merchantInfo['customer_service_phone'],
                'business_category' => $merchantInfo['business_category'],
                'rebates' => $merchantInfo['rebates'],
                'rebates_text' => $merchantInfo['rebates'] ? '全场返现' . $merchantInfo['rebates'] . '%' : '',
                'delivery_text' => $promised_delivery_text,
                'tags' => $tags,
                'marketing_tags' => array_filter($marketing_tags),
                'category_tags' => array_filter($category_tags),
            ];

            if (isset($banners[$merchantInfo['entity_id']]) && count($banners[$merchantInfo['entity_id']]) > 0) {
                foreach ($banners[$merchantInfo['entity_id']] as $item) {
                    $data[$merchantInfo['entity_id']]['banner'][] = [
                        'src' => $item['image'],
                        'href' => $item['url'],
                    ];
                }
            } else {
                // 默认的
                $data[$merchantInfo['entity_id']]['banner'][] = [
                    'href' => '',
                    'src' => 'http://assets.lelai.com/assets/img/topic/default.jpg',
                ];
            }
        }
        return $data;
    }

    /**
     * getStoreDetailBrief
     * Author Jason Y. wang
     * 返回简单的店铺详情
     * @param $wholesalerIds
     * @param $areaId
     * @param $order
     * @return array
     */
    public static function getStoreDetailBrief($wholesalerIds, $areaId, $order = '', $need_products = false)
    {
        $data = [];
        if (!is_array($wholesalerIds) || count($wholesalerIds) == 0) {
            return $data;
        }
        if (empty($order)) {
            //按顺序查出所有供应商
            $order = implode(',', $wholesalerIds);
            $order_by = [new Expression("FIELD (`entity_id`," . $order . ")")];
        } else {
            $order_by = $order;
        }

        $wholesalers = LeMerchantStore::find()->select(['entity_id', 'store_name', 'customer_service_phone',
            'min_trade_amount', 'promised_delivery_time', 'rebates', 'city', 'marketing_tags', 'category_tags', 'store_category', 'short_name'])->where(['in', 'entity_id', $wholesalerIds])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->orderBy($order_by)->asArray()->all();
        //查出所有供应商配送说明
        $deliveryArray = LeMerchantDelivery::find()->where(['in', 'store_id', $wholesalerIds])
            ->andWhere(['delivery_region' => $areaId])->asArray()->all();
        $deliveryArray = Tools::conversionKeyArray($deliveryArray, 'store_id');
        //供应商促销信息
        $rules = Tools::getWholesalerPromotions(array_unique($wholesalerIds));
        //Tools::log($wholesalerIds, 'h6_rules.log');
        //Tools::log($rules, 'h6_rules.log');
        //组织数据
        $now = date("Y-m-d H:i:s");
        foreach ($wholesalers as $merchantInfo) {
            Tools::log($merchantInfo, 'wangyang.log');
            //配送区域送达时间说明
            $merchant_area_setting = isset($deliveryArray[$merchantInfo['entity_id']]) ? $deliveryArray[$merchantInfo['entity_id']] : null;
            if ($merchant_area_setting) {
                $promised_delivery_text = $merchant_area_setting['note'];
                $min_trade_amount = $merchant_area_setting['delivery_lowest_money'];
            } else {
                $min_trade_amount = $merchantInfo['min_trade_amount'];
                $promised_delivery_text = $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '';
            }

            $promotion_message_in_tag = self::getWholesalerPromotionMessageInTag($rules, $merchantInfo['entity_id']);

            list($tags, $marketing_tags, $category_tags) = self::getMerchantTags($merchantInfo);

            $coupon = [];
            if (self::$wholesalerCoupon) {
                $coupon['icon'] = Tags::$ICON_QUAN;
                $coupon['icon_text'] = '券';
            }

            $data[$merchantInfo['entity_id']] = [
                'wholesaler_id' => $merchantInfo['entity_id'],
                'wholesaler_name' => $merchantInfo['short_name'] ?: $merchantInfo['store_name'],
                'phone' => [$merchantInfo['customer_service_phone']],
                'city' => $merchantInfo['city'],
                'min_trade_amount' => round($min_trade_amount), //最低起送价取整
                'delivery_text' => $promised_delivery_text,
                'customer_service_phone' => $merchantInfo['customer_service_phone'],
                'rebates' => $merchantInfo['rebates'],
                'rebates_text' => $merchantInfo['rebates'] ? '全场返现' . $merchantInfo['rebates'] . '%' : '',
                'tags' => $tags,
                'marketing_tags' => $marketing_tags,
                'category_tags' => $category_tags,
                'store_category' => $merchantInfo['store_category'],
                'promotion_message_in_tag' => $promotion_message_in_tag,
                'coupon' => $coupon,
                'short_name' => $merchantInfo['short_name'],
            ];

            //供应商商品列表
            if ($need_products) {
                $product_ids = [];
                $products_num = self::HOME_STORE_PRODUCT_NUM;
                $model = new Products($merchantInfo['city']);
                $product_ids = $model->find()
                    ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                    ->andWhere(['state' => Products::STATE_APPROVED])
                    ->andWhere(['status' => Products::STATUS_ENABLED])
                    ->andWhere(['>', 'special_price', 0])
                    ->andWhere(['<', 'special_from_date', $now])
                    ->andWhere(['>', 'special_to_date', $now])
                    ->andWhere(['not', ['gallery' => null]]);
                Tools::log($product_ids->createCommand()->getRawSql(), 'home7.log');
                $product_ids = $product_ids->column();

                $special_product_count = count($product_ids);
                if ($special_product_count < $products_num) {
                    //$products_num = $products_num - $special_product_count;
                    $product_most_cheap = $model->find()
                        ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                        ->andWhere(['state' => Products::STATE_APPROVED])
                        ->andWhere(['status' => Products::STATUS_ENABLED])
                        ->andWhere(new Expression('label1&' . self::PRODUCT_FAVOURABLE_TAG . '=1'))
                        ->andWhere(['not in', 'entity_id', $product_ids])
                        ->andWhere(['not', ['gallery' => null]])
                        ->limit($products_num - count($product_ids));
                    // Tools::log($product_most_cheap->createCommand()->getRawSql(), 'home7.log');
                    $product_most_cheap = $product_most_cheap->column();

                    $product_ids = array_merge($product_ids, $product_most_cheap);
                    if (count($product_ids) < $products_num) {
                        $product_other = $model->find()
                            ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                            ->andWhere(['state' => Products::STATE_APPROVED])
                            ->andWhere(['status' => Products::STATUS_ENABLED])
                            ->andWhere(['not in', 'entity_id', $product_ids])
                            ->andWhere(['not', ['gallery' => null]])
                            ->limit($products_num - count($product_ids));
                        // Tools::log($product_other->createCommand()->getRawSql(), 'home7.log');
                        $product_other = $product_other->column();

                        $product_ids = array_merge($product_ids, $product_other);
                    }
                }

                $product_ids = array_slice($product_ids, 0, 3);
                $products = (new ProductHelper())->initWithProductIds($product_ids, $merchantInfo['city'])
                    ->getData();
                $data[$merchantInfo['entity_id']]['product_list'] = $products;
                $data[$merchantInfo['entity_id']]['special_product_number'] = $special_product_count;
            }
        }
        return $data;
    }

    /**
     * 根据$storeModel返回商家详情数组
     *
     * @param $wholesalerIds
     * @param $areaId
     * @return array
     */
    public static function getStoreDetail2($wholesalerIds, $areaId = 0)
    {
        $data = [];
        if (!is_array($wholesalerIds) || count($wholesalerIds) == 0) {
            return $data;
        }

        //查出所有供应商
        $order = implode(',', $wholesalerIds);
        $order_by = [new Expression("FIELD (`entity_id`," . $order . ")")];     //按顺序查出所有供应商
        $wholesalers = LeMerchantStore::find()->where(['in', 'entity_id', $wholesalerIds])
            ->orderBy($order_by)->asArray()->all();
        //Tools::log('-----------$wholesalers:' . print_r($wholesalers, true), 'debug.txt');

        //查出所有供应商配送说明
        $deliveryArray = LeMerchantDelivery::find()->where(['in', 'store_id', $wholesalerIds]);
        if ($areaId > 0) {
            $deliveryArray->andWhere(['delivery_region' => $areaId]);
        }
        $deliveryArray = $deliveryArray->asArray()->all();
        $deliveryArray = Tools::conversionKeyArray($deliveryArray, 'store_id');
        //供应商促销信息
        $rules = Tools::getWholesalerPromotions(array_unique($wholesalerIds));

        //组织数据
        foreach ($wholesalers as $merchantInfo) {
            //配送区域送达时间说明
            $merchant_area_setting = isset($deliveryArray[$merchantInfo['entity_id']]) ? $deliveryArray[$merchantInfo['entity_id']] : null;
            if ($merchant_area_setting) {
                $promised_delivery_text = $merchant_area_setting['note'];
                $min_trade_amount = $merchant_area_setting['delivery_lowest_money'];
            } else {
                $min_trade_amount = $merchantInfo['min_trade_amount'];
                $promised_delivery_text = $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '';
            }

            $promotion_message_in_tag = self::getWholesalerPromotionMessageInTag($rules, $merchantInfo['entity_id']);

            list($tags, $marketing_tags, $category_tags) = self::getMerchantTags($merchantInfo);

            $coupon = [];
            if (self::$wholesalerCoupon) {
                $coupon['icon'] = Tags::$ICON_QUAN;
                $coupon['icon_text'] = '券';
            }

            $data[$merchantInfo['entity_id']] = [
                'wholesaler_id' => $merchantInfo['entity_id'],
                'wholesaler_name' => $merchantInfo['short_name'] ?: $merchantInfo['store_name'],
                'delivery_time' => $promised_delivery_text,
                'min_trade_amount' => round($min_trade_amount), //最低起送价取整
                'operate_time' => $merchantInfo['operate_time'],
                'rebates' => $merchantInfo['rebates'],  //折扣
                'rebates_text' => $merchantInfo['rebates'] ? '全场返现' . $merchantInfo['rebates'] . '%' : '',
                'delivery_text' => $promised_delivery_text,
                'tags' => $tags,
                'marketing_tags' => array_filter($marketing_tags),
                'category_tags' => array_filter($category_tags),
                'store_category' => $merchantInfo['store_category'],
                'promotion_message_in_tag' => $promotion_message_in_tag,
                'coupon' => $coupon,
                'special_product_number' => 0, //特价商品数量
                'product_list' => [], //商品列表
            ];
            //
            $now = date("Y-m-d H:i:s");
            $product_ids = [];
            $products_num = self::HOME_STORE_PRODUCT_NUM;
            $model = new Products($merchantInfo['city']);
            //特价商品
            $product_ids = $model->find()
                ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                ->andWhere(['state' => Products::STATE_APPROVED])
                ->andWhere(['status' => Products::STATUS_ENABLED])
                ->andWhere(['>', 'special_price', 0])
                ->andWhere(['<', 'special_from_date', $now])
                ->andWhere(['>', 'special_to_date', $now])
                ->andWhere(['not', ['gallery' => null]])
                //->limit($products_num)
                ->column();

            $special_product_count = count($product_ids);
            if ($special_product_count < $products_num) {
                //全场最惠
                $product_most_cheap = $model->find()
                    ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                    ->andWhere(['state' => Products::STATE_APPROVED])
                    ->andWhere(['status' => Products::STATUS_ENABLED])
                    ->andWhere(new Expression('label1&' . self::PRODUCT_FAVOURABLE_TAG . '=1'))
                    ->andWhere(['not in', 'entity_id', $product_ids])
                    ->andWhere(['not', ['gallery' => null]])
                    ->limit($products_num - count($product_ids))
                    ->column();

                $product_ids = array_merge($product_ids, $product_most_cheap);
                if (count($product_ids) < $products_num) {
                    //其他商品（随机）
                    $product_other = $model->find()
                        ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                        ->andWhere(['state' => Products::STATE_APPROVED])
                        ->andWhere(['status' => Products::STATUS_ENABLED])
                        ->andWhere(['not in', 'entity_id', $product_ids])
                        ->andWhere(['not', ['gallery' => null]])
                        ->limit($products_num - count($product_ids))
                        ->column();

                    $product_ids = array_merge($product_ids, $product_other);
                }
            }
            $product_ids = array_slice($product_ids, 0, 3);
            $products = (new ProductHelper())->initWithProductIds($product_ids, $merchantInfo['city'])
                ->getData();
            //$products = self::getProductList($merchantInfo);
            //
            if (!empty($products)) {
                /*  取消过滤数据
                $need_key = ['product_id', 'name', 'image', 'price', 'original_price', 'is_special', 'tag_text'];
                foreach ($products as &$product) {
                    foreach ($product as $k => $item) {
                        if (!in_array($k, $need_key)) {
                            unset($product[$k]);
                        }
                    }
                }
                */
                $data[$merchantInfo['entity_id']]['special_product_number'] = $special_product_count;
                $data[$merchantInfo['entity_id']]['product_list'] = $products;
            }
            //
        }
        return $data;
    }


    static public function getProductBriefArray($productModel)
    {
        // 是数组就不用转了
        $productInfo = null;
        if (is_array($productModel)) {
            $productInfo = $productModel;
        } elseif (method_exists($productModel, 'getAttributes')) {
            $productInfo = $productModel->getAttributes();
        } else {
            Exception::catalogProductNotFound();
        }

        // 获取商家信息
        $wholesaler_id = $productInfo['wholesaler_id'];
        $wholesalers_info = Redis::getWholesalers([$wholesaler_id]);
        $wholesalerInfo = unserialize($wholesalers_info[$wholesaler_id]);
        //Tools::log($wholesalerInfo,'getProductBriefArray.log');
        $wholesalerInfo = self::getStoreDetail($wholesalerInfo);
        //Tools::log($wholesalerInfo,'getProductBriefArray.log');
        $gallery = explode(';', $productInfo['gallery']);
        //$image = isset($gallery[0]) ? $gallery[0] : '';

        $finalPrice = Tools::getPrice($productInfo);

        // 返点
        $lelai_rebates = CoreConfigData::getLeLaiRebates();
        $wholesaler_rebates = isset($wholesalerInfo['rebates']) ? $wholesalerInfo['rebates'] : 0;
        //$rebates_all = self::calculateRebates($rebates, $wholesaler_rebates, $lelai_rebates, $productInfo['is_calculate_lelai_rebates']);
        $rebates_all = self::calculateRebates($wholesalerInfo, $productInfo);
        // tag
        if (!isset($productInfo['tags'])) {
            $productInfo['tags'] = Tags::getTags($wholesalerInfo['city'], $productInfo['entity_id']);
        }

        //获取商品中的促销规则ID
        $rule_ids = [];
        //查询优惠条件标签
        $rules = [$productInfo['rule_id']];
        if (count($rule_ids) > 0) {
            $rules = Tools::getProductPromotions($rule_ids);
        }

        $rebate = [];
        if ($rebates_all > 0) {
            $rebate = array(array(
                'short' => '返点' . $rebates_all . '%',
                'text' => '商品参加返' . $rebates_all . '%活动',
                'color' => 'FF0000',
            ));
        }

        $promotion_tags = self::getPromotionRuleTags($rules, $productInfo['rule_id']);
        $tags = array_merge(
            $promotion_tags,
            $rebate,
            $productInfo['tags']
        );

        // 拼名字
        $name = Products::getProductNameText($productInfo);
        $data = [
            'product_id' => $productInfo['entity_id'],
            'name' => $name,
            'image' => Tools::getImage($productInfo['gallery'], '388x388'),
            'price' => $finalPrice,// TODO:此字段有待商榷
            'original_price' => Tools::formatPrice($productInfo['price']),
            'qty' => $productInfo['qty'],
            'specification' => $productInfo['specification'],
            'wholesaler_id' => $productInfo['wholesaler_id'],
            'wholesaler_name' => $wholesalerInfo['short_name'] ?: $wholesalerInfo['wholesaler_name'],
            'wholesaler_url' => 'wholesaler/index/index?sid=' . $productInfo['wholesaler_id'],
            'barcode' => $productInfo['barcode'],
            'first_category_id' => $productInfo['first_category_id'],
            'second_category_id' => $productInfo['second_category_id'],
            'third_category_id' => $productInfo['third_category_id'],
            'special_price' => $productInfo['special_price'],
            'special_from_date' => $productInfo['special_from_date'],
            'special_to_date' => $productInfo['special_to_date'],
            'sold_qty' => $productInfo['sold_qty'],
            'real_sold_qty' => $productInfo['real_sold_qty'],
            'gallery' => $gallery,
            'brand' => $productInfo['brand'],
            'export' => $productInfo['export'],
            'origin' => $productInfo['origin'],
            'package_num' => $productInfo['package_num'],
            'package_spe' => $productInfo['package_spe'],
            'package' => $productInfo['package'],
            'shelf_life' => $productInfo['shelf_life'],
            'desc' => $productInfo['description'],
            'status' => $productInfo['status'],
            'state' => $productInfo['state'],
            'promotion_text_from' => $productInfo['promotion_text_from'],
            'promotion_text_to' => $productInfo['promotion_text_to'],
            'promotion_text' => $productInfo['promotion_text'],
            'product_description' => $productInfo['description'],
            'minimum_order' => $productInfo['minimum_order'],
            'sort_weights' => $productInfo['sort_weights'],
            'rebates' => $productInfo['rebates'],            // 商家单独设置的返点
            'rebates_wholesaler' => $wholesaler_rebates,                // 商家全局返点
            'rebates_lelai' => $productInfo['rebates_lelai'],    // 乐来单独返点(数据库新加字段)
            'lelai_rebates' => $lelai_rebates,                    // 乐来全局返点(proto新加字段)
            'is_calculate_lelai_rebates' => $productInfo['is_calculate_lelai_rebates'],    // 是否取乐来全局返点
            'rebates_all' => $rebates_all,                    // 最终返点计算结果
            'tags' => $tags,
            //'security_info'         => self::getSecurityInfo(),//商品保障详情
            'commission' => $productInfo['commission'],
            'restrict_daily' => $productInfo['restrict_daily'],
            'subsidies_wholesaler' => $productInfo['subsidies_wholesaler'],
            'subsidies_lelai' => $productInfo['subsidies_lelai'],
            'rule_id' => $productInfo['rule_id'],
        ];

        return array_filter($data);
    }

    /**
     * 查询给定商品的相关商品
     *
     * @param \yii\base\Model $productModel
     * @param int $recommendNum
     * @return array
     */
    public function getRelatedProducts($city, $productModel, $recommendNum = 9)
    {
        // 是数组就不用转了
        $productInfo = null;
        if (is_array($productModel)) {
            $productInfo = $productModel;
        } elseif (method_exists($productModel, 'getAttributes')) {
            $productInfo = $productModel->getAttributes();
        } else {
            Exception::catalogProductNotFound();
        }
        //Tools::log($productInfo, 'exception.log');

        // 最多给9个
        $recommendNum = ($recommendNum > 9) ? 9 : $recommendNum;

        // 目前相关商品的优先级为:
        // 找运营编辑的组内商品
        // 同条码的商品
        // 同品牌
        // 同分类

        //$wholesaler = $this->getWholesaler($productInfo['wholesaler_id']);
        $rProductModel = new Products($city);
        $rProductList = [];

        // 通用条件
        $condition = [];
        $condition['wholesaler_id'] = $productInfo['wholesaler_id'];
        $condition = ['and', $condition, ['state' => 2, 'status' => 1]];
        $condition = ['and', $condition, ['not', ['entity_id' => $productInfo['product_id']]]];// 不要添加自己


        //找运营编辑的组内商品(暂不生效)
        if (0 && $recommendNum > 0) {
            $barcode = $productInfo['barcode'];
            $groups = ProductGroupDetail::find()
                ->where(['barcode' => $barcode])
                ->all();
            if ($groups) {
                $group_ids = [];
                /** @var ProductGroupDetail $group */
                foreach ($groups as $group) {
                    array_push($group_ids, $group->group_id);
                }
                $barcodes = ProductGroupDetail::find()
                    ->where(['group_id' => $group_ids])
                    ->andWhere(['!=', 'barcode', $barcode])
                    ->all();
            }

            $condition = ['and', $condition, ['like', 'brand', $productInfo['brand']]];
            $condition = ['and', $condition,
                ['not', ['entity_id' => $productInfo['entity_id']]]// 除去当前商品
            ];
            $temp = $rProductModel->find()
                ->where($condition)
                ->andWhere(['like', 'brand', $productInfo['brand']])
                ->andWhere(['not', ['entity_id' => $productInfo['entity_id']]])
                ->limit($recommendNum)
                ->all();
            if (count($temp)) {
                $recommendNum -= count($temp);
                $rProductList = array_merge($rProductList, $temp);
            }
        }

        // 1.优先展示同条码的商品
        $temp = $rProductModel->find()
            ->where($condition)
            ->andWhere(['barcode' => $productInfo['barcode']])
            ->limit($recommendNum)->asArray()
            ->all();
        if (count($temp)) {
            $recommendNum -= count($temp);
            $rProductList = array_merge($rProductList, $temp);
        }


        //2.同品牌商品
        if ($recommendNum > 0) {
            $temp = $rProductModel->find()
                ->where($condition);
            if ($productInfo['brand']) {
                $temp = $temp->andWhere(['like', 'brand', $productInfo['brand']]);
            }
            $temp = $temp->limit($recommendNum)->asArray()
                ->all();
            if (count($temp)) {
                $recommendNum -= count($temp);
                $rProductList = array_merge($rProductList, $temp);
            }
        }

        //3.同分类
        if ($recommendNum > 0) {
            $temp = $rProductModel->find()
                ->where($condition)
                ->andWhere(['third_category_id' => $productInfo['third_category_id']])
                ->limit($recommendNum)->asArray()
                ->all();
            if (count($temp)) {
                $recommendNum -= count($temp);
                $rProductList = array_merge($rProductList, $temp);
            }
        }

        return $rProductList;
    }

    /**
     * 查找商家model
     *
     * @param $wholesaler_id
     *
     * @return ActiveRecord
     * @throws \Exception
     */
    protected function getWholesaler($wholesaler_id)
    {
        // 查新商家
        if (!isset($this->_wholesaler[$wholesaler_id])) {
            /** @var ActiveRecord $merchantModel */
            $merchantModel = LeMerchantStoreExtend::findMerchantByID($wholesaler_id);
            if (!$merchantModel) {
                $this->_wholesaler[$wholesaler_id] = -1;
            } else {
                $this->_wholesaler[$wholesaler_id] = $merchantModel;
            }
        }
        // 没有查到商家报错
        if (is_numeric($this->_wholesaler[$wholesaler_id]) && $this->_wholesaler[$wholesaler_id] == -1) {
            Exception::storeNotExisted();
        }

        // 返回商家model
        return $this->_wholesaler[$wholesaler_id];
    }

    /**
     * 计算商品返点
     *
     * 商品返点分两部分,商家返点和乐来的返点
     * 商家部分:如果rebates字段非负,则直接去rebates字段,否则按商家全局设置的返点计算
     * 乐来部分:如果is_calculate_lelai_rebates为真,则直接取平台全局乐来返点值,否则取rebates_lelai字段
     * 最终返点为以上两个部分之和
     *
     * @param $wholesalerInfo
     * @param $productInfo
     *
     * @return mixed
     */
    public static function calculateRebates($wholesalerInfo, $productInfo, $lelai_rebates = null)
    {
        // 供应商单独设置的商品返点
        $rebates_wholesaler = $productInfo['rebates'];
        // 供应商全局设置的返点
        $wholesaler_rebates = isset($wholesalerInfo['rebates']) ? $wholesalerInfo['rebates'] : 0;

        // 乐来设置的单商品返点
        $rebates_lelai = isset($productInfo['rebates_lelai']) ? $productInfo['rebates_lelai'] : 0;
        if (is_null($lelai_rebates)) {
            // 乐来全平台返点
            $lelai_rebates = CoreConfigData::getLeLaiRebates();
        }
        // 乐来设置的商品是否取全平台返点
        $isCalculateLelaiRebates = $productInfo['is_calculate_lelai_rebates'];

        if ($rebates_wholesaler >= 0) {// 默认值为-1,小于0表示未设置返点.
            // 设置了商品单独的返点,则忽略商家全局的
            $wholesaler = $rebates_wholesaler;
        } else {
            $wholesaler = $wholesaler_rebates;
        }

        if ($isCalculateLelaiRebates) {
            // 乐来全局返点
            $lelai = $lelai_rebates;
        } else {
            // 单独设置的返点
            $lelai = $rebates_lelai;
        }

        $rebates_all = $wholesaler + $lelai;

        return $rebates_all;
    }


    /**
     * 详情中保障模块
     * @return array
     */
    public static function getSecurityInfo()
    {
        //商品保障详情
        $security_info = [
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '正品低价',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '闪电发货',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '货到付款',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '假一赔十',
            ],
        ];
        return $security_info;
    }

    /**
     * 根据$leMerchantModel获取供应商账号信息
     *
     * @param LeMerchant|array $leMerchantModel
     *
     * @return array
     */
    static public function getWholesalerAccountInfo($leMerchantModel)
    {
        // 是数组就不用转了
        $merchantInfo = [];
        if (is_array($leMerchantModel)) {
            $merchantInfo = $leMerchantModel;
        } elseif (method_exists($leMerchantModel, 'getAttributes')) {
            $merchantInfo = $leMerchantModel->getAttributes();
        } else {
            MerchantException::merchantNotFound();
        }

        $result = array_filter(array(
            'wholesaler_id' => $merchantInfo['entity_id'],
            'user_name' => $merchantInfo['name'],
            'real_name' => $merchantInfo['real_name'],
            'phone' => $merchantInfo['phone'],
            'id_card' => $merchantInfo['id_card'],
            'id_card_front' => $merchantInfo['id_card_front'],
            'id_card_back' => $merchantInfo['id_card_back'],
            'email' => $merchantInfo['email'],
            'auth_token' => $merchantInfo['auth_token'],
        ));

        //供应商判断的位le_merchant_store中的ID
        /** @var LeMerchantStore $wholesaler */
        $wholesaler = LeMerchantStore::find()->where(['merchant_id' => $merchantInfo['entity_id']])->one();

        if ($wholesaler) {
            $result['wholesaler_id'] = $wholesaler->entity_id;
        }
        return $result;
    }


    /**
     * Function: getCustomerModel
     * Author: Jason Y. Wang
     * 返回用户模型
     *
     * @param       $wholesalerId
     * @param $token
     *
     * @return LeMerchant|null
     */
    function getMerchantModel($wholesalerId, $token)
    {

        if (!$wholesalerId) {
            return null;
        }

        //供应商判断的位le_merchant_store中的ID
        /** @var LeMerchantStore $wholesaler_store */
        $wholesaler_store = LeMerchantStore::find()->where(['entity_id' => $wholesalerId])->one();
        $wholesalerId = $wholesaler_store->merchant_id;

        /* @var LeMerchant $merchant */
        $merchant = LeMerchantExtend::findOne(['entity_id' => $wholesalerId]);

        if (!$merchant) {
            MerchantException::merchantNotFound();
        }
        //if($this->_header->getSource() == SourceEnum::PCWEB){
        //	return $merchant;
        //}
        if ($token != $merchant->auth_token) {
            MerchantException::merchantAuthTokenExpired();
        }
        return $merchant;
    }

    protected static function getMerchantTags($merchantInfo)
    {

        $tags = [];
        if ($merchantInfo['rebates'] > 0) {
            $tags[] = [
                'short' => '全场返现' . floor($merchantInfo['rebates']) . '%',
            ];
        }

        //marketing_tags  营销标签
        $marketing_tags = [];
        if (strlen($merchantInfo['marketing_tags']) > 0) {
            $marketing_tags_array = explode(';', $merchantInfo['marketing_tags']);
            foreach ($marketing_tags_array as $marketing_tag) {
                $marketing_tags[] = [
                    'short' => $marketing_tag,
                ];
            }

        }

        //category_tags  分类标签
        $category_tags = [];
        if (strlen($merchantInfo['category_tags']) > 0) {
            $category_tags_array = explode(';', $merchantInfo['category_tags']);
            foreach ($category_tags_array as $category_tag) {
                $category_tags[] = [
                    'short' => $category_tag,
                ];
            }
        }
        return [$tags, $marketing_tags, $category_tags];

    }

    /**
     * @param $rules
     * @param $rule_id
     * Author Jason Y. wang
     * 获取优惠规则信息
     * @return array
     */
    public static function getPromotionRuleTags($rules, $rule_id)
    {
        $promotion_tags = [];
        if (empty($rules) || empty($rule_id) || !is_array($rules) || count($rules) == 0) {
            return $promotion_tags;
        }
        if (isset($rules[$rule_id])) {
            $rule = $rules[$rule_id];
            //Tools::log($rule,'wangyang1.log');
            $promotion_tags = [
                [
                    'short' => isset($rule['tag_short']) ? $rule['tag_short'] : '',
                    'text' => isset($rule['tag_long']) ? $rule['tag_long'] : '',
                    'color' => isset($rule['tag_long_color']) ? $rule['tag_long_color'] : '',
                    'icon' => isset($rule['tag_icon']) ? $rule['tag_icon'] : '',
                    'url' => isset($rule['tag_url']) ? $rule['tag_url'] : '',
                ]
            ];
        }

        return $promotion_tags;
    }

    /**
     * @param $rules
     * @param $wholesaler_id
     * Author Jason Y. wang
     * 获取优惠规则信息
     * @return array
     */
    public static function getWholesalerPromotionMessage($rules, $wholesaler_id)
    {
        $promotion_messages = [];
        if (empty($wholesaler_id) || !is_array($rules) || count($rules) == 0) {
            return $promotion_messages;
        }
        /** @var \service\message\common\PromotionRule $rule */
        foreach ($rules as $rule) {
            if ($rule->getWholesalerId() == $wholesaler_id) {
                $promotion_message = $rule->getWholesalerDescription();
                array_push($promotion_messages, $promotion_message);
            }
        }

        return $promotion_messages;
    }

    /**
     * @param $rules
     * @param $wholesaler_id
     * Author XiaoQiang
     * 获取优惠规则信息
     * @return array
     */
    public static function getWholesalerPromotionMessageInTag($rules, $wholesaler_id, $store_name = '')
    {
        self::$wholesalerCoupon = false;
        $promotion_messages = [];
        if (empty($wholesaler_id) || !is_array($rules) || count($rules) == 0) {
            return $promotion_messages;
        }
        /** @var \service\message\common\PromotionRule $rule */
        foreach ($rules as $rule) {
            if ($rule->getWholesalerId() == $wholesaler_id) {
                $promotion_message = [];
                if ($rule->getCouponType() == 1) {
                    //活动 //1.满额减;2.满额折;3.满额赠;4.满量减;5.满量折;6.满量赠
                    switch ($rule->getPromotionType()) {
                        // 减
                        case 1:
                        case 4:
                            $icon = Tags::$ICON_JIAN;
                            $icon_text = Tags::$ICON_JIAN_TEXT;
                            break;
                        // 折
                        case 2:
                        case 5:
                            $icon = Tags::$ICON_ZHE;
                            $icon_text = Tags::$ICON_ZHE_TEXT;
                            break;
                        // 赠
                        case 3:
                        case 6:
                            $icon = Tags::$ICON_ZENG;
                            $icon_text = Tags::$ICON_ZENG_TEXT;
                            break;
                        // 默认"促"
                        default:
                            $icon = Tags::$ICON_CU;
                            $icon_text = Tags::$ICON_CU_TEXT;
                            break;
                    }

                    $promotion_message = [
                        'text' => $rule->getWholesalerDescription(),
                        'icon' => $icon,
                        'icon_text' => $icon_text,
                        //多品级活动 tag_url生成规则是固定的，不再使用tag_url字段
                        'url' => $rule->getType() == 2 ? self::RULE_TAG_URL_PREFIX . $rule->getRuleId() : $rule->getTagUrl(),
                    ];
                }

                if ($rule->getType() == 3) {
                    $rule_detail_list = [
                        ['title' => '活动名称', 'content' => $rule->getName()],
                    ];

                    $promotion_range = $store_name . "全部商品";
                    if ($rule->getSubsidiesLelaiIncluded() != 1) {
                        $promotion_range .= "<br/>特价商品不参与该活动";
                    }
                    $rule_detail_list [] = ['title' => '活动范围', 'content' => $promotion_range];

                    $rule_detail_list [] = ['title' => '活动时间', 'content' => $rule->getFromDate() . "至<br/>" . $rule->getToDate()];
                    $rule_uses_limit = $rule->getRuleUsesLimit();
                    $uses_limit_text = $rule_uses_limit ? '每人限制' . $rule_uses_limit . '次' : '不限制次数';
                    $rule_detail_list [] = ['title' => '参与次数', 'content' => $uses_limit_text];
                    $rule_detail_list [] = ['title' => '活动详情', 'content' => $rule->getRuleDetail() ? $rule->getRuleDetail() : ''];

                    $promotion_message['promotion_detail'] = $rule_detail_list;

                    //订单级优惠券
                    if ($rule->getCouponType() != 1) {
                        self::$wholesalerCoupon = true;
                    }
                }

                if (!empty($promotion_message)) {
                    array_push($promotion_messages, $promotion_message);
                }
            }
        }

        return $promotion_messages;
    }
}