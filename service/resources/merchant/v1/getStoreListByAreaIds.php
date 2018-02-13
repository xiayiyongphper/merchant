<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */

namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantStoreExtend;
use service\components\Tools;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByAreaIdsResponse;
use service\resources\MerchantResourceAbstract;


class getStoreListByAreaIds extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getStoresByAreaIdsRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $areaIds = $request->getAreaIds();
        $areaId = array_pop($areaIds);
        $response = $this->response();

        //获取所有区域内店铺列表ID
        $wholesalerIds = LeMerchantStoreExtend::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0])->orderBy('sort desc')->column();

        $wholesalerArray = MerchantResourceAbstract::getStoreDetailBrief($wholesalerIds,$areaId);

        $responseData = [
            'wholesaler_list' => $wholesalerArray,
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new getStoresByAreaIdsRequest();
    }

    public static function response()
    {
        return new getStoresByAreaIdsResponse();
    }
}