<?php
namespace service\components;
/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-3
 * Time: 下午2:22
 */
class Events
{
    const CUSTOMER_SYS_NAME = 'customer';
    const ROUTE_SYS_NAME = 'route';
    const MERCHANT_SYS_NAME = 'merchant';
    const CORE_SYS_NAME = 'core';

    const EVENT_ORDER_NEW = 'order_new';
    const EVENT_ORDER_CONFIRM = 'order_confirm';
    const EVENT_ORDER_DECLINE = 'order_decline';
	const EVENT_ORDER_CANCEL = 'order_cancel';
    const EVENT_ORDER_APPLY_CANCEL = 'order_apply_cancel';
    const EVENT_ORDER_AGREE_CANCEL = 'order_agree_cancel';
    const EVENT_ORDER_REJECT_CANCEL = 'order_reject_cancel';
	const EVENT_ORDER_COMPLETE = 'order_complete';
	const EVENT_ORDER_REJECT = 'order_reject';
    const EVENT_ES_ORDER_REPORT = 'es_order_report';

    const EVENT_PUSH_NOTIFICATION = 'push_notification';

    public static function getCustomerEventName($eventName)
    {
        return self::getEventName(self::CUSTOMER_SYS_NAME, $eventName);
    }

    public static function getRouteEventName($eventName)
    {
        return self::getEventName(self::ROUTE_SYS_NAME, $eventName);
    }

    public static function getMerchantEventName($eventName)
    {
        return self::getEventName(self::MERCHANT_SYS_NAME, $eventName);
    }

    public static function getCoreEventName($eventName)
    {
        return self::getEventName(self::CORE_SYS_NAME, $eventName);
    }

    protected static function getEventName($sys, $eventName)
    {
        return sprintf('%s_msg.%s', $sys, $eventName);
    }
}