<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/11
 * Time: 11:49
 */

namespace common\models;


use common\redis\Keys;
use framework\components\ToolsAbstract;
use service\message\customer\CustomerResponse;

class ProductDecorator
{
    /**
     * @var Products|SpecialProduct
     */
    private $product;

    /**
     * ProductDecorator constructor.
     * @param Products|SpecialProduct $product
     * @throws \Exception
     */
    public function __construct($product)
    {
        if ($product instanceof Products || $product instanceof SpecialProduct) {
            $this->product = $product;
        } else {
            throw new \Exception('参数错误');
        }
    }

    /**
     * 检查限购
     *
     * @param int $qty
     * @param CustomerResponse $customer
     * @param string $indexKey
     * @return bool
     */
    public function checkRestrictDaily($qty, $customer, $indexKey = 'restrict_daily')
    {
        $key = Keys::getDailyPurchaseHistory($customer->getCustomerId(), $customer->getCity());
        $purchasedQty = ToolsAbstract::getRedis()->hGet($key, $this->product['entity_id']);
        $restrictDaily = empty($this->product[$indexKey]) ? 0 : (int)$this->product[$indexKey];
        if ($restrictDaily == 0) {
            return true;
        }

        if ($restrictDaily > 0 && $restrictDaily > $purchasedQty && ($restrictDaily - $purchasedQty - $qty) >= 0) {
            return true;
        }
        return false;
    }

    /**
     * Author Jason Y.Wang
     * @param CustomerResponse $customer
     * @param $productId
     * @return string
     * 获取限购商品已经购买的数量
     */
    public static function getAlreadyBuyNum($customer,$productId){
        $key = Keys::getDailyPurchaseHistory($customer->getCustomerId(), $customer->getCity());
        $purchasedQty = ToolsAbstract::getRedis()->hGet($key, $productId);
        return $purchasedQty;
    }

    /**
     * @return Products|SpecialProduct
     */
    public function getProduct()
    {
        return $this->product;
    }
}