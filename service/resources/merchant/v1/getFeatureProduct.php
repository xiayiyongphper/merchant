<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use common\models\LeMerchantProductList;
use common\models\LeMerchantStore;
use common\models\Products;
use service\components\Tools;
use service\message\merchant\getFeatureProductsRequest;
use service\message\merchant\getFeatureProductsResponse;
use service\message\merchant\getProductRequest;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;


class getFeatureProduct extends MerchantResourceAbstract
{
    public function run($data)
    {
        $timeStart = microtime(true);
        /** @var getProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $customer = $this->_initCustomer($request);
        /** @var LeMerchantStore $merchantModel */
        $identifier = 'featured_product_list';
        $list = LeMerchantProductList::find()
            ->where(['identifier' => $identifier,])
            ->andWhere(['wholesaler_id' => $request->getWholesalerId()])->asArray()->all();
        $productIds = [];
        foreach ($list as $item) {
            $product_ids = $item['product_id'];
            $product_ids = array_filter(explode(';', $product_ids));
            $productIds = array_merge($productIds, $product_ids);
        }
        Tools::log($productIds,'wangyang.log');
        $block = [];
        $products = (new ProductHelper())
            ->initWithProductIds($productIds, $customer->getCity(), [$request->getWholesalerId()])
            ->getTags()
            ->getData();
        Tools::log($products,'wangyang.log');
        $products = array_slice($products, 0, 30);
        $block['product_list'] = $products;
        $response = $this->response();

        $response->setFrom(Tools::pb_array_filter($block));
        $timeEnd = microtime(true);
        //Tools::log($timeEnd-$timeStart,'wangyang.log');
        return $response;
    }

    public static function request()
    {
        return new getFeatureProductsRequest();
    }

    public static function response()
    {
        return new getFeatureProductsResponse();
    }
}