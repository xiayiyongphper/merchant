<?php
namespace service\resources\merchant\v1;

use framework\data\Pagination;
use common\models\Products;
use service\components\Redis;
use service\components\Tools;
use service\message\merchant\searchProductResponse;
use service\message\merchant\wishlistRequest;
use service\models\Wishlist as WL;
use service\resources\MerchantResourceAbstract;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-29
 * Time: 下午2:54
 */
class wishlist extends MerchantResourceAbstract
{
    public function run($data)
    {
        $pageSize = 10;
        /** @var wishlistRequest $request */
        $request = new wishlistRequest();
		$request->parseFromString($data);
        $response = new searchProductResponse();
        $customer = $this->_initCustomer($request);
        $key = WL::getKey($request->getCustomerId(), $customer->getCity());
        $redis = Tools::getRedis();
        $keys = $redis->hKeys($key);
        $pageKeys = array_chunk($keys, $pageSize);
        $page = $request->getPagination()->getPage() ? $request->getPagination()->getPage() : 1;
        $pages = new Pagination(['totalCount' => count($keys)]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);
        $responseData = [
            'pages' => [
                'total_count' => $pages->getTotalCount(),
                'page' => $pages->getCurPage(),
                'last_page' => $pages->getLastPageNumber(),
                'page_size' => $pages->getPageSize(),
            ],
        ];
        if ($page <= count($pageKeys)) {
            --$page;
            $hashKeys = $pageKeys[$page];
            $list = $redis->hMGet($key, $hashKeys);
            // 获取所有商家id
            $wids = array();
            foreach ($list as $json) {
                if (WL::validJsonSchema($json)) {
                    $value = json_decode($json, true);
                    $wids[$value['wholesaler_id']] = $value['wholesaler_id'];
                }
            }
            $winfo = Redis::getWholesalers($wids);
            $productModel = new Products($customer->getCity());
            $products = $productModel->find()->where(['entity_id' => $hashKeys])->all();
            $product_list = [];
            foreach ($products as $item) {
                $data = $this->getProductBriefArray($item);
                // 商家名
                $onew = unserialize($winfo[$data['wholesaler_id']]);
                $data['wholesaler_name'] = $onew['store_name'];
                $data['purchased_qty'] = Tools::getPurchasedQty($customer->getCustomerId(), $customer->getCity(), $data['product_id']);
                // 插入
                array_push($product_list, array_filter($data));
            }
            $responseData['product_list'] = $product_list;
        }
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new wishlistRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}