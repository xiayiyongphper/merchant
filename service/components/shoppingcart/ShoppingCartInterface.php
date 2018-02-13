<?php
/**
 * Created by Jason Y. Wang
 * Author: Jason Y. Wang
 * Date: 2017/8/16
 * Time: 11:10
 * 所有购物车都应该有的行为
 */

namespace service\components\shoppingcart;


interface ShoppingCartInterface
{
    public function cartItems();
    public function updateItems($products);
    public function removeItems($products);
}