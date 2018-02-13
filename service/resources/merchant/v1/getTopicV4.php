<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use common\models\LeMerchantProductList;
use common\models\Products;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\PromotionRule;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\CoreConfigData;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getTopic
 * @package service\resources\merchant\v1
 */
class getTopicV4 extends MerchantResourceAbstract
{


    /**
     * 获取专题页面
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        $key = 'topic';
        /** @var thematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $response = $this->response();
        $redis = Tools::getRedis();
        $ruleId = $request->getRuleId();
        $customer = $this->_initCustomer($request);
        $wholesaler_ids = self::getWholesalerIdsByAreaId($customer->getAreaId());
        $field = $ruleId . '_V4_' . $customer->getAreaId();
        $return = [];
        //是否展示领取优惠券按钮
        $coupons = Proxy::getCouponReceiveList(3,$ruleId,0);
        //Tools::wLog($coupons);
        if($coupons){
            $return['coupon_receive_layout'] = [
                'banner_image' => 'http://assets.lelai.com/assets/coupon/group.png',
            ];
            $couponReceive = $coupons->getCouponReceive();
            $couponList = [];
            foreach ($couponReceive as $coupon) {
                $couponList[] = $coupon->toArray(); // 设置回应的商品优惠券列表
            }
            $return['coupon_list']  = $couponList;
        }

        if (false && $redis->hExists($key, $field)) {
            $return = unserialize($redis->hGet($key, $field));
        } else {
            $productModel = new Products($customer->getCity());
            $products = $productModel->find()
                ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                ->orderBy('sort_weights desc')
                ->andWhere(['rule_id' => $ruleId])
                ->andWhere(['wholesaler_id' => $wholesaler_ids])
                ->asArray()
                ->all();
            $topicList = [];
            foreach ($products as $product) {
                /** @var Products $product */
                if (!isset($topicList[$product['wholesaler_id']])) {
                    $topicList[$product['wholesaler_id']] = [];
                }
                $topicList[$product['wholesaler_id']][] = $product;
            }
//            ToolsAbstract::log('product count:' . count($products));
//            ToolsAbstract::log($topicList);
            $wholesaler_ids = array_keys($topicList);
//            ToolsAbstract::log($wholesaler_ids);
            $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $customer->getAreaId());
//            ToolsAbstract::log($storeDetails);
            //活动列表0
            /** @var LeMerchantProductList $topic */
            foreach ($topicList as $wholesalerId => $topicProducts) {
                $storeDetail = $storeDetails[$wholesalerId];
//                ToolsAbstract::log($topicProducts);
                $thematic = [];
                //参加专题的商品列表
                $thematic['products'] = (new ProductHelper())
                    ->initWithProductArray($topicProducts, $customer->getCity())
                    ->getTags()
                    ->getData();
//                $thematic['products'] = self::getProductsArrayPro($topicProducts, $storeDetail, $lelai_rebates);

                $thematic['store'] = $storeDetail;
                $return['thematic'][] = $thematic;
            }
            $this->getTopicInfo($ruleId, $return);

            $redis->hSet($key, $field, serialize($return));
            $redis->expire($key, 3600); //1小时缓存过期
        }
        if ($return) {
            $response->setFrom(Tools::pb_array_filter($return));
        }
        return $response;
    }

    protected function getTopicInfo($ruleId, &$return)
    {
        $rules = Proxy::getSaleRule($ruleId);
        Tools::log($rules);
        if ($rules) {
            foreach ($rules->getPromotions() as $rule) {
                /** @var PromotionRule $rule */
                if ($rule->getRuleId() > 0) {
                    $return['title'] = $rule->getName();
                    $return['rule'] = $rule->getTopicDescription();
                    if ($rule->getTopicBanner()) {
                        $banners = explode(';', $rule->getTopicBanner());
                        foreach ($banners as $banner) {
                            $return['banner'][] = ['src' => $banner];
                        }
                    }
                    break;
                }
            }
        }
        return false;
    }

    /**
     * @return \framework\redis\Cache
     */
    protected function getRedisCache()
    {
        return \Yii::$app->get('redisCache');
    }

    public static function request()
    {
        return new thematicActivityRequest();
    }

    public static function response()
    {
        return new thematicActivityResponse();
    }

}