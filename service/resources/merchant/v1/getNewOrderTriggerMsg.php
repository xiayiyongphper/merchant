<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/10/13
 * Time: 17:51
 */

namespace service\resources\merchant\v1;


use common\models\LeMerchantTriggerMsg;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\getNewOrderTriggerMsgRequest;
use service\message\merchant\getNewOrderTriggerMsgResponse;
use service\resources\MerchantResourceAbstract;

class getNewOrderTriggerMsg extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getNewOrderTriggerMsgRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);

        $messages = LeMerchantTriggerMsg::findAll([
            'customer_id' => $customer->getCustomerId(),
            'status' => LeMerchantTriggerMsg::STATUS_UNREAD,
            'trigger_type' => 4
        ]);

        $response = self::response();
        if (!$messages) {
            return $response;
        }

        $messageIds = [];
        $balanceSum = 0;
        $allCouponDiscount = 0;
        $couponCount = 0;
        foreach ($messages as $message) {
            $messageIds[] = $message->entity_id;
            if ($message->type == LeMerchantTriggerMsg::TYPE_BALANCE) {
                if (isset($message->result['balance'])) {
                    $balanceSum += $message->result['balance'];
                }
            } elseif ($message->type == LeMerchantTriggerMsg::TYPE_COUPON) {
                if (isset($message->result['coupons']) && is_array($message->result['coupons'])) {
                    foreach ($message->result['coupons'] as $couponId => $couponDiscount) {
                        $allCouponDiscount += $couponDiscount;
                    }
                    $couponCount++;
                }
            }
        }

        $result = [
            'balance' => ($balanceSum > 0 ? $balanceSum . '元' : ''),
            'coupon_discount' => ($allCouponDiscount > 0 ? $allCouponDiscount . '元' : ''),
            'coupon_count' => ($couponCount > 0 ? $couponCount : '')
        ];

        LeMerchantTriggerMsg::updateAll(
            [
                'status' => LeMerchantTriggerMsg::STATUS_READ,
                'updated_at' => ToolsAbstract::getDate()->date(),
            ],
            ['entity_id' => $messageIds]
        );

        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    /**
     * @return getNewOrderTriggerMsgRequest
     */
    public static function request()
    {
        return new getNewOrderTriggerMsgRequest();
    }

    /**
     * @return getNewOrderTriggerMsgResponse
     */
    public static function response()
    {
        return new getNewOrderTriggerMsgResponse();
    }
}