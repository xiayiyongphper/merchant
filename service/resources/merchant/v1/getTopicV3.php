<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use common\models\Products;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\PromotionRule;
use service\message\customer\CustomerResponse;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use yii\helpers\ArrayHelper;

/**
 * Author: Jason Y. Wang
 * Class getTopicV2
 * @package service\resources\merchant\v1
 */
class getTopicV3 extends MerchantResourceAbstract
{

    /**
     * 获取专题页面
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var thematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $response = $this->response();
        $customer = $this->_initCustomer($request);
        $ruleId = $request->getRuleId();
        $wholesaler_ids = self::getWholesalerIdsByAreaId($customer->getAreaId());

        $productModel = new Products($customer->getCity());
        //外层查询商品，负责分页
        $_products = $productModel::find()
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->andWhere(['>', 'price', 0])
            ->andWhere(['rule_id' => $ruleId])
            ->andWhere(['wholesaler_id' => $wholesaler_ids])
            ->orderBy('sort_weights desc')->asArray()
            ->limit(60)
            ->all();

        if (count($_products)) {
            $thematic = [];
            $thematic['products'] = (new ProductHelper())
                ->initWithProductArray($_products, $customer->getCity())
                ->getTags()
                ->getData();
//            $thematic['products'] = self::getProductsArrayPro2($productIds, $customer->getCity());
            $return['thematic'][] = $thematic;
        }else{
            Exception::topicNotExist();
        }

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

        $this->getTopicInfo($ruleId, $return);
        //得到商家列表
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

    public static function request()
    {
        return new thematicActivityRequest();
    }

    public static function response()
    {
        return new thematicActivityResponse();
    }

}