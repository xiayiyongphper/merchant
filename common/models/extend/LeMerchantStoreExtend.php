<?php

namespace common\models\extend;

use common\models\LeMerchantStore;
use Yii;

class LeMerchantStoreExtend extends LeMerchantStore
{

    /**
     * 未审核
     */
    const STATUS_PENDING = 0;
    /**
     * 正常营业
     */
    const STATUS_NORMAL = 1;
    /**
     * 暂停营业
     */
    const STATUS_SUSPEND = 2;
    /**
     * 封号
     */
    const STATUS_DISABLED = 3;
    /**
     * 审核不通过
     */
    const STATUS_DISAPPROVED = 4;

    public static function findMerchantByID($id)
    {
        $merchant = static::findOne(['entity_id' => $id]);
        if(!empty($merchant)){
            // 处理返点小数位数问题
            $merchant->rebates = floatval($merchant->rebates);
        }
        return $merchant;
    }

    public static function getAreaString($id)
    {
        $info = static::findOne(['entity_id' => $id]);
        if($info){

        }
    }

    public static function getGeneralSelectColumns()
    {
        return [
            'entity_id',
            'store_name',
            'customer_service_phone',
            'store_address',
            'contact_phone',
        ];
    }

    /**
     * getWholesalerIdsByAreaId
     * Author ryan
     *
     * @param $areaId
     * @return array
     */
    public static function getWholesalerIdsByAreaId($areaId,$orderBy = 'sort desc')
    {
        //获取所有区域内店铺列表ID
        $query = static::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0]);
        $query->orderBy($orderBy);
        $wholesalerIds = $query->column();
        return $wholesalerIds;
    }

}
