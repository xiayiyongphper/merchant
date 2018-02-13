<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use service\components\Redis;
use service\components\Tools;
use service\message\customer\CustomerResponse;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\ProductTopicResponse;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use yii\helpers\ArrayHelper;
use framework\data\Pagination;
use framework\components\ToolsAbstract;
use common\models\HomePageConfig;

/**
 * Author: Jason Y. Wang
 * Class getTopicV2
 * @package service\resources\merchant\v1
 */
class getProductTopic extends MerchantResourceAbstract
{
    const PAGE_SIZE = 18;
    protected $_areaId;
    protected $_cityId;
    protected $_wholesalerIds;

    /**
     * 获取产品模块详情
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var thematicActivityRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $identifier = $request->getIdentifier();

        $response = $this->response();
        $customerResponse = $this->_initCustomer($request);
        $this->_areaId = $customerResponse->getAreaId();
        $this->_cityId = $customerResponse->getCity();
        Tools::log("areaId: {$this->_areaId} ,cityId:{$this->_cityId}",'hl.log');

        //区域内店铺IDs
        $this->_wholesalerIds = $this->getWholesalerIdsByAreaId($this->_areaId);
        Tools::log('all_wholesaler_ids:'.json_encode($this->_wholesalerIds),'hl.log');
        //无供应商时
        if (count($this->_wholesalerIds) == 0) {
            return $response;
        }

        if(empty($identifier)){
            return $response;
        }

        $data = [
            'title' => $identifier,
            'products' => []
        ];
        $productIds = [];
        $product_block = $this->parseJson();
        foreach ($product_block as $block){
            if(!isset($block['title']) || $block['title'] != $identifier){
                continue;
            }

            //$productIds = isset($block['products']) ? $block['products'] : [];
            $productIds = !empty($block['products']) ? explode(',',$block['products']) : [];
            Tools::log("all product ids :".json_encode($productIds),'hl.log');
            break;
        }

        $products = [];
        if(!empty($productIds)){
            $products = (new ProductHelper())->initWithProductIds($productIds, $this->_cityId, $this->_wholesalerIds)
                ->getTags()
                ->getData();
        }

        $totalCount = count($products);
        $pageNation_request = $request->getPagination();
        if($pageNation_request){
            $page_num = $pageNation_request->getPage()?:1;
            $page_size = $pageNation_request->getPageSize()?:self::PAGE_SIZE;
        }else{
            $page_num = 1;
            $page_size = self::PAGE_SIZE;
        }

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->setPageSize($page_size);
        $pagination->setCurPage($page_num);
        $data['pagination'] = Tools::getPagination($pagination);

        $offset = $pagination->getOffset();
        $products = array_slice($products,$offset,$page_size);
        $data['products'] = $products;

        $response->setFrom(Tools::pb_array_filter($data));
        return $response;
    }

    /**
     * @return mixed
     */
    protected function parseJson()
    {
        $featured = HomePageConfig::find()
            ->where(['refer_id' => $this->_cityId])
            ->andWhere(['type' => HomePageConfig::CONFIG_TYPE_HOME])
            ->andWhere(['<=', 'start_time', ToolsAbstract::getDate()->date()])
            ->andWhere(['like', 'version', '2.0'])
            ->orderBy('start_time DESC')->asArray()->one();
        $json = $featured['content'];
        $json = json_decode($json, true);
        return $json['product_blocks'];
    }

    public static function request()
    {
        return new thematicActivityRequest();
    }

    public static function response()
    {
        return new ProductTopicResponse();
    }

}