<?php
/**
 * Created by Jason Y. Wang
 * Author: Jason Y. Wang
 * Date: 2017/8/16
 * Time: 11:10
 */

namespace service\components\shoppingcart;


use common\models\SpecialProduct;
use framework\db\readonly\models\core\Rule;
use service\components\Proxy;
use service\components\sales\quote\Item;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\Protocol;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\models\ProductHelper;

class ParseCartItems implements ParseCartItemsInterface
{

    /**
     * Author Jason Y.Wang
     * @param CustomerResponse $customer
     * @param $products //redis中的购物车
     * @param $cartSeckillProducts
     * @return array 返回分组后的商品
     * 返回分组后的商品
     */
    public static function parseWholesalerCartItems($customer, $wholesaler_id, $products, $cartSeckillProducts = [])
    {
        $productIds = array_keys($products);
        $cartItems = [];

        //秒杀商品信息数组
        $seckillProducts = (new ProductHelper())
            ->initWithProductArray($cartSeckillProducts, $customer->getCity())
            ->getTags(['show_seckill' => 1])->getData();
        /** @var SpecialProduct $product */
        foreach ($seckillProducts as $product_id => $product) {
            $item = new Item();
            $product['left_time'] = $cartSeckillProducts[$product_id]['left_time'];
            $product['selected'] = $cartSeckillProducts[$product_id]['selected'];
            $product['num'] = $cartSeckillProducts[$product_id]['num'];
            $cartItem = $item->setProduct($customer, $product);
            $cartItems['speckill'][] = $cartItem;
        }

        //获取商品信息数组
        $commonProducts = self::getProductsByProductIds($productIds, $customer->getCity());
        $ruleIds = [];
        foreach ($commonProducts as $commonProduct) {
            if ($commonProduct['rule_id'] > 0) {
                array_push($ruleIds, $commonProduct['rule_id']);
            }
        }
        $ruleIds = array_unique($ruleIds);

        $rules = [];
        $header = new Header();
        $header->setProtocol(Protocol::JSON);
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('sales.getRulesByJson');
        $rulesResponse = Proxy::sendRequest($header, ['wholesalerId' => $wholesaler_id, 'ruleIds' => $ruleIds])->getPackageBody();
        $rulesArray = json_decode($rulesResponse, true);

        //去掉供应商活动  保证都是商品级的活动
        $availableRuleIds = array_filter(array_keys($rulesArray));
        //初始化优惠活动类
        foreach ($rulesArray as $ruleIdKey => $ruleArray) {
            $rule = new Rule();
            $rule->setAttributes($ruleArray, false);
            $rules[$ruleIdKey] = $rule;
        }

        //有活动的商品  兼顾排序
        foreach ($availableRuleIds as $availableRuleId) {
            foreach ($commonProducts as $product_id => $product) {
                if ($product['rule_id'] == $availableRuleId) {
                    $item = new Item();
                    $product['num'] = $products[$product_id];
                    $cartItem = $item->setProduct($customer, $product);
                    $cartItems[$availableRuleId][] = $cartItem;
                    //有活动的商品
                    unset($commonProducts[$product_id]);
                }
            }
        }
        //没有活动的商品
        foreach ($commonProducts as $product_id => $product) {
            $item = new Item();
            $product['num'] = $products[$product_id];
            $cartItem = $item->setProduct($customer, $product);
            $cartItems[0][] = $cartItem;
        }

        return [
            'rule' => $rules,
            'cartItems' => $cartItems,
        ];
    }

    private static function getProductsByProductIds($productIds, $city)
    {
        //普通商品
        $productIdsArray['common'] = [];
        //特殊商品
        $productIdsArray['special'] = [];

        foreach ($productIds as $productId) {
            if (SpecialProduct::isSpecialProduct($productId)) {
                array_push($productIdsArray['special'], $productId);
            } else {
                array_push($productIdsArray['common'], $productId);
            }
        }

        $commonProducts = [];
        if (!empty($productIdsArray['common'])) {
            //普通商品
            $commonProducts = (new ProductHelper())->initWithProductIds($productIds, $city,
                [], '388x388', false)->getData();
        }

        $specialProducts = [];
        if (!empty($productIdsArray['special'])) {
            $specialProducts = SpecialProduct::find()->where(['entity_id' => $productIdsArray['special']])->asArray()->all();
            //秒杀商品
            $specialProducts = (new ProductHelper())
                ->initWithProductArray($specialProducts, $city)
                ->getTags(['show_seckill' => 1])->getData();
        }

        return $commonProducts + $specialProducts;

    }

}