<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/27
 * Time: 12:24
 */

namespace service\resources\merchant\v1;

use common\models\SalesFlatOrder;
use service\components\Events;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\common\OrderAction;
use service\resources\Exception;
use service\resources\MerchantException;
use service\resources\MerchantResourceAbstract;


class orderAgreeCancel extends MerchantResourceAbstract
{

    public function run($data)
    {
        /** @var \service\message\sales\OrderDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $this->_initMerchant($request);
        //用户权限校验
        $response = self::response();
        if (!$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()
            ->joinWith('item')
            ->where(['entity_id'=>$request->getOrderId()])->one();
        //Tools::log($order,'server.txt');
        if (!$order || !$order->entity_id) {
            Exception::orderNotExisted();
        }
        if ($order->wholesaler_id != $request->getWholesalerId()) {
            MerchantException::notYourOrder();
        }

        // 检查能同意取消订单
        if(!$order->canUnhold()){
            MerchantException::orderCantAgreeCancel();
        }

        // 同意后取消
        $order->setState(SalesFlatOrder::STATE_CANCELED, SalesFlatOrder::STATUS_CANCELED);
        $order->setCompletedAt();
        $order->save();

        //// 推送
        //Proxy::sendMessage(Events::getCustomerEventName(Events::EVENT_ORDER_AGREE_CANCEL), array(
        //    'name'=>Events::EVENT_ORDER_AGREE_CANCEL,
        //    'data'=>array(
        //        'customer_id'=>$order->customer_id,
        //        'order_id'=>$order->entity_id,
        //    ),
        //));
		//
        //// 退优惠券
        //Proxy::sendMessage(Events::getCoreEventName(Events::EVENT_ORDER_AGREE_CANCEL), array(
        //    'name' => Events::EVENT_ORDER_AGREE_CANCEL,
        //    'data' => array(
        //        'customer_id' => $order->customer_id,
        //        'order_id' => $order->entity_id,
        //    ),
        //));

        $responseData = [
            'order_id' => $order->entity_id,
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
            'auto_script_tip' => $order->getAutoScriptTip(),
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new OrderAction();
    }

    public static function response()
    {
        return new Order();
    }
}