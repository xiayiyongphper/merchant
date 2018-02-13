<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2017/9/20
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\getProductRequest;
use service\message\merchant\thematicActivityResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getAggregationProducts
 * @package service\resources\merchant\v1
 * 商品详情页的商品聚合模块，包含标准库聚合和ES聚合
 */
class getAggregationProductsArea extends MerchantResourceAbstract
{

    private $productGroupType = [
        1 => '更多颜色',
        2 => '更多口味',
        3 => '更多包装',
    ];

    private $result = [];

    /**
     * 获取聚合商品
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var getProductRequest $request */
        $request = $this->request();
        $response = $this->response();

        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $productIds = $request->getProductIds();
        $wholesalerId = $request->getWholesalerId();
        Tools::log($productIds, 'getAggregationProductsArea.log');
        //商品ID
        $productId = array_pop($productIds);

        if ($productId == 0) {
            return $response;
        }

        $productArray = (new ProductHelper())->initWithProductIds([$productId], $customer->getCity())->getData();

        if (empty($productArray[$productId])) {
            Tools::log($productArray, 'getAggregationProductsArea.log');
            return $response;
        }
        $lsin = $productArray[$productId]['lsin'];

        $this->getAggregationProductsFromPms($customer, $lsin, $wholesalerId);
        $this->getAggregationProductsFromEs($customer, $lsin, $productId);
        $response->setFrom(Tools::pb_array_filter(['thematic' => $this->result]));
        return $response;
    }

    private function getAggregationProductsFromEs($customer, $lsin, $productId)
    {
        $elasticSearch = new ElasticSearchExt($customer);
        $products = $elasticSearch->getAggregationProductsFromEs($lsin, $productId);
        if (!empty($products)) {
            $sub_area['title'] = '更多同款';
            $sub_area['products'] = $products;
            array_push($this->result, $sub_area);
        }
    }

    private function getAggregationProductsFromPms($customer, $lsin, $wholesalerId)
    {
        $productGroup = Tools::getProductGroupProducts($lsin);

        $elasticSearch = new ElasticSearchExt($customer);
        foreach ($productGroup as $productGroupId => $lsins) {
            $products = $elasticSearch->getProductGroupProducts($lsins, $wholesalerId);
            if (!empty($products)) {
                if (empty($this->productGroupType[$productGroupId])) {
                    $sub_area['title'] = '更多商品';
                } else {
                    $sub_area['title'] = $this->productGroupType[$productGroupId];
                }

                $sub_area['products'] = $products;
                array_push($this->result, $sub_area);
            }

        }
    }

    public static function request()
    {
        return new getProductRequest();
    }

    public static function response()
    {
        return new thematicActivityResponse();
    }

}