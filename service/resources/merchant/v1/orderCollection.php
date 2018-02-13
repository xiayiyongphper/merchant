<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/27
 * Time: 12:24
 */

namespace service\resources\merchant\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use service\components\Tools;
use framework\data\Pagination;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderCollectionResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class updateItems
 * @package service\resources\customers
 */
class orderCollection extends MerchantResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data){

        $request = self::request();
        $request->parseFromString($data);
        $merchant = $this->_initMerchant($request);
        // 供应商id
        $wholesalerId = $merchant->getWholesalerId();

        $response = new OrderCollectionResponse();
        $pageSize = self::DEFAULT_PAGE_SIZE;
        $page = $request->getPage() ? $request->getPage() : 1;
        $query = SalesFlatOrder::find();
        $orderTableName = SalesFlatOrder::tableName();
        // 关键字搜索
        $keyword = $request->getKeyword();
        $condition = ["{$orderTableName}.wholesaler_id" => $wholesalerId];
        if($keyword){
            // 搜订单号
            $keyword_condition = ['like', 'increment_id', $keyword];
            // 搜电话号
            $keyword_condition = ['or', $keyword_condition,
                ['like', 'phone', $keyword],
            ];
            // 搜超市名称
            $keyword_condition = ['or', $keyword_condition,
                ['like', 'store_name', $keyword],
            ];
            // 组合之前的
            $condition = ['and', $condition, $keyword_condition];
        }
        //Tools::log($condition, 'server.log');
        $query->where($condition);
        //$query->joinWith('item');
        $query->joinWith('orderstatus');
        switch ($request->getState()) {
            case 'new':
                /** 新订单,待接单 */
                $status = array(
                    SalesFlatOrder::STATUS_PROCESSING,
                );
                $orderBy = ["$orderTableName.created_at"=>SORT_DESC];
                break;
            case 'shipping':
                /** 发货中 */
                $status = array(
                    SalesFlatOrder::STATUS_PROCESSING_RECEIVE,
                );
                $orderBy = ["$orderTableName.`updated_at`"=>SORT_DESC];
                break;
            case 'apply_cancel':
                /** 申请取消 */
                $status = array(
                    SalesFlatOrder::STATUS_HOLDED,
                );
                $orderBy = ["$orderTableName.`updated_at`"=>SORT_DESC];
                break;
            case 'all':
                /** 全部 */
                $status = false;
                $orderBy = ["$orderTableName.`updated_at`"=>SORT_DESC];
                break;
            default:
                $status = false;
                $orderBy = ["$orderTableName.`created_at`"=>SORT_DESC];
                break;
        }
        if (is_array($status)) {
            $query->andWhere(["$orderTableName.`status`" => $status]);
        }

        if ($time = $request->getTime()) {
            $time = date("Y-m-d H:i:s");
            $query->andWhere(['<', "$orderTableName.created_at", $time]);
        }

        $countQuery = clone $query;

        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);

        $orders = $query->offset($pages->getOffset())
            ->limit($pages->getLimit())
            ->orderBy($orderBy)
            ->all();
        //
        $responseArray = [
            'pagination' => [
                'total_count' => $pages->getTotalCount(),
                'page' => $pages->getCurPage(),
                'last_page' => $pages->getLastPageNumber(),
            ],
        ];
        $items = [];

        foreach ($orders as $_order) {
            // Tools::log($_order, 'server.log');
            /** @var SalesFlatOrder $_order */
            if ($_order->orderstatus->label) {
                $statusLabel = $_order->orderstatus->label;
            } else {
                $statusLabel = $_order->status;
            }
            $date = new Date();
            $order = [
                'order_id' => $_order->getPrimaryKey(),
                'status' => $_order->status,
                'status_label' => $statusLabel,
                'wholesaler_id' => $_order->wholesaler_id,
                'wholesaler_name' => $_order->wholesaler_name,
                'store_name' => $_order->store_name,
                'payment_method' => $_order->payment_method,
                //'coupon_code'=>$_order['coupon_code'],
                'increment_id' => $_order->increment_id,
                'grand_total' => $_order->grand_total,
                'created_at' => $date->date('Y-m-d H:i:s', $_order->created_at),
                'auto_script_tip' => $_order->getAutoScriptTip(),
            ];
            $orderItems = [];
            $total_qty_ordered = 0;
            foreach ($_order->getItemsCollection() as $_orderItem) {
                $orderItems[] = [
                    'item_id' => $_orderItem['item_id'],
                    'name' => $_orderItem['name'],
                    'price' => round($_orderItem['price'],2),
                    'qty' => $_orderItem['qty'],
                    'image' => $_orderItem['image'],
                ];
                $total_qty_ordered += $_orderItem['qty'];
            }
            $order['total_qty_ordered'] = $total_qty_ordered;
            if (count($orderItems) > 0) {
                $order['items'] = $orderItems;
            }
            $items[] = $order;
        }
        if (count($items) > 0) {
            $responseArray['items'] = $items;
        }
        $response->setFrom(Tools::pb_array_filter($responseArray));
        return $response;

    }

    public static function request(){
        return new OrderCollectionRequest();
    }

    public static function response(){
        return new OrderCollectionResponse();
    }
}