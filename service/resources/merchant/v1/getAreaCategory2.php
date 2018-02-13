<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use common\models\ClassPage;
use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\categoryResponse;
use service\message\merchant\getAreaCategoryRequest;
use service\resources\MerchantResourceAbstract;


class getAreaCategory2 extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getAreaCategoryRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $wholesaler_id = $request->getWholesalerId();

        $city = $customer->getCity();
        $area_id = $customer->getAreaId();

        //供应商查询
        if ($wholesaler_id > 0) {
            $wholesaler_ids = [$wholesaler_id];
        } else {
            // 否则就查该区域的商家id
            $wholesaler_ids = MerchantResourceAbstract::getWholesalerIdsByAreaId($area_id);
        }
        $elasticSearch = new ElasticSearchExt($customer);
        $category = $elasticSearch->getCategory($wholesaler_ids);

        $response = $this->response();

        $result['category'] = $category;

        //自定义推荐分类
        $recommendCategories = ClassPage::getClassPageRecommendList($city);
        foreach ($recommendCategories as $recommendCategory) {
            $keyValue = [];
            $keyValue['key'] = $recommendCategory['entity_id'];
            $keyValue['value'] = $recommendCategory['name'];
            $result['recommend_catetory'][] = $keyValue;
        }
        $response->setFrom(Tools::pb_array_filter($result));

        return $response;
    }

    public static function request()
    {
        return new getAreaCategoryRequest();
    }

    public static function response()
    {
        return new categoryResponse();
    }
}