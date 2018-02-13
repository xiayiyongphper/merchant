<?php
/**
 * Created by Jason Y. Wang
 * Author: Jason Y. Wang
 * Date: 2017/8/16
 * Time: 11:10
 */

namespace service\components\shoppingcart;


use service\components\Tools;

class SpecialKillerShoppingCart implements ShoppingCartInterface
{

    public $wholesalerId;
    public $customerId;
    public $products;


    public function __construct($customer_id,$wholesaler_id)
    {
        if(!$wholesaler_id){

        }
        $redis = Tools::getRedis();
        $this->wholesalerId = $wholesaler_id;
        $this->customerId = $customer_id;
        $this->products = $redis->hGetAll('');
    }

    public function cartItems()
    {
        
        return;
    }

    public function addItems()
    {

    }

    public function updateItems($products)
    {
        // TODO: Implement updateItems() method.
    }

    public function removeItems($products)
    {
        // TODO: Implement removeItems() method.
    }
}