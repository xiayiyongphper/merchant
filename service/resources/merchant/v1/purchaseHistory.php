<?php

namespace service\resources\merchant\v1;

use common\models\Products;
use framework\components\es\Console;
use framework\components\ToolsAbstract;
use framework\message\Message;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\KeyValueItem;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\message\merchant\purchaseHistoryRequest;
use service\message\merchant\searchProductResponse;
use service\message\sales\ProductReportRequest;
use service\message\sales\ProductReportResponse;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-29
 * Time: 下午2:54
 */
class purchaseHistory extends MerchantResourceAbstract
{
    const GROUP_SIZE = 3;
    const PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var purchaseHistoryRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $productReportResponse = $this->getProductReport($request, $customer);
        $groupData = $this->processGroupData($productReportResponse->getItems(), $productReportResponse->getGroupSize());

        $response = self::response();
        $responseData = [];
        // 获取所有商家id
        $wids = [];
        $frequency = [];
        foreach ($groupData as $value) {
            $wids[$value['wholesaler_id']] = $value['wholesaler_id'];
            $frequency[$value['product_id']] = $value['frequency'];
        }
        $winfo = Redis::getWholesalers($wids);
        $productModel = new Products($customer->getCity());
        $productIds = ArrayHelper::getColumn($groupData, 'product_id');
        if (count($productIds) > 0) {
            $query = $productModel->find()
                ->where(['entity_id' => $productIds])
                ->orderBy([new Expression("FIELD (`entity_id`," . implode(',', $productIds) . ")")])
                ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED]);
            if ($request->getWholesalerId() > 0) {
                $query->andWhere(['wholesaler_id' => $request->getWholesalerId()]);
            }
            $products = $query->all();
            $product_list = [];
            foreach ($products as $item) {
                $data = $this->getProductBriefArray($item);
                // 商家名
                if (isset($winfo[$data['wholesaler_id']])) {
                    $onew = unserialize($winfo[$data['wholesaler_id']]);
                    $data['wholesaler_name'] = $onew['store_name'];
                } else {
                    Console::get()->log('用户的所属城市可能发生变更、供货商不存在导致供货商信息无法获取!', null, [__METHOD__], Console::ES_LEVEL_WARNING);
                }
                $data['purchased_qty'] = Tools::getPurchasedQty($customer->getCustomerId(), $customer->getCity(), $data['product_id']);
                if (isset($frequency[$data['product_id']])) {
                    $data['frequency'] = $frequency[$data['product_id']];
                }
                array_push($product_list, $data);
            }
            $responseData['product_list'] = $product_list;
            $response->setFrom(ToolsAbstract::pb_array_filter($responseData));
        }
        $response->setPages($productReportResponse->getPages());
        return $response;
    }

    /**
     * @param purchaseHistoryRequest $request
     * @param CustomerResponse $customer
     * @return ProductReportResponse
     */
    protected function getProductReport($request, $customer)
    {
        $header = new Header();
        $header->setRoute('sales.productReport');
        $header->setCustomerId($request->getCustomerId());
        $header->setSource(SourceEnum::MERCHANT);
//        $header->setTraceId($this->getTraceId());
        $productReportRequest = new ProductReportRequest();
        $filters = [
            ['key' => 'time_range', 'value' => sprintf('%sTO%s', date('Y-m-d', strtotime('-3month')), date('Y-m-d', strtotime('+1day')))],
        ];

        if ($request->getWholesalerId() > 0) {
            $filters = [];
        }

        if ($request->getCustomerId() > 0) {
            $filters[] = ['key' => 'customer_id', 'value' => $request->getCustomerId()];
        }
        if ($request->getCategoryId() > 0) {
            $filters[] = ['key' => 'category_id', 'value' => $request->getCategoryId()];
        }
        if ($request->getCategoryLevel() > 0) {
            $filters[] = ['key' => 'category_level', 'value' => $request->getCategoryLevel()];
        }
        if ($request->getWholesalerId() > 0) {
            $filters[] = ['key' => 'wholesaler_id', 'value' => $request->getWholesalerId()];
        } else {
            $wholesalerIds = MerchantResourceAbstract::getWholesalerIdsByAreaId($customer->getAreaId());
            $wholesalerIdString = implode(',', $wholesalerIds);
            $filters[] = ['key' => 'wholesaler_id', 'value' => $wholesalerIdString];
        }

        $pageNation = $request->getPagination();
        if ($pageNation) {
            $requestData = [
                'pagination' => [
                    'page' => $pageNation->getPage() ? $pageNation->getPage() : 1,
                ],
                'filters' => $filters,
            ];
        } else {
            $requestData = [
                'pagination' => [
                    'page' => 1,
                ],
                'filters' => $filters,
            ];
        }

        $productReportRequest->setFrom($requestData);
        /** @var Message $message */
        $message = Proxy::sendRequest($header, $productReportRequest);
        /** @var ProductReportResponse $response */
        $response = new ProductReportResponse();
        $response->parseFromString($message->getPackageBody());
        return $response;
    }

    protected function processGroupData($items, $groupSize)
    {
        $data = [];
        if (count($items) > 0 && count($items) % $groupSize == 0) {
            $i = 0;
            $obj = [];
            /** @var KeyValueItem $item */
            foreach ($items as $item) {
                $obj[$item->getKey()] = $item->getValue();
                if (++$i % $groupSize == 0) {
                    $data[] = $obj;
                    $obj = [];
                }
            }
        }
        return $data;
    }

    public static function request()
    {
        return new purchaseHistoryRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}