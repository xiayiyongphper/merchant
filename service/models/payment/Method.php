<?php
namespace service\models\payment;
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 2015/1/8
 * Time: 20:59
 */
class Method
{
    /**
     * wechat for app
     */
    const WECHAT = 1;

    /**
     * alipay
     */
    const ALIPAY = 2;

    /**
     * offline
     */
    const OFFLINE = 3;

    /**
     * weixin
     */
    const WX = 4;

    /**
     * 钱包支付
     */
    const WALLET = 5;

    /**
     * 关闭钱包支付功能
     */
    const WALLET_SWITCH = 0;
}