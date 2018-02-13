<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantStoreExtend;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\common\Store;
use service\message\merchant\getStoreDetailRequest;
use service\models\homepage\storeHomeConfig;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;


class getStoreDetail1 extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getStoreDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        $wholesaler_id = $request->getWholesalerId();
        $customer = $this->_initCustomer($request);

        $areaId = $customer->getAreaId();

        /** @var LeMerchantStoreExtend $wholesaler */
        $wholesaler = LeMerchantStoreExtend::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['entity_id' => $wholesaler_id])->one();

        if (empty($wholesaler)) {
            Exception::storeNotExisted();
        }

        if ($wholesaler->status != LeMerchantStoreExtend::STATUS_NORMAL) {
            Exception::wholesalerStatusStop();
        }

        // redis读
        $wholesalers_info = Redis::getWholesalers([$wholesaler_id]);
        $wholesaler_info = unserialize($wholesalers_info[$wholesaler_id]);
        if (!$wholesaler_info) {
            Exception::storeNotExisted();
        }


        $response = $this->response();
        $data = MerchantResourceAbstract::getStoreDetail($wholesaler_info, $areaId);

        //去掉默认banner
        foreach ($data['banner'] as $key => $banner) {
            if ($banner['src'] == self::$bannerUrl) {
                unset($data['banner'][$key]);
            }
        }

        //是否展示领取优惠券按钮 和 优惠券列表
        $coupons = Proxy::getCouponReceiveList(2, 0, $wholesaler_id);
        //Tools::wLog($coupons);
        if ($coupons) {
            $data['coupon_receive_layout'] = [
                'banner_image' => 'http://assets.lelai.com/assets/coupon/group.png',
            ];

            $coupons = $coupons->toArray();
            //Tools::log($coupons,'hl.log');
            $data['coupon_list'] = $coupons['coupon_receive'];
        }

        // 供应商首页单独的配置
        $config_model = new storeHomeConfig($customer, $this->getAppVersion(), $wholesaler_id);
        $homepage_config = $config_model->toArray();
        if ($homepage_config) {
            if (isset($homepage_config['brand_blocks']) && isset($homepage_config['brand_blocks'][0])) {
                $homepage_config['brand_block'] = $homepage_config['brand_blocks'][0];
                unset($homepage_config['brand_blocks']);
            }
            if (isset($homepage_config['quick_entry_blocks'])) {
                $homepage_config['quick_entry_module'] = $homepage_config['quick_entry_blocks'];
                unset($homepage_config['quick_entry_blocks']);
            }
        }
        $data['homepage_config'] = $homepage_config;


        Tools::log(Tools::pb_array_filter($data), 'store_detail.log');
        $response->setFrom(Tools::pb_array_filter($data));
        return $response;
    }


    public static function request()
    {
        return new getStoreDetailRequest();
    }

    public static function response()
    {
        return new Store();
    }
}