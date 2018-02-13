<?php


namespace service\resources\merchant\v1;

use common\models\CustomThematicActivity;
use common\models\CustomThematicActivitySub;
use common\models\CustomThematicActivitySubProduct;
use common\models\LeMerchantStore;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\merchant\customThematicActivityRequest;
use service\message\merchant\customThematicActivityResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getCustomThematicActivity
 * @package service\resources\merchant\v1
 */
class getCustomThematicActivity extends MerchantResourceAbstract
{
    /**
     * 获取专题页面  2.9版本新增
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var customThematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $response = $this->response();
        $customer = $this->_initCustomer($request);
        $response_data = [];
        $thematic_id = $request->getThematicId();
        $wholesalerIds = MerchantResourceAbstract::getWholesalerIdsByAreaId($customer->getAreaId());
        /** @var CustomThematicActivity $thematic_activity */
        $thematic_activity = CustomThematicActivity::find()->where(['entity_id' => $thematic_id])->one();
        $response_data['banner']['src'] = $thematic_activity->banner;
        $response_data['title'] = $thematic_activity->title;
        $response_data['rule'] = $thematic_activity->rule;
        $promotion_ids = $thematic_activity->promotion_ids;

        if ($promotion_ids) {
            $promotion_ids = explode(',', $promotion_ids);
            $couponList = [];
            foreach ($promotion_ids as $promotion_id) {
                $coupons = Proxy::getCouponReceiveList(3, $promotion_id, 0);
                if ($coupons) {
                    $couponReceive = $coupons->getCouponReceive();
                    foreach ($couponReceive as $coupon) {
                        array_push($couponList, $coupon->toArray()); // 设置回应的商品优惠券列表
                    }
                }
            }
            $response_data['coupon_list'] = $couponList;
        }
        $type = $thematic_activity->type;
        switch ($type) {
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_ONE: //不使用tab分组
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_TWO: //按供应商分组
                $thematic_subs = $this->getThematicSubByWholesaler($thematic_id, $customer->getCity(),$wholesalerIds);
                $response_data['custom_thematic'] = $thematic_subs;
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_THREE: //按分类分组
                $thematic_subs = $this->getThematicSubByCategory($thematic_id, $customer->getCity());
                $response_data['custom_thematic'] = $thematic_subs;
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_FOUR: //自定义分组
                $thematic_subs = $this->getThematicSubBySetting($thematic_id);
                $response_data['custom_thematic'] = $thematic_subs;
                break;
            default:
                break;
        }

        $response->setFrom(Tools::pb_array_filter($response_data));
        return $response;
    }

    public function getThematicSubBySetting($thematic_id)
    {
        $custom_thematics = CustomThematicActivitySub::find()->where(['thematic_id' => $thematic_id])->orderBy('sort asc')->all();

        $thematic_subs = [];
        /** @var CustomThematicActivitySub $custom_thematic */
        foreach ($custom_thematics as $custom_thematic) {
            $custom_thematic_tmp = [];
            $custom_thematic_tmp['id'] = $custom_thematic->entity_id;
            $custom_thematic_tmp['short_name'] = $custom_thematic->short_name;
            $custom_thematic_tmp['long_name'] = $custom_thematic->long_name;
            $custom_thematic_tmp['image_name'] = $custom_thematic->image_name;
            $custom_thematic_tmp['schema'] = $custom_thematic->schema_url;
            $thematic_subs[] = $custom_thematic_tmp;
        }

        return $thematic_subs;
    }

    public function getThematicSubByCategory($thematic_id, $city)
    {
        $category_ids = CustomThematicActivitySubProduct::find()->alias('t')
            ->select('p.first_category_id')
            ->leftJoin('lelai_booking_product_a.products_city_' . $city . ' as p', 't.product_id = p.entity_id')
            ->where(['thematic_id' => $thematic_id])
            ->groupBy('p.first_category_id')->column();
        Tools::log($category_ids,'getCustomThematicActivity.log');
        $categories = Redis::getCategories($category_ids);
        Tools::log($categories,'getCustomThematicActivity.log');
        $thematic_subs = [];
        foreach ($categories as $category) {
            $custom_thematic_tmp = [];
            $custom_thematic_tmp['id'] = $category['id'];
            $custom_thematic_tmp['short_name'] = $category['name'];
            $custom_thematic_tmp['long_name'] = $category['name'];
            $thematic_subs[] = $custom_thematic_tmp;
        }
        return $thematic_subs;
    }

    public function getThematicSubByWholesaler($thematic_id, $city,$wholesalerIds)
    {
        $wholesaler_ids = CustomThematicActivitySubProduct::find()->alias('t')
            ->select('p.wholesaler_id')
            ->leftJoin('lelai_booking_product_a.products_city_' . $city . ' as p', 't.product_id = p.entity_id')
            ->where(['thematic_id' => $thematic_id])
            ->andWhere(['p.wholesaler_id'=>$wholesalerIds])
            ->groupBy('p.wholesaler_id')->column();
        $wholesalers = LeMerchantStore::find()->select(['entity_id', 'store_name'])
            ->where(['entity_id' => $wholesaler_ids])
            ->orderBy('sort desc')
            ->asArray()->all();
        $thematic_subs = [];
        foreach ($wholesalers as $wholesaler) {
            $custom_thematic_tmp = [];
            $custom_thematic_tmp['id'] = $wholesaler['entity_id'];
            $custom_thematic_tmp['short_name'] = $wholesaler['store_name'];
            $custom_thematic_tmp['long_name'] = $wholesaler['store_name'];
            $thematic_subs[] = $custom_thematic_tmp;
        }
        return $thematic_subs;
    }

    public static function request()
    {
        return new customThematicActivityRequest();
    }

    public static function response()
    {
        return new customThematicActivityResponse();
    }

}