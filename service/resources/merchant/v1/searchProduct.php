<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use framework\data\Pagination;
use common\models\Products;
use service\components\search\DateBaseSearch;
use service\components\search\Search;
use service\components\search\SphinxSearch;
use service\components\search\ElasticSearch;
use service\components\Tools;
use service\message\common\Product;
use service\message\merchant\searchProductRequest;
use service\message\merchant\searchProductResponse;
use framework\message\Message;
use service\resources\MerchantResourceAbstract;
use yii\base\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;


class searchProduct extends MerchantResourceAbstract
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     * 加入sphinx搜索
     * @param Message $data
     * @return null|searchProductResponse
     */
    public function run($data)
    {
        $timeStart = microtime(true);
        /** @var searchProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        try {

            $search = new ElasticSearch($customer, $request);
        } catch (\Exception $e) {
            $search = new DateBaseSearch($customer, $request);
        }

        $product = new Products();
        $product->searchModel = $search;
        $products = $product->search();
        $timeEnd = microtime(true);
        return $products;

    }

    public static function request()
    {
        return new searchProductRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}