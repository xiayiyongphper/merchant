<?php
/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-12
 * Time: 上午10:25
 */
namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantStoreExtend;
use common\models\LeMerchantDelivery;
use common\models\LeMerchantStore;
use common\models\LeMerchantStoreCategory;
use common\models\Tags;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\common\StoreBlock;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * 获取改区域的所有供货商
 * Class getAreaStores
 * @package service\resources\merchant\v1
 */
class getAreaStores extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getStoresByAreaIdsRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $areaId = current($request->getAreaIds());
        $categories = LeMerchantStoreCategory::find()->all();
        $storeTable = LeMerchantStore::tableName();
        $response = self::response();
        $dummyQuery = new Query();
        $dummyQuery->from('le_merchant_store')->addSelect(["$storeTable.*", new Expression("0 as store_category_id"), new Expression("0 as store_category_name")])
            ->andWhere('1 = 0');
        /** @var LeMerchantStoreCategory $category */
        foreach ($categories as $category) {
            $sql = LeMerchantStore::find()
                ->addSelect(["*", new Expression("'$category->entity_id' as store_category_id"), new Expression("'$category->name' as store_category_name")])
                ->where(['like', 'area_id', '|' . $areaId . '|'])
                ->andWhere(['status' => LeMerchantStoreExtend::STATUS_NORMAL])
                ->andWhere(['>=', 'sort', 0])
                ->andWhere(['like', 'store_category', '|' . $category->entity_id . '|'])
                ->createCommand()
                ->getRawSql();;
            $dummyQuery->union($sql);
        }
        $unionQuery = new Query();
        $unionQuery->from(['dummy_name' => $dummyQuery])
        ->orderBy(new Expression('sort desc'));
        ToolsAbstract::log($unionQuery->createCommand(LeMerchantStore::getDb())->getRawSql(),'getAreaStores.sql');
        $stores = $unionQuery->all(LeMerchantStore::getDb());
        $wholesalerIds = ArrayHelper::getColumn($stores, 'entity_id');
        $deliveryArray = LeMerchantDelivery::find()->where(['in', 'store_id', $wholesalerIds])
            ->andWhere(['delivery_region' => $areaId])->asArray()->all();
        $deliveryArray = Tools::conversionKeyArray($deliveryArray, 'store_id');
        //促销信息
        $rules = Tools::getWholesalerPromotions(array_unique($wholesalerIds));

        $storeGroup = [];
        foreach ($stores as $store) {
            //配送区域送达时间说明
            $merchant_area_setting = isset($deliveryArray[$store['entity_id']]) ? $deliveryArray[$store['entity_id']] : null;
            if ($merchant_area_setting) {
                $promised_delivery_text = $merchant_area_setting['note'];
                $min_trade_amount = $merchant_area_setting['delivery_lowest_money'];
            } else {
                $min_trade_amount = $store['min_trade_amount'];
                $promised_delivery_text = $store['promised_delivery_time'] ? $store['promised_delivery_time'] . '小时送达' : '';
            }

            $promotion_message_in_tag = self::getWholesalerPromotionMessageInTag($rules, $store['entity_id']);

            list($tags, $marketing_tags, $category_tags) = self::getMerchantTags($store);

            $coupon = [];
            if(self::$wholesalerCoupon){
                $coupon['icon'] = Tags::$ICON_QUAN;
                $coupon['icon_text'] = '券';
            }

            $storeData = [
                'wholesaler_id' => $store['entity_id'],
                'wholesaler_name' => $store['store_name'],
                'phone' => [$store['customer_service_phone']],
                'city' => $store['city'],
                'min_trade_amount' => round($min_trade_amount), //最低起送价取整
                'delivery_text' => $promised_delivery_text,
                'customer_service_phone' => $store['customer_service_phone'],
                'rebates' => $store['rebates'],
                'rebates_text' => $store['rebates'] ? '全场返现' . $store['rebates'] . '%' : '',
                'tags' => $tags,
                'marketing_tags' => $marketing_tags,
                'category_tags' => $category_tags,
                'promotion_message_in_tag' => $promotion_message_in_tag,
                'coupon' => $coupon,
            ];

            if (!isset($storeGroup[$store['store_category_id']])) {
                $storeGroup[$store['store_category_id']] = [
                    'category' => $store['store_category_name'],
                    'store_list' => []
                ];
            }
            $storeGroup[$store['store_category_id']]['store_list'][] = $storeData;
        }
        
        $response->setFrom(ToolsAbstract::pb_array_filter(['store_group' => $storeGroup]));
        return $response;
    }

    public static function request()
    {
        return new getStoresByAreaIdsRequest();
    }

    public static function response()
    {
        return new StoreBlock();
    }
}