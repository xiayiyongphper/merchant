<?php
namespace service\resources;
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Api2
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * API exception
 *
 * @category   Mage
 * @package    Mage_Api2
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Exception
{
    const DEFAULT_ERROR_CODE = 0;//default error code, none exception
    const SERVICE_NOT_AVAILABLE = 503;
    const SERVICE_NOT_AVAILABLE_TEXT = '系统错误，请稍后重试！';
    const SYSTEM_MAINTENANCE = 5003;
    const SYSTEM_MAINTENANCE_TEXT = '系统维护中，请稍后重试';
    const OFFLINE = 9999;
    const SYSTEM_NOT_FOUND = 404;
    const SYSTEM_NOT_FOUND_TEXT = '找不到相关信息';
    const RESOURCE_NOT_FOUND_TEXT = '找不到相关资源';
    const RESOURCE_NOT_FOUND = 1001;
    const INVALID_REQUEST_ROUTE_TEXT = '非法的请求';
    const INVALID_REQUEST_ROUTE = 1002;
    /**
     * customer exception code start with 2
     */
    /**
     * customer not found
     */
    const CUSTOMER_NOT_FOUND = 2001;
    const CUSTOMER_NOT_FOUND_TEXT = '用户不存在';
    /**
     * customer auth token expired
     */
    const CUSTOMER_AUTH_TOKEN_EXPIRED = 2002;
    const CUSTOMER_AUTH_TOKEN_EXPIRED_TEXT = '用户信息已过期，请重新登陆！';
    const CUSTOMER_SHOPPING_CART_EMPTY = 2003;
    const CUSTOMER_SHOPPING_CART_EMPTY_TEXT = '购物车为空，请先添加商品！';
    const CUSTOMER_PHONE_INVALID = 2004;
    const CUSTOMER_PHONE_INVALID_TEXT = '手机号码有误！';
    const CUSTOMER_PHONE_ALREADY_REGISTERED = 2005;
    const CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT = '该手机号码已注册！';
    const CUSTOMER_PHONE_NOT_REGISTERED = 2011;
    const CUSTOMER_PHONE_NOT_REGISTERED_TEXT = '该手机号码未注册！';
    const CUSTOMER_USERNAME_ALREADY_REGISTERED = 2005;
    const CUSTOMER_PHONE_ALREADY_BINDING_TEXT = '该手机号码已被绑定！';
    const CUSTOMER_PHONE_ALREADY_BINDING = 2010;
    const CUSTOMER_USERNAME_ALREADY_REGISTERED_TEXT = '该用户名已注册！';
    const CUSTOMER_SMS_TYPE_INVALID = 2006;
    const CUSTOMER_SMS_TYPE_INVALID_TEXT = '短信验证码类型错误!';
    const CUSTOMER_BALANCE_INSUFFICIENT = 2007;
    const CUSTOMER_BALANCE_INSUFFICIENT_TEXT = '钱包余额不足';
    const CUSTOMER_INVITE_CODE_INVALID = 2008;
    const CUSTOMER_INVITE_CODE_INVALID_TEXT = '邀请码不正确';
    const CUSTOMER_DEVICE_RESTRICT = 2009;
    const CUSTOMER_DEVICE_RESTRICT_TEXT = '亲，您的账号太多啦';


    /**
     * store exception code start with 3
     */

    /**
     * store not found
     */
    const STORE_NOT_FOUND = 3001;
    const STORE_NOT_FOUND_TEXT = '店铺不存在';

    const STORE_DEFAULT_NOT_FOUND = 3002;
    const STORE_DEFAULT_NOT_FOUND_TEXT = '您所在的区域尚未开通服务，我们会尽快开通，敬请期待！';
    const MULTI_STORE_NOT_ALLOWED = 3003;
    const MULTI_STORE_NOT_ALLOWED_TEXT = '暂不支持多个店铺订单';
    const STORE_OUT_OF_DELIVERY_RANGE = 3004;
    const STORE_OUT_OF_DELIVERY_RANGE_TEXT = '对不起，您的地址不在商家配送区域内！';
    const STORE_OUT_OF_DELIVERY_CAPACITY = 3005;
    const STORE_OUT_OF_DELIVERY_CAPACITY_TEXT = '一大波订单正在袭击小蜂侠，机智的你换个时间段送货更通畅哦~';

    const PRODUCT_NOT_IN_DELIVER_RANGE = 3006;
    const PRODUCT_NOT_IN_DELIVER_RANGE_TEXT = '商品不在当前区域';

    const WHOLESALER_NOT_DELIVER_AREA = 3007;
    const WHOLESALER_NOT_DELIVER_AREA_TEXT = '供应商不配送当前区域';

    const WHOLESALER_STATUS_STOP = 3008;
    const WHOLESALER_STATUS_STOP_TEXT = '供应商已停止营业';


    /**
     * CATALOG EXCEPTION
     */
    const CATALOG_PRODUCT_NOT_FOUND = 4001;
    const CATALOG_PRODUCT_NOT_FOUND_TEXT = '不存在此商品';
    const CATALOG_PRODUCT_CITY_ID_NOT_FOUND = 4002;
    const CATALOG_PRODUCT_SOLD_OUT = 4003;
    const CATALOG_PRODUCT_SOLD_OUT_TEXT = '下手太慢啦，该商品已抢光';
    const NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT1 = '下手太慢啦，%s已抢光';
    const NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT2 = '下手太慢啦，%s库存不足';

    const SEARCH_KEYWORDS_INVALID = 4004;
    const SEARCH_KEYWORDS_INVALID_TEXT = '搜索关键字中包含非法字符';

    const SEARCH_RESULT_EMPTY = 4005;
    const SEARCH_RESULT_EMPTY_TEXT = '搜索结果为空';

    const TOPIC_NOT_EXIST = 4006;
    const TOPIC_NOT_EXIST_TEXT = '专题不存在';
    
    /**
     * SALES EXCEPTION
     */
    const SALES_ORDER_NOT_EXISTED = 5001;
    const SALES_ORDER_NOT_EXISTED_TEXT = '该订单不存在';
    const SALES_PAYMENT_METHOD_NOT_SUPPORTED = 5002;
    const SALES_PAYMENT_METHOD_NOT_SUPPORTED_TEXT = '暂不支持该支付方式';
    const SALES_COUPON_CODE_INVALID = 5003;
    const SALES_COUPON_CODE_INVALID_TEXT = '兑换码无效';
    const SALES_COUPON_CODE_EXPIRED = 5004;
    const SALES_COUPON_CODE_EXPIRED_TEXT = '兑换码已过有效期';
    const SALES_COUPON_CODE_EXCEED_USAGE_LIMIT = 5005;
    const SALES_COUPON_CODE_EXCEED_USAGE_LIMIT_TEXT = '兑换码已抢光';
    const SALES_COUPON_CODE_EXCEED_CUSTOMER_USAGE_LIMIT = 5006;
    const SALES_COUPON_CODE_EXCEED_CUSTOMER_USAGE_LIMIT_TEXT = '您的兑换次数已达上限';
    const SALES_ORDER_CANNOT_CANCELED = 5007;
    const SALES_ORDER_CANNOT_CANCELED_TEXT = '订单不能取消';
    const SALES_ORDER_CANNOT_PICKING_SUCCESS = 5008;
    const SALES_ORDER_CANNOT_PICKING_SUCCESS_TEXT = '订单不能拣货成功';
    const SALES_ORDER_CANNOT_START_PICKING = 5009;
    const SALES_ORDER_CANNOT_START_PICKING_TEXT = '订单不能操作开始拣货';
    const SALES_ORDER_CANNOT_DELIVERY_SUCCESS = 5010;
    const SALES_ORDER_CANNOT_DELIVERY_SUCCESS_TEXT = '订单不能操作配送成功';
    const SALES_ORDER_CANNOT_PICKUP_SUCCESS = 5011;
    const SALES_ORDER_CANNOT_PICKUP_SUCCESS_TEXT = '订单不能操作成功取件';
    const SALES_ORDER_CANNOT_PICKING_FAILURE = 5012;
    const SALES_ORDER_CANNOT_PICKING_FAILURE_TEXT = '订单不能操作拣货失败';
    const SALES_ORDER_CANNOT_RECEIPT_CONFIRM = 5013;
    const SALES_ORDER_CANNOT_RECEIPT_CONFIRM_TEXT = '订单不能操作确认收货';
    const SALES_ORDER_CANNOT_REVIEW = 5014;
    const SALES_ORDER_CANNOT_REVIEW_TEXT = '订单不能操作评价';
    const SALES_ORDER_CANNOT_APPLY_FOR_REFUND = 5015;
    const SALES_ORDER_CANNOT_APPLY_FOR_REFUND_TEXT = '订单不能申请退款';
    const SALES_ORDER_REFUND_TYPE_INVALID = 5016;
    const SALES_ORDER_REFUND_TYPE_INVALID_TEXT = '请选择退款理由';
    const SALES_ORDER_PAYMENT_NOT_ALLOWED = 5017;
    const SALES_ORDER_PAYMENT_NOT_ALLOWED_TEXT = '该订单当前无法付款';
    const SALES_ORDER_CREATE_RISK_DANGER_CODE = 5018;
    const SALES_ORDER_CREATE_RISK_DANGER_CODE_TEXT = '您可能存在违规操作，无法下单';
    const SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT = 5019;
    const SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT_TEXT = '不满足最低起送金额：%s';
    const SALES_ORDER_CANNOT_DECLINE = 5020;
    const SALES_ORDER_CANNOT_DECLINE_TEXT = '订单不能操作拒单';
    const SALES_ORDER_CANNOT_UNHOLD = 5021;
    const SALES_ORDER_CANNOT_UNHOLD_TEXT = '订单不能撤销取消';
    const SALES_ORDER_CANNOT_HOLD = 5022;
    const SALES_ORDER_CANNOT_HOLD_TEXT = '订单不能取消';
	const SALES_ORDER_CANNOT_CLOSE = 5023;
	const SALES_ORDER_CANNOT_CLOSE_TEXT = '订单不能操作拒单';

    /**
     * Sales Rule Exception
     */
    const SALES_RULE_COUPON_NOT_EXISTED = 6001;
    const SALES_RULE_COUPON_NOT_EXISTED_TEXT = '优惠券不存在';
    const SALES_RULE_COUPON_NUMBER_REACH_THE_MAXIMUM_LIMIT = 6002;
    const SALES_RULE_COUPON_NUMBER_REACH_THE_MAXIMUM_LIMIT_TEXT = '优惠券已经被领光了';
    const SALES_RULE_COUPON_ONLY_NEW_CUSTOMER_REDEEM = 6003;
    const SALES_RULE_COUPON_ONLY_NEW_CUSTOMER_REDEEM_TEXT = '抱歉，此券仅限未下过订单的新用户领取使用！';
    const SALES_RULE_COUPON_CODE_EXCEED_USAGE_LIMIT = 6004;
    const SALES_RULE_COUPON_CODE_EXCEED_USAGE_LIMIT_TEXT = '该类优惠券您的兑换次数已达上限';
    const SALES_RULE_COUPON_CODE_DEVICE_ID_NOT_EXIST = 6005;
    const SALES_RULE_COUPON_CODE_DEVICE_ID_NOT_EXIST_TEXT = '无法获取设备号';


    /**
     * COURIER EXCEPTION
     */
    const COURIER_NOT_EXISTED = 7001;
    const COURIER_NOT_EXISTED_TEXT = '快递员不存在';
    const COURIER_ORDER_NOT_EXISTED = 7002;
    const COURIER_ORDER_NOT_EXISTED_TEXT = '订单不存在';
    const COURIER_ORDER_OPERATION_NOT_PERMITTED = 7003;
    const COURIER_ORDER_OPERATION_NOT_PERMITTED_TEXT = '您没有权限操作该订单！';
    const COURIER_PASSWORD_NOT_MATCH = 7004;
    const COURIER_PASSWORD_NOT_MATCH_TEXT = '用户名与密码不匹配';
    const COURIER_LOGIN_TYPE_NOT_PERMITTED = 7005;
    const COURIER_LOGIN_TYPE_NOT_PERMITTED_TEXT = '登陆类型不对！';
    const PICKER_GrRAB_A_SINGLE_NOT_PERMITTED = 7006;
    const PICKER_GrRAB_A_SINGLE_NOT_PERMITTED_TEXT = '订单已经被抢，请重新刷新，选择其他订单！';
    const PICKER_TYPE_NOT_PERMITTED = 7007;
    const PICKER_TYPE_NOT_PERMITTED_TEXT = '账户不是拣货员类型';
    const PICKER_SUCCESS_NOT_PERMITTED = 7008;
    const PICKER_SUCCESS_NOT_PERMITTED_TEXT = '拣货中的订单才能此操作。';
    const COURIER_PICKER_SUCCESS_NOT_PERMITTED = 7009;
    const COURIER_PICKER_SUCCESS_NOT_PERMITTED_TEXT = '订单仍未拣货完成。';
    const COURIER_ORDER_CANNOT_DELIVERY_FAILURE = 7010;
    const COURIER_ORDER_CANNOT_DELIVERY_FAILURE_TEXT = '订单不能配送失败';

    const SYSTEM_NOT_SUPPORT = 9001;
    const SYSTEM_NOT_SUPPORT_TEXT = '当前系统不支持';

    const MESSAGE_SUCCESS = '操作成功';
    const MESSAGE_COUPON_CODE_INVALID = '该优惠券不可使用或已过期';
    const MESSAGE_BALANCE_IS_ZERO_CANNOT_USE = '钱包余额为0，不能使用余额';


    /*
     * EVENT EXCEPTION
     */
    const EVENT_NOT_FOUND = 10001;
    const EVENT_NOT_FOUND_TEXT = '场次不存在';

    public static function paymentMethodNotSupported()
    {
        throw new \Exception(self::SALES_PAYMENT_METHOD_NOT_SUPPORTED_TEXT, self::SALES_PAYMENT_METHOD_NOT_SUPPORTED);
    }

    public static function orderNotExisted()
    {
        throw new \Exception(self::SALES_ORDER_NOT_EXISTED_TEXT, self::SALES_ORDER_NOT_EXISTED);
    }

    public static function searchKeyWordInvalid()
    {
        throw new \Exception(self::SEARCH_KEYWORDS_INVALID_TEXT, self::SEARCH_KEYWORDS_INVALID);
    }

    public static function searchResultEmpty()
    {
        throw new \Exception(self::SEARCH_RESULT_EMPTY_TEXT, self::SEARCH_RESULT_EMPTY);
    }

    public static function pickerGrabASingleNotPermitted()
    {
        throw new \Exception(self::PICKER_GrRAB_A_SINGLE_NOT_PERMITTED_TEXT, self::PICKER_GrRAB_A_SINGLE_NOT_PERMITTED);
    }

    public static function pickupSuccessNotPermitted()
    {
        throw new \Exception(self::COURIER_PICKER_SUCCESS_NOT_PERMITTED_TEXT, self::COURIER_PICKER_SUCCESS_NOT_PERMITTED);
    }

    public static function canPickingSuccessNotPermitted()
    {
        throw new \Exception(self::PICKER_SUCCESS_NOT_PERMITTED_TEXT, self::PICKER_SUCCESS_NOT_PERMITTED);
    }

    public static function pickerTypeNotPermitted()
    {
        throw new \Exception(self::PICKER_TYPE_NOT_PERMITTED_TEXT, self::PICKER_TYPE_NOT_PERMITTED);
    }

    public static function customerNotExisted()
    {
        throw new \Exception(self::CUSTOMER_NOT_FOUND_TEXT, self::CUSTOMER_NOT_FOUND);
    }

    public static function customerPhoneInvalid()
    {
        throw new \Exception(self::CUSTOMER_PHONE_INVALID_TEXT, self::CUSTOMER_PHONE_INVALID);
    }

    public static function customerPhoneAlreadyRegistered()
    {
        throw new \Exception(self::CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT, self::CUSTOMER_PHONE_ALREADY_REGISTERED);
    }

    public static function customerPhoneNotRegistered()
    {
        throw new \Exception(self::CUSTOMER_PHONE_NOT_REGISTERED_TEXT, self::CUSTOMER_PHONE_NOT_REGISTERED);
    }

    public static function customerPhoneAlreadyBinding()
    {
        throw new \Exception(self::CUSTOMER_PHONE_ALREADY_BINDING_TEXT, self::CUSTOMER_PHONE_ALREADY_BINDING);
    }

    public static function customerUserNameAlreadyRegistered()
    {
        throw new \Exception(self::CUSTOMER_USERNAME_ALREADY_REGISTERED_TEXT, self::CUSTOMER_USERNAME_ALREADY_REGISTERED);
    }

    public static function customerSmsTypeInvalid()
    {
        throw new \Exception(self::CUSTOMER_SMS_TYPE_INVALID_TEXT, self::CUSTOMER_SMS_TYPE_INVALID);
    }

    public static function customerInviteCodeInvalid()
    {
        throw new \Exception(self::CUSTOMER_INVITE_CODE_INVALID_TEXT, self::CUSTOMER_INVITE_CODE_INVALID);
    }

    public static function storeNotExisted()
    {
        throw new \Exception(self::STORE_NOT_FOUND_TEXT, self::STORE_NOT_FOUND);
    }

    public static function storeDefaultNotExisted()
    {
        throw new \Exception(self::STORE_DEFAULT_NOT_FOUND_TEXT, self::STORE_DEFAULT_NOT_FOUND);
    }

    public static function offline($text)
    {
        throw new \Exception($text, self::OFFLINE);
    }

    public static function systemNotSupport()
    {
        throw new \Exception(self::SYSTEM_NOT_SUPPORT_TEXT, self::SYSTEM_NOT_SUPPORT);
    }

    public static function resourceNotFound()
    {
        throw new \Exception(self::RESOURCE_NOT_FOUND_TEXT, self::RESOURCE_NOT_FOUND);
    }

    public static function invalidRequestRoute()
    {
        throw new \Exception(self::INVALID_REQUEST_ROUTE_TEXT, self::INVALID_REQUEST_ROUTE);
    }

    public static function multiStoreNotAllowed()
    {
        throw new \Exception(self::MULTI_STORE_NOT_ALLOWED_TEXT, self::MULTI_STORE_NOT_ALLOWED);
    }

    public static function systemNotFound()
    {
        throw new \Exception(self::SYSTEM_NOT_FOUND_TEXT, self::SYSTEM_NOT_FOUND);
    }

    public static function emptyShoppingCart()
    {
        throw new \Exception(self::CUSTOMER_SHOPPING_CART_EMPTY_TEXT, self::CUSTOMER_SHOPPING_CART_EMPTY);
    }

    public static function outOfDeliveryRange()
    {
        throw new \Exception(self::STORE_OUT_OF_DELIVERY_RANGE_TEXT, self::STORE_OUT_OF_DELIVERY_RANGE);
    }

    public static function outOfDeliveryCapacity()
    {
        throw new \Exception(self::STORE_OUT_OF_DELIVERY_CAPACITY_TEXT, self::STORE_OUT_OF_DELIVERY_CAPACITY);
    }

    public static function catalogProductNotFound()
    {
        throw new \Exception(self::CATALOG_PRODUCT_NOT_FOUND_TEXT, self::CATALOG_PRODUCT_NOT_FOUND);
    }

    public static function catalogProductSoldOut()
    {
        throw new \Exception(self::CATALOG_PRODUCT_SOLD_OUT_TEXT, self::CATALOG_PRODUCT_SOLD_OUT);
    }

    public static function catalogProductSoldOut1($productName)
    {

        throw new \Exception(sprintf(self::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT1, $productName), self::CATALOG_PRODUCT_SOLD_OUT);
    }

    public static function catalogProductSoldOut2($productName)
    {

        throw new \Exception(sprintf(self::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT2, $productName), self::CATALOG_PRODUCT_SOLD_OUT);
    }

    public static function balanceInsufficient()
    {
        throw new \Exception(self::CUSTOMER_BALANCE_INSUFFICIENT_TEXT, self::CUSTOMER_BALANCE_INSUFFICIENT);
    }

    public static function salesCouponCodeInvalid()
    {
        throw new \Exception(self::SALES_COUPON_CODE_INVALID_TEXT, self::SALES_COUPON_CODE_INVALID);
    }

    public static function salesCouponCodeExpired()
    {
        throw new \Exception(self::SALES_COUPON_CODE_EXPIRED_TEXT, self::SALES_COUPON_CODE_EXPIRED);
    }

    public static function salesCouponCodeExceedUsageLimit()
    {
        throw new \Exception(self::SALES_COUPON_CODE_EXCEED_USAGE_LIMIT_TEXT, self::SALES_COUPON_CODE_EXCEED_USAGE_LIMIT);
    }

    public static function salesCouponCodeExceedCustomerUsageLimit()
    {
        throw new \Exception(self::SALES_COUPON_CODE_EXCEED_CUSTOMER_USAGE_LIMIT_TEXT, self::SALES_COUPON_CODE_EXCEED_CUSTOMER_USAGE_LIMIT);
    }

    public static function salesOrderCanNotCanceled()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_CANCELED_TEXT, self::SALES_ORDER_CANNOT_CANCELED);
    }

    public static function salesOrderCanNotUnHold()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_UNHOLD_TEXT, self::SALES_ORDER_CANNOT_UNHOLD);
    }

    public static function salesOrderCanNotHold()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_HOLD_TEXT, self::SALES_ORDER_CANNOT_HOLD);
    }

    public static function salesOrderCanNotPickingSuccess()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_PICKING_SUCCESS_TEXT, self::SALES_ORDER_CANNOT_PICKING_SUCCESS);
    }

    public static function salesOrderCanNotStartPicking()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_START_PICKING_TEXT, self::SALES_ORDER_CANNOT_START_PICKING);
    }

    public static function salesOrderCanNotDeliverySuccess()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_DELIVERY_SUCCESS_TEXT, self::SALES_ORDER_CANNOT_DELIVERY_SUCCESS);
    }

    public static function salesOrderCanNotPickupSuccess()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_PICKUP_SUCCESS_TEXT, self::SALES_ORDER_CANNOT_PICKUP_SUCCESS);
    }

    public static function salesOrderCanNotPickingFailure()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_PICKING_FAILURE_TEXT, self::SALES_ORDER_CANNOT_PICKING_FAILURE);
    }

    public static function salesOrderCanNotReceiptConfirm()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_RECEIPT_CONFIRM_TEXT, self::SALES_ORDER_CANNOT_RECEIPT_CONFIRM);
    }

    public static function salesOrderCanNotReview()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_REVIEW_TEXT, self::SALES_ORDER_CANNOT_REVIEW);
    }

    public static function salesOrderCanNotApplyForRefund()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_APPLY_FOR_REFUND_TEXT, self::SALES_ORDER_CANNOT_APPLY_FOR_REFUND);
    }

	public static function salesOrderCanNotClose()
	{
		throw new \Exception(self::SALES_ORDER_CANNOT_CLOSE_TEXT, self::SALES_ORDER_CANNOT_CLOSE);
	}

    public static function salesOrderCanNotDecline()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_DECLINE_TEXT, self::SALES_ORDER_CANNOT_DECLINE);
    }

    /**
     * 优惠券已经被领光了
     * @throws \Exception
     */
    public static function salesRuleCouponTakeOut()
    {
        throw new \Exception(self::SALES_RULE_COUPON_NUMBER_REACH_THE_MAXIMUM_LIMIT_TEXT, self::SALES_RULE_COUPON_NUMBER_REACH_THE_MAXIMUM_LIMIT);
    }

    public static function customerAuthTokenExpired()
    {
        throw new \Exception(self::CUSTOMER_AUTH_TOKEN_EXPIRED_TEXT, self::CUSTOMER_AUTH_TOKEN_EXPIRED);
    }

    public static function salesOrderRefundTypeInvalid()
    {
        throw new \Exception(self::SALES_ORDER_REFUND_TYPE_INVALID_TEXT, self::SALES_ORDER_REFUND_TYPE_INVALID);
    }

    public static function serviceNotAvailable()
    {
        throw new \Exception(self::SERVICE_NOT_AVAILABLE_TEXT, self::SERVICE_NOT_AVAILABLE);
    }

    public static function systemMaintenance()
    {
        throw new \Exception(self::SYSTEM_MAINTENANCE_TEXT, self::SYSTEM_MAINTENANCE);
    }

    public static function salesPaymentNotAllowed()
    {
        throw new \Exception(self::SALES_ORDER_PAYMENT_NOT_ALLOWED_TEXT, self::SALES_ORDER_PAYMENT_NOT_ALLOWED);
    }

    public static function productQtyLow($message)
    {
        throw new \Exception($message, self::CATALOG_PRODUCT_SOLD_OUT);
    }

    public static function salesRuleCouponOnlyNewCustomerRedeem()
    {
        throw new \Exception(self::SALES_RULE_COUPON_ONLY_NEW_CUSTOMER_REDEEM_TEXT, self::SALES_RULE_COUPON_ONLY_NEW_CUSTOMER_REDEEM);
    }

    /**
     * 同规则领取次数限制
     *
     * @throws \Exception
     */
    public static function salesRuleUsageExceedLimit()
    {
        throw new \Exception(self::SALES_RULE_COUPON_CODE_EXCEED_USAGE_LIMIT_TEXT, self::SALES_RULE_COUPON_CODE_EXCEED_USAGE_LIMIT);
    }

    /**
     * 设备号不存在不让领取优惠券
     *
     * @throws \Exception
     */
    public static function DeviceIdNotExist()
    {
        throw new \Exception(self::SALES_RULE_COUPON_CODE_DEVICE_ID_NOT_EXIST_TEXT, self::SALES_RULE_COUPON_CODE_DEVICE_ID_NOT_EXIST);
    }

    /**
     * 可能存在违规下单操作
     *
     * @throws \Exception
     */
    public static function createOrdersRiskDanger()
    {
        throw new \Exception(self::SALES_ORDER_CREATE_RISK_DANGER_CODE_TEXT, self::SALES_ORDER_CREATE_RISK_DANGER_CODE);
    }

    public static function notSatisfyMinTradeAmount($amount)
    {
        throw new \Exception(sprintf(self::SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT_TEXT, $amount), self::SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT);
    }

    public static function topicNotExist()
    {
        throw new \Exception(self::TOPIC_NOT_EXIST_TEXT,self::TOPIC_NOT_EXIST);
    }

    public static function productNotInDeliveryRange()
    {
        throw new \Exception( self::PRODUCT_NOT_IN_DELIVER_RANGE_TEXT,self::PRODUCT_NOT_IN_DELIVER_RANGE);
    }

    public static function wholesalerNotDeliveryArea()
    {
        throw new \Exception( self::WHOLESALER_NOT_DELIVER_AREA_TEXT,self::WHOLESALER_NOT_DELIVER_AREA);
    }

    public static function wholesalerStatusStop()
    {
        throw new \Exception( self::WHOLESALER_STATUS_STOP_TEXT,self::WHOLESALER_STATUS_STOP);
    }

}
