<?php

namespace console\controllers;

use common\models\LeMerchantStore;
use service\components\shoppingcart\ShoppingCart;
use service\components\Tools;
use service\message\common\Product;
use service\message\customer\CustomerResponse;
use service\message\merchant\CartItemsResponse;
use service\message\merchant\WholesalerCart;
use service\resources\merchant\v1\cartItems;
use yii\base\Module;
use yii\console\Controller;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-2-9
 * Time: 上午10:16
 */
class CartController extends Controller
{
    public $customer;

    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->customer = new CustomerResponse();
        $this->customer->setCustomerId(12879);
        $this->customer->setAuthToken('f8lBSqIVBUh0o88J');
        $this->customer->setCity(441800);
        $this->customer->setAreaId(44);
    }

    public function actionCartItems()
    {
        $start = microtime(true);
        $cart = new ShoppingCart($this->customer);
        $cartInfo = $cart->cartItems();
        $response = new CartItemsResponse();
        print_r($cartInfo);
        $response->setFrom(['wholesaler_cart' => $cartInfo]);
        $end = microtime(true);
        echo $end - $start;
        echo PHP_EOL;
    }

    public function actionUpdateCartItems()
    {
        echo 'start' . PHP_EOL;
        $start = microtime(true);
        $cart = new ShoppingCart($this->customer);
//        $product = new Product();
//        $product->setProductId(34806);
//        $product->setNum(10);
//        $product->setSelected(1);
//        $product->setWholesalerId(556);
//        $product1 = new Product();
//        $product1->setProductId(34805);
//        $product1->setNum(2);
//        $product1->setSelected(1);
//        $product1->setWholesalerId(556);
        $product = new Product();
        $product->setProductId(34804);
        $product->setNum(2);
        $product->setSelected(0);
        $product->setWholesalerId(556);
//        $product3 = new Product();
//        $product3->setProductId(34802);
//        $product3->setNum(2);
//        $product3->setSelected(0);
//        $product3->setWholesalerId(116);
        $wholesalerCartItems = $cart->updateItems([$product]);
//        $wholesalerCartItems = $cart->updateItems([$product3]);
        $response = new CartItemsResponse();
        $response->setFrom($wholesalerCartItems);
        print_r($wholesalerCartItems);
        $end = microtime(true);
        echo $end - $start;

        echo 'complete' . PHP_EOL;
    }

    public function actionRemoveCartItems()
    {
        echo 'start' . PHP_EOL;
        echo microtime(true) . PHP_EOL;
        $cart = new ShoppingCart($this->customer);
        $product = new Product();
        $product->setProductId(34806);
        $product->setNum(1);
        $product->setSelected(1);
        $product->setWholesalerId(556);
        $product1 = new Product();
        $product1->setProductId(34805);
        $product1->setNum(1);
        $product1->setSelected(1);
        $product1->setWholesalerId(556);
        $cart->removeItems([$product, $product1]);
        echo microtime(true) . PHP_EOL;
        echo 'complete' . PHP_EOL;
    }


    public function actionTest()
    {
        $newCart = new ShoppingCart($this->customer);
        $newCartItems = $newCart->cartItemsFromRedis($this->customer->getCustomerId());
        $cartItems = [];
        foreach ($newCartItems as $wholesaler_id => $products) {
            foreach ($products as $product_id => $num) {
                $cartItems[$wholesaler_id]['list'][$product_id]['productId'] = $product_id;
                $cartItems[$wholesaler_id]['list'][$product_id]['num'] = abs($num);
            }
        }
        print_r($cartItems);
    }

    /**
     * Author Jason Y.Wang
     * 将3.0之前的购物车解析成3.0之后的购物车
     */
    public function actionConvertShoppingCart()
    {
        $redis = Tools::getRedis();
        $keys = $redis->keys("cart_key*");
        foreach ($keys as $key) {
            $result = explode('_', $key);
            $customer_id = $result[2];
            $cart = unserialize(Tools::getRedis()->get($key));
            foreach ($cart as $wholesaler_id => $product_list) {
                $customer_cart_key = sprintf(ShoppingCart::SHOPPING_CART_SUFFIX, $customer_id);
                $wholesaler_cart_key = sprintf(ShoppingCart::SHOPPING_CART_WHOLESALER_SUFFIX, $customer_id, $wholesaler_id);
                $redis->zAdd($customer_cart_key, time(), $wholesaler_id);
                foreach ($product_list['list'] as $product_id => $product) {
                    $num = $product['num'];
                    $redis->hSet($wholesaler_cart_key, $product_id, $num);
                }
            }
        }

    }

}