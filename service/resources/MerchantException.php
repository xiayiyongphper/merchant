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
class MerchantException extends Exception
{

    const MERCHANT_NOT_FOUND = 8001;
    const MERCHANT_NOT_FOUND_TEXT = '供应商账号不存在';
    const MERCHANT_INVALID_PASSWORD = 8002;
    const MERCHANT_INVALID_PASSWORD_TEXT = '密码错误';
    const MERCHANT_AUTH_TOKEN_EXPIRED = 8003;
    const MERCHANT_AUTH_TOKEN_EXPIRED_TEXT = '账号信息已过期，请重新登陆！';

    const MERCHANT_NOT_YOUR_ORDER = 8100;
    const MERCHANT_NOT_YOUR_ORDER_TEXT = '这不是你的订单！';

    const MERCHANT_ORDER_CANT_CONFIRM = 8200;
    const MERCHANT_ORDER_CANT_CONFIRM_TEXT = '无法确认订单！';
    const MERCHANT_ORDER_CANT_CLOSE = 8201;
    const MERCHANT_ORDER_CANT_CLOSE_TEXT = '无法拒绝订单！';
    const MERCHANT_ORDER_CANT_AGREE_CANCEL = 8203;
    const MERCHANT_ORDER_CANT_AGREE_CANCEL_TEXT = '无法同意申请！';
    const MERCHANT_ORDER_CANT_REJECT_CANCEL = 8204;
    const MERCHANT_ORDER_CANT_REJECT_CANCEL_TEXT = '无法拒绝申请！';

    const CUSTOMER_WHOLESALER_CITY_NOT_MATCH_TEXT = '用户所属城市与供货商所属城市不匹配，无法查看内容';
    const CUSTOMER_WHOLESALER_CITY_NOT_MATCH = 8205;

    public static function merchantNotFound()
    {
        throw new \Exception(self::MERCHANT_NOT_FOUND_TEXT, self::MERCHANT_NOT_FOUND);
    }

    public static function merchantPasswordError()
    {
        throw new \Exception(self::MERCHANT_INVALID_PASSWORD_TEXT, self::MERCHANT_INVALID_PASSWORD);
    }

    public static function merchantAuthTokenExpired()
    {
        throw new \Exception(self::MERCHANT_AUTH_TOKEN_EXPIRED_TEXT, self::MERCHANT_AUTH_TOKEN_EXPIRED);
    }

    public static function notYourOrder()
    {
        throw new \Exception(self::MERCHANT_NOT_YOUR_ORDER_TEXT, self::MERCHANT_NOT_YOUR_ORDER);
    }

    public static function orderCantConfirm()
    {
        throw new \Exception(self::MERCHANT_ORDER_CANT_CONFIRM_TEXT, self::MERCHANT_ORDER_CANT_CONFIRM);
    }

    public static function orderCantClose()
    {
        throw new \Exception(self::MERCHANT_ORDER_CANT_CLOSE_TEXT, self::MERCHANT_ORDER_CANT_CLOSE);
    }


    public static function orderCantAgreeCancel()
    {
        throw new \Exception(self::MERCHANT_ORDER_CANT_AGREE_CANCEL_TEXT, self::MERCHANT_ORDER_CANT_AGREE_CANCEL);
    }

    public static function orderCantRejectCancel()
    {
        throw new \Exception(self::MERCHANT_ORDER_CANT_REJECT_CANCEL_TEXT, self::MERCHANT_ORDER_CANT_REJECT_CANCEL);
    }

    const CONTRACTOR_INIT_ERROR = 9001;
    const CONTRACTOR_INIT_ERROR_TEXT = '业务员不存在';

    public static function contractorInitError()
    {
        throw new \Exception(self::CONTRACTOR_INIT_ERROR_TEXT, self::CONTRACTOR_INIT_ERROR);
    }

    const CONTRACTOR_PERMISSION_ERROR = 9004;
    const CONTRACTOR_PERMISSION_ERROR_TEXT = '无权访问该模块';

    public static function contractorPermissionError()
    {
        throw new \Exception(self::CONTRACTOR_PERMISSION_ERROR_TEXT, self::CONTRACTOR_PERMISSION_ERROR);
    }

    public static function customerWholesalerCityNotMatch()
    {
        throw new \Exception(self::CUSTOMER_WHOLESALER_CITY_NOT_MATCH_TEXT, self::CUSTOMER_WHOLESALER_CITY_NOT_MATCH);
    }



}
