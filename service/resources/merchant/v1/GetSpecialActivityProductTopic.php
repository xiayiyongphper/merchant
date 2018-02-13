<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use common\models\LeMerchantProductList;
use common\models\LeMerchantProductListGroup;
use common\models\Products;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\customer\CustomerResponse;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getTopicV2
 * @package service\resources\merchant\v1
 */
class GetSpecialActivityProductTopic extends MerchantResourceAbstract
{

    const TOPIC_PRODUCTS_MAX_NUM = 6;

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
        ToolsAbstract::log($request->toArray(), 'GetSpecialActivityProductTopic.log');
        $response = $this->response();
        $customerResponse = $this->_initCustomer($request);
        $identifier = $request->getIdentifier();

        //得到商家列表
        $wholesaler_ids = MerchantResourceAbstract::getWholesalerIdsByAreaId($customerResponse->getAreaId());

        $return = $this->topicList($identifier, $customerResponse, $wholesaler_ids);

        if ($return) {
            $response->setFrom(Tools::pb_array_filter($return));
        }
        return $response;
    }


    protected function getTopicInfo($identifier, &$return)
    {
        $topicInfo = LeMerchantProductListGroup::getThematicInfo($identifier);
        if ($topicInfo) {
            $return['title'] = $topicInfo->title;
            $return['rule'] = $topicInfo->description;
            if ($topicInfo->banner) {
                $banners = explode(';', $topicInfo->banner);
                foreach ($banners as $banner) {
                    $return['banner'][] = ['src' => $banner];
                }
            }
        }
    }

    /**
     * @param $identifier
     * @param CustomerResponse $customerResponse
     * @param $wholesaler_ids
     * @return array
     */
    protected function topicList($identifier, $customerResponse, $wholesaler_ids)
    {
        $return = [];

        $topicList = LeMerchantProductList::getThematic($wholesaler_ids, $identifier);

        if (!$topicList) {
            return $return;
        }

        $conditions = ['or'];
        /** @var LeMerchantProductList $topic */
        foreach ($topicList as $topic) {
            $product_ids = array_filter(explode(';', $topic->product_id));
            $condition = ['in', 'entity_id', $product_ids];
            array_push($conditions, $condition);
        }

        $productModel = new Products($customerResponse->getCity());
        $normalProducts = $productModel::find()
            ->where($conditions)
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->andWhere(['>', 'price', 0])
            ->limit(60)
            ->orderBy('sort_weights desc')
            ->asArray()
            ->all();

        $specialActivityProducts = SpecialProduct::find()
            ->where($conditions)
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->andWhere(['>', 'price', 0])
            ->limit(60)
            ->orderBy('sort_weights desc');
        ToolsAbstract::log($specialActivityProducts->createCommand()->getRawSql());
        $specialActivityProducts = $specialActivityProducts->asArray()
            ->all();

        $products = array_merge($normalProducts, $specialActivityProducts);
        if (count($products) > 0) {
            $thematic = [];
            $thematic['products'] = (new ProductHelper())
                ->initWithProductArray($products, $customerResponse->getCity())
                ->getTags()
                ->getData();
//            $thematic['products'] = self::getProductsArrayPro2($productIds,$customerResponse->getCity());
            $return['thematic'][] = $thematic;
        }

        $this->getTopicInfo($identifier, $return);
        return $return;
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