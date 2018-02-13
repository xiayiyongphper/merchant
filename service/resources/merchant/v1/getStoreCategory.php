<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/22
 * Time: 15:29
 */

namespace service\resources\merchant\v1;

//use service\components\Proxy;
use service\components\Tools;
use service\message\merchant\getStoreCategoryRequest;
use service\message\merchant\getStoreCategoryResponse;
use service\resources\MerchantResourceAbstract;
use common\models\LeMerchantStoreCategory;


class getStoreCategory extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getStoresByAreaIdsRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //
        $categorys = self::getCategorys();
        if (!empty($categorys)) {
            $all = [['category_id' => 0, 'name' => '全部']];
            $categorys = array_merge($all, $categorys);
        }
        $data = [
            'category_list' => $categorys,
        ];
        //
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($data));
        //Tools::log($response->toArray(), 'debug.txt');
        return $response;
    }

    public static function request()
    {
        return new getStoreCategoryRequest();
    }

    public static function response()
    {
        return new getStoreCategoryResponse();
    }

    public static function getCategorys()
    {
        //查出所有供应商
        return LeMerchantStoreCategory::find()->select(['entity_id AS category_id', 'name','icon AS category_icon'])->asArray()->all();
    }

}