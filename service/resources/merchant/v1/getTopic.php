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
use service\components\Tools;
use service\components\xhprof_lib\utils\XHProfRunsDefault;
use service\message\customer\CustomerResponse;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\CoreConfigData;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getTopic
 * @package service\resources\merchant\v1
 */
class getTopic extends MerchantResourceAbstract
{

    const TOPIC_PRODUCTS_MAX_NUM = 6;

    /**
     * 获取专题页面  不分组的专题页面
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        $key = 'topic';
        /** @var thematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $response = $this->response();
        $redis = Tools::getRedis();
        $identifier = $request->getIdentifier();
        $customerResponse = $this->_initCustomer($request);
        $field = $identifier . '_V1_' . $customerResponse->getAreaId();
        if (false && $redis->hExists($key, $field)) {
            $return = unserialize($redis->hGet($key, $field));
        } else {
            //得到商家列表
            $wholesaler_ids = MerchantResourceAbstract::getWholesalerIdsByAreaId($customerResponse->getAreaId());
            switch ($identifier) {
                case 'selectionTopic':
                    //精选固定专题
                    $return = $this->selectionTopic($wholesaler_ids, $customerResponse, $identifier);
                    break;
                case 'newArriveTopic':
                    //最新上架固定专题
                    $return = $this->newArriveTopic($wholesaler_ids, $customerResponse, $identifier);
                    break;
                default:
                    //其他专题
                    $return = $this->topicList($wholesaler_ids, $identifier, $customerResponse);
            }
            $redis->hSet($key, $field, serialize($return));
            $redis->expire($key, 3600); //1小时缓存过期
        }
        if ($return) {
            //Tools::log(Tools::pb_array_filter($return),'wangyang.log');
            $response->setFrom(Tools::pb_array_filter($return));
        }
        return $response;
    }

    /**
     * @param $wholesaler_ids
     * @param CustomerResponse $customerResponse
     * @param string $identifier
     * @return array
     */
    protected function selectionTopic($wholesaler_ids, $customerResponse, $identifier = 'selectionTopic')
    {
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $customerResponse->getAreaId());
        $return = [];
        // 返点
        $lelai_rebates = CoreConfigData::getLeLaiRebates();
        $productModel = new Products($customerResponse->getCity());
        foreach ($storeDetails as $storeDetail) {
            $products = $productModel::find()->where(["wholesaler_id" => $storeDetail['wholesaler_id']])
                ->andWhere(['>=', 'sort_weights', 500])
                ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                ->orderBy('sort_weights desc')->limit(6)->asArray()->all();
            //Tools::log($products,'wangyang.log');
            if (count($products) == 0) {
                continue;
            }
//            $thematic['products'] = self::getProductsArrayPro($products,$storeDetail,$lelai_rebates);
            $thematic['products'] = (new ProductHelper())
                ->initWithProductArray($products, $customerResponse->getCity(), '600x600')
                ->getTags()
                ->getData();
            $thematic['store'] = $storeDetail;
            $return['thematic'][] = $thematic;
        }
        $this->getTopicInfo($identifier, $return);
        return $return;
    }

    /**
     * @param $wholesaler_ids
     * @param CustomerResponse $customerResponse
     * @param string $identifier
     * Author Jason Y. wang
     *
     * @return array
     */
    protected function newArriveTopic($wholesaler_ids, $customerResponse, $identifier = 'newArriveTopic')
    {
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $customerResponse->getAreaId());
        // 返点
        $productModel = new Products($customerResponse->getCity());
        $return = [];
        foreach ($storeDetails as $storeDetail) {
            $thematic = [];
            $products = $productModel::find()->where(['wholesaler_id' => $storeDetail['wholesaler_id']])
                ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
                ->orderBy('shelf_time,updated_at desc')->limit(6)->all();
            if (count($products) == 0) {
                continue;
            }
//            $thematic['products'] = self::getProductsArrayPro($products,$storeDetail,$lelai_rebates);
            $thematic['products'] = (new ProductHelper())
                ->initWithProductArray($products, $customerResponse->getCity(), '600x600')
                ->getTags()
                ->getData();
            $thematic['store'] = $storeDetail;
            $return['thematic'][] = $thematic;
        }
        $this->getTopicInfo($identifier, $return);
        return $return;
    }

    /**
     * @param $wholesaler_ids
     * @param $identifier
     * @param CustomerResponse $customerResponse
     * Author Jason Y. wang
     *
     * @return array
     */
    protected function topicList($wholesaler_ids, $identifier, $customerResponse)
    {
        $return = [];
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $customerResponse->getAreaId(),'sort desc');

        //活动列表
        $topicList = LeMerchantProductList::getThematic($wholesaler_ids, $identifier);

        if (!$topicList) {
            return $return;
        }
        /** @var LeMerchantProductList $topic */
        foreach ($topicList as $topic) {
            $storeDetail = $storeDetails[$topic->wholesaler_id];
            $thematic = [];
            //参加专题的商品列表
            $product_ids = explode(';', $topic->product_id);

//            $thematic['products'] = self::getProductsArrayPro($products,$storeDetail,$lelai_rebates);
            $thematic['products'] = (new ProductHelper())
                ->initWithProductIds($product_ids, $customerResponse->getCity())
                ->getTags()
                ->getData();
            $thematic['store'] = $storeDetail;
            $return['thematic'][] = $thematic;
        }
        $this->getTopicInfo($identifier, $return);
        return $return;
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
     * @return \framework\redis\Cache
     */
    protected function getRedisCache()
    {
        return \Yii::$app->get('redisCache');
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