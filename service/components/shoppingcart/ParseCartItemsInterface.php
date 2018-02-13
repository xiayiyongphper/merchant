<?php
/**
 * Created by Jason Y. Wang
 * Author: Jason Y. Wang
 * Date: 2017/8/16
 * Time: 11:10
 */

namespace service\components\shoppingcart;


interface ParseCartItemsInterface
{
    public static function parseWholesalerCartItems($customer, $wholesaler_id, $products, $cartSeckillProducts);
}