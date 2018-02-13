<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use common\models\extend\LeMerchantStoreExtend;
use common\models\LeMerchantProductList;
use common\models\LeMerchantProductListGroup;
use common\models\LeMerchantStore;
use common\models\Products;
use service\components\Redis;
use service\components\Tools;
use service\message\customer\CustomerResponse;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\CoreConfigData;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use yii\helpers\ArrayHelper;

/**
 * Author: Jason Y. Wang
 * Class getTopicV2
 * @package service\resources\merchant\v1
 */
class getTopicV2 extends MerchantResourceAbstract
{

    const TOPIC_PRODUCTS_MAX_NUM = 6;

    /**
     * 获取专题页面  按商家得到的专题页面
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var thematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $response = $this->response();
        $customerResponse = $this->_initCustomer($request);
        $identifier = $request->getIdentifier();

        //得到商家列表
        $wholesaler_ids = MerchantResourceAbstract::getWholesalerIdsByAreaId($customerResponse->getAreaId());

        $return = $this->topicList($identifier,$customerResponse,$wholesaler_ids);

        if($return){
            $response->setFrom(Tools::pb_array_filter($return));
        }
        return $response;
    }


    protected function getTopicInfo($identifier,&$return){
        $topicInfo = LeMerchantProductListGroup::getThematicInfo($identifier);
        if($topicInfo){
            $return['title'] = $topicInfo->title;
            $return['rule'] = $topicInfo->description;
            if($topicInfo->banner){
                $banners = explode(';',$topicInfo->banner);
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
    protected function topicList($identifier,$customerResponse,$wholesaler_ids){
        $return = [];

        $topicList = LeMerchantProductList::getThematic($wholesaler_ids,$identifier);

        if(!$topicList){
            return $return;
        }

        $product_ids = [];
        /** @var LeMerchantProductList $topic */
        foreach ($topicList as $topic) {
            $product_ids_array = array_filter(explode(';',$topic->product_id));
            $product_ids = array_merge($product_ids,$product_ids_array);
        }
        Tools::log($product_ids,'getTopicV2.log');
        if(count($product_ids)){
            $thematic = [];
            $thematic['products'] = (new ProductHelper())
                ->initWithProductIds($product_ids, $customerResponse->getCity())
                ->getTags()
                ->getData();
//            $thematic['products'] = self::getProductsArrayPro2($productIds,$customerResponse->getCity());
            $return['thematic'][] = $thematic;
        }

        $this->getTopicInfo($identifier,$return);
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