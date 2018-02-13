<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/23
 * Time: 9:41
 */

namespace service\resources\merchant\v1;

use service\components\Proxy;
use service\components\Tools;
use service\resources\MerchantResourceAbstract;
use service\message\merchant\getStoresByCategoryIdRequest;
use service\message\merchant\getStoresByCategoryIdResponse;
use common\models\LeMerchantStoreCategory;

class getStoresByCategoryId extends MerchantResourceAbstract
{
    const RECENTLY_BUY_LIMIT = 4;

    public function run($data)
    {
        $request = self::request();
        $request->parseFromString($data);
        //
        $flag = $request->getFlag();
        $areaIds = $request->getAreaIds();
        $areaId = array_pop($areaIds);

        //1-按照分类，2-最常购买，3-综合排序
        switch ($flag) {
            case 1:
                $categoryId = $request->getCategoryId();
                $wholesalerIds = $this->getAllWholesalerIds($categoryId, $areaId);
                break;
            case 2:
                $categoryId = 0;
                $customer = $this->_initCustomer($request);
                $orderBy = 'sort_score desc';
                $wholesalerIds = MerchantResourceAbstract::getWholesalerIdsByAreaId($customer->getAreaId(), $orderBy);
                //$recentBuyWholesalerIds
                $wholesalerIds = Proxy::getRecentlyBuyWholesalerIds($customer->getCustomerId(), $wholesalerIds);
                Tools::log($wholesalerIds, 'debug.txt');
                if (count($wholesalerIds) > self::RECENTLY_BUY_LIMIT) {
                    $wholesalerIds = array_slice($wholesalerIds, 0, self::RECENTLY_BUY_LIMIT);
                }
                break;
            default:
                $categoryId = 0;
                $wholesalerIds = $this->getAllWholesalerIds($categoryId, $areaId);
        }
        $wholesalers = $this->getStoreDetail2($wholesalerIds, $areaId);
        Tools::log('--------$wholesalers: ' . print_r($wholesalers, true), 'debug.txt');

        //设置供货商类别icon
        $this->processCategoryIcon($categoryId, $wholesalers);

        $data = [
            'wholesaler_list' => $wholesalers,
        ];
        //
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($data));
        //Tools::log($response->toArray(), 'debug.txt');
        return $response;
    }

    /**
     * 设置供货商类别icon
     * @param $categoryId
     * @param $wholesalers
     */
    private function processCategoryIcon($categoryId, &$wholesalers)
    {
        $allStoreCategory = $this->getAllMerchantStoreCategory();
        foreach ($wholesalers as &$wholesaler) {
            if ($categoryId == 0) {
                $storeCategory = empty($wholesaler['store_category']) ? [] : explode('|', $wholesaler['store_category']);
                $storeCategory = array_filter($storeCategory);
                $categoryIdTmp = empty($storeCategory) ? 0 : array_shift($storeCategory);
            } else {
                $categoryIdTmp = $categoryId;
            }
            //Tools::log("----------categoryIdTmp:$categoryIdTmp".PHP_EOL, 'debug.txt');
            $wholesaler['category_icon'] = isset($allStoreCategory[$categoryIdTmp]) ? $allStoreCategory[$categoryIdTmp] : '';
        }
        unset($allStoreCategory);
        return;
    }


    /**
     *
     * @return array
     */
    private function getAllMerchantStoreCategory()
    {
        $merchantStoreCategory = LeMerchantStoreCategory::find()->select(['entity_id', 'icon'])->asArray()->all();
        $storeCategory = [];
        foreach ($merchantStoreCategory as $row) {
            $storeCategory[$row['entity_id']] = $row['icon'];
        }
        return $storeCategory;
    }

    public static function request()
    {
        return new getStoresByCategoryIdRequest();
    }

    public static function response()
    {
        return new getStoresByCategoryIdResponse();
    }


}