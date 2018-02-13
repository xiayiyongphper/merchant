<?php
namespace service\models\payment;
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 2015/1/8
 * Time: 20:59
 */
interface PaymentInterface
{
    /**
     * pay order
     * @return mixed
     */
    public function pay();

    /**
     * refund order
     * @return mixed
     */
    public function refund();
}