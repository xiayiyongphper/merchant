<?php
/**
 * Created by Jason Y. Wang
 * Author: Jason Y. Wang
 * Date: 2017/8/16
 * Time: 11:10
 */

namespace service\components\shoppingcart;


use common\models\Products;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\sales\WholesalerQuote;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\Product;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;

class ShoppingCart implements ShoppingCartInterface
{
    const SHOPPING_CART_SUFFIX = 'shopping_cart_%d';
    const SHOPPING_CART_WHOLESALER_SUFFIX = 'shopping_cart_wholesaler_%d_%d';
    /** @var CustomerResponse $customer */
    private $customer;
    private $customerId;
    private $wholesalerIds;
    private $cityId;
    private $_cartItems = [];  //从redis中获得的结构化购物车

    /**
     * ShoppingCart constructor.
     * @param CustomerResponse $customer
     */
    public function __construct($customer)
    {
        $this->customer = $customer;
        $this->customerId = $customer->getCustomerId();
        $this->cityId = $customer->getCity();
        $this->wholesalerIds = MerchantResourceAbstract::getWholesalerIdsByAreaId($customer->getAreaId());
    }

    /**
     * Author Jason Y.Wang
     * @return array|boolean
     *
     */
    public function cartItems()
    {
        //组织用户的购物车
        $customerShoppingCart = $this->getWholesalerShoppingCart();
        return $customerShoppingCart;
    }

    public function getSingleWholesalerShoppingCart($wholesaler_id)
    {
        //单个店铺的购物车
        $customerShoppingCart = $this->getWholesalerShoppingCart([$wholesaler_id]);
        return $customerShoppingCart;
    }


    /**
     * Author Jason Y.Wang
     * @param $products
     * @param int $location
     * @return array
     */
    public function updateItems($products, $location = 0)
    {
        /** @var Header $pbHeader */
        $pbHeader = \Yii::$app->getRequest()->getPbHeader();
        //修改redis数据
        $needUpdateWholesalerIds = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $wholesalerId = $product->getWholesalerId();
            array_push($needUpdateWholesalerIds, $wholesalerId);
            $secKillProduct = false;
            //兼容3.0之前的版本
            if (version_compare($pbHeader->getAppVersion(), '3.0', '<') &&
                (($pbHeader->getSource() == SourceEnum::ANDROID_SHOP) || ($pbHeader->getSource() == SourceEnum::IOS_SHOP))) {
                if (SpecialProduct::isSecKillProductByIdTypeOld($product->getProductId(), $product->getType())) {
                    $secKillProduct = true;
                }
            } else {
                if (SpecialProduct::isSecKillProductByIdType($product->getProductId(), $product->getType())) {
                    $secKillProduct = true;
                }
            }

            //秒杀商品不同逻辑
            if ($secKillProduct) {
                /** @var Products $productModel */
                $productModel = $this->getProductModel($this->customer->getCity(), $product->getProductId());
                $productToCart = $productModel::findOne(['entity_id' => $product->getProductId()]);
                $res = $this->updateSecKillProductCartNum($productToCart, $product->getNum(), $product->getSelected());
            } else {
                //普通商品加入购物车
                $this->updateItemsValidate($product, $location);
            }
        }

        //已经修改成功的店铺购物车
        $customerShoppingCart = $this->getWholesalerShoppingCart($needUpdateWholesalerIds);
        return $customerShoppingCart;
    }

    /**
     * Author Jason Y.Wang
     * @param Product $product
     * @param $location
     * @throws \Exception
     */
    private function updateItemsValidate($product, $location = 0)
    {
        $wholesalerId = $product->getWholesalerId();
        $productId = $product->getProductId();
        $num = $product->getNum();

        if (empty($wholesalerId) || empty($productId) || empty($num)
            || filter_var($wholesalerId, FILTER_VALIDATE_INT) === false
            || filter_var($productId, FILTER_VALIDATE_INT) === false
            || filter_var($num, FILTER_VALIDATE_INT) === false
        ) {
            Exception::systemNotFound();
        }

        /* 得到商品模型，秒杀和其他普通商品区别开来 */
        /** @var Products $productModel */
        $productModel = $this->getProductModel($this->customer->getCity(), $productId);
        $productToCart = $productModel::findOne(['entity_id' => $productId]);

        if (!$productToCart) {
            throw new \Exception('该商品不存在');
        }

        if (!in_array($productToCart->wholesaler_id, $this->wholesalerIds)) {
            throw new \Exception('该商品不在配送范围');
        }

        //在Item.php中处理
//        //商品数量大于库存时，修改商品数量
//        if ($num > $productToCart->qty) {
//            $num = $productToCart->qty;
//        }
//        //商品数量小于起订数量时，修改商品数量
//        if ($num < $productToCart->minimum_order) {
//            $num = $productToCart->minimum_order;
//        }

        if ($productToCart->status != 1 || $productToCart->state != 2) {
            throw new \Exception('购物车中某些商品已下架', 8200);
        }

        if (!$product->getSelected()) {
            $num = 0 - $product->getNum();
        }

        $productAvailable[$wholesalerId][$productId] = $num;

        //更新购物车的商品
        $this->updateProductCartNum($this->customerId, json_encode($productAvailable), $location, time());

    }

    public function removeItems($products)
    {
        /** @var Header $pbHeader */
        $pbHeader = \Yii::$app->getRequest()->getPbHeader();
        //修改redis数据
        $needUpdateWholesalerIds = [];
        foreach ($products as $key => $product) {
            /** @var Product $product */
            $wholesalerId = $product->getWholesalerId();
            array_push($needUpdateWholesalerIds, $wholesalerId);
            $productId = $product->getProductId();
            $type = $product->getType();
            //秒杀商品  客户端传过来的商品type为1，2.9写死在客户端
            $secKillProduct = false;
            //兼容3.0之前的版本
            if (version_compare($pbHeader->getAppVersion(), '3.0', '<') &&
                (($pbHeader->getSource() == SourceEnum::ANDROID_SHOP) || ($pbHeader->getSource() == SourceEnum::IOS_SHOP))) {
                if (SpecialProduct::isSecKillProductByIdTypeOld($productId, $type)) {
                    $secKillProduct = true;
                }
            } else {
                if (SpecialProduct::isSecKillProductByIdType($productId, $type)) {
                    $secKillProduct = true;
                }
            }


            if ($secKillProduct) {
                $this->removeSecKillProduct($product);
            } else {
                $wholesaler_cart_key = $this->getShoppingCartWholesalerKey($this->customerId, $wholesalerId);
                Tools::getRedis()->hDel($wholesaler_cart_key, $productId);
            }
        }

        //已经修改成功的店铺购物车
        $customerShoppingCart = $this->getWholesalerShoppingCart($needUpdateWholesalerIds, true);
        return $customerShoppingCart;

    }


    /**
     * 获取商品模型
     *
     * @param integer $city
     * @param integer $productId
     * @return Products|SpecialProduct
     */
    private function getProductModel($city, $productId)
    {
        if (SpecialProduct::isSpecialProduct($productId)) {
            return new SpecialProduct();
        }
        return new Products($city);
    }

    private function getWholesalerShoppingCart($wholesalerIds = [], $ifNeedEmptyWholesaler = false)
    {
        //该用户所有的秒杀商品
        $seckillProductInfo = $this->getSecKillProductInfo();
        //redis中的普通商品
        $this->_cartItems = $this->cartItemsFromRedis($this->customerId);
        if (empty($wholesalerIds)) {
            //购物车中所有供应商
            $common_wholesaler_ids = array_keys($this->_cartItems);
            $seckill_wholesaler_ids = array_keys($seckillProductInfo);
            //购物车中包含的店铺，有商品的和无商品的都包括
            $shoppingCartWholesalerIds = array_unique(array_merge($common_wholesaler_ids, $seckill_wholesaler_ids));
            //所用店铺的购物车
            $wholesalerIds = $shoppingCartWholesalerIds;
        }

        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesalerIds, $this->customer->getAreaId());
        $area_wholesaler_ids = MerchantResourceAbstract::getWholesalerIdsByAreaId($this->customer->getAreaId());

        $wholesalerCart = [];
        foreach ($wholesalerIds as $wholesaler_id) {
            //商家信息
            if (empty($storeDetails[$wholesaler_id]) || !in_array($wholesaler_id, $area_wholesaler_ids)) {
                continue;
            }
            $wholesaler = $storeDetails[$wholesaler_id];
            //商品信息
            $cartSeckillProducts = empty($seckillProductInfo[$wholesaler_id]) ? [] : $seckillProductInfo[$wholesaler_id];
            $cartCommonProducts = empty($this->_cartItems[$wholesaler_id]) ? [] : $this->_cartItems[$wholesaler_id];
            //无商品时
            if (empty($cartCommonProducts) && empty($cartSeckillProducts)) {
                //没有商品时，不返回该店铺
                if ($ifNeedEmptyWholesaler) {
                    $wholesalerQuoteCollect = WholesalerQuote::init($this->customer)->setWholesaler($wholesaler)
                        ->collectTotals();
                } else {
                    continue;
                }
            } else {
                $parseResult = ParseCartItems::parseWholesalerCartItems($this->customer, $wholesaler_id, $cartCommonProducts, $cartSeckillProducts);

                $rules = $parseResult['rule'];
                $wholesalerCartItems = $parseResult['cartItems'];

                if (count($wholesalerCartItems) == 0) {
                    if ($ifNeedEmptyWholesaler) {
                        $wholesalerQuoteCollect = WholesalerQuote::init($this->customer)->setWholesaler($wholesaler)
                            ->collectTotals();
                    } else {
                        continue;
                    }
                } else {
                    $wholesalerQuoteCollect = WholesalerQuote::init($this->customer, $rules)->setWholesaler($wholesaler)
                        ->setWholesalerItems($wholesalerCartItems)
                        ->collectTotals();
                }
            }
            //商家购物车
            $wholesalerCart[] = $wholesalerQuoteCollect;
        }

        return $wholesalerCart;
    }

    /**
     * Author Jason Y.Wang
     * @param $customer_id
     * @param $productAvailable
     * 更新购物车中的商品
     * @param $location
     * @param $cur_time
     * @return bool
     */
    private function updateProductCartNum($customer_id, $productAvailable, $location = 0, $cur_time)
    {
        $script = <<<'SCRIPT'
    local function updateCartItem(KEYS)
    local customerId = tonumber(KEYS[1]);
    local productAvailable = cjson.decode(KEYS[2]);
    local cur_time = tonumber(KEYS[3]);
    local location = tonumber(KEYS[4]);
    local cartKey = "shopping_cart_" .. customerId;

    for wholesalerId, products in pairs(productAvailable) do
        local shoppingCartWholesalerKey = "shopping_cart_wholesaler_" .. customerId .. "_" .. wholesalerId;
        if (location == 0) then
            redis.call("ZADD", cartKey, cur_time, wholesalerId);
        end
        if (next(products) ~= nil) then
            for productId, num in pairs(products) do
                redis.call("HSET", shoppingCartWholesalerKey, productId, num);
            end
        end
    end

    return 1;
end

return updateCartItem(KEYS);
SCRIPT;
        $res = ToolsAbstract::getRedis()->eval($script, [
            $customer_id, $productAvailable, $cur_time, $location
        ], 4);
        Tools::log($customer_id, 'updateProducts.log');
        Tools::log($productAvailable, 'updateProducts.log');
        Tools::log($cur_time, 'updateProducts.log');
        Tools::log($res, 'updateProducts.log');
        return $res;
    }

    /**
     * Author Jason Y.Wang
     * @param $customerId
     * 获取redis的购物车
     *
     * @return array
     */
    public function cartItemsFromRedis($customerId)
    {
        $cartItems = [];
        $redis = Tools::getRedis();
        $shoppingCartKey = "shopping_cart_" . $customerId;
        $wholesalerIds = $redis->zRevRangeByScore($shoppingCartKey, "+inf", "-inf");
        foreach ($wholesalerIds as $wholesalerId) {
            $shoppingCartWholesalerKey = "shopping_cart_wholesaler_" . $customerId . "_" . $wholesalerId;
            $products = $redis->hGetAll($shoppingCartWholesalerKey);
            //加入时间排序
            $products = array_reverse($products, true);
            foreach ($products as $product_id => $num) {
                $cartItems[$wholesalerId][$product_id] = $num;
            }
        }

        return $cartItems;
    }

    /**
     * Author Jason Y.Wang
     * @param $customerId
     * 获取部分供应商的redis的购物车
     *
     * @param $wholesalerIds
     * @return array
     */
    private function wholesalerCartItemsFromRedis($customerId, $wholesalerIds)
    {
        if (!is_array($wholesalerIds)) {
            $wholesalerIds = [$wholesalerIds];
        }

        $cartItems = [];
        $redis = Tools::getRedis();

        foreach ($wholesalerIds as $wholesalerId) {
            $shoppingCartWholesalerKey = "shopping_cart_wholesaler_" . $customerId . "_" . $wholesalerId;
            $products = $redis->hGetAll($shoppingCartWholesalerKey);
            //加入时间排序
            $products = array_reverse($products, true);
            $cartItems[$wholesalerId] = [];
            foreach ($products as $product_id => $num) {
                $cartItems[$wholesalerId][$product_id] = $num;
            }
        }

        return $cartItems;
    }


    public function getShoppingCartWholesalerKey($customerId, $wholesalerId)
    {
        return sprintf(self::SHOPPING_CART_WHOLESALER_SUFFIX, $customerId, $wholesalerId);
    }

    /**
     * [wholesaler_id1 => [pro1, pro2], wholesaler_id2 => [pro3, pro4], ...]
     *
     * @return array
     */
    private function getSecKillProductInfo()
    {
        $ret = [];
        if (!$activity = $this->getCurSecKillActivity()) {
            return $ret;
        }

        /* 从redis里面获取信息 */
        $actId = $activity['entity_id'];
        $customerId = $this->customer->getCustomerId();
        $areaId = $this->customer->getAreaId();
        if (!$result = ToolsAbstract::getUserCartSecKillProducts($actId, $customerId, $areaId)) {
            return $ret;
        }

        $proIds = array_keys($result);
        $products = SpecialProduct::findAll([
            'entity_id' => $proIds,
            'activity_id' => $activity['entity_id'],
            'city' => $this->customer->getCity(),
            'type2' => SpecialProduct::TYPE_SECKILL
        ]);

        if (!$products) {
            return $ret;
        }
        foreach ($products as $k => $product) {
            if (empty($product->wholesaler_id)) {
                continue;
            }

            $product = $product->toArray();

            $product['num'] = $result[$product['entity_id']]['n'];
            $product['left_time'] = $result[$product['entity_id']]['t'];
            $product['selected'] = SpecialProduct::getSecKillProductIsSelected($customerId, $product['entity_id']);
            $ret[$product['wholesaler_id']][$product['entity_id']] = $product;
        }

        return $ret;
    }

    private function getCurSecKillActivity()
    {
        return SecKillActivity::getCityCurActivity($this->customer->getCity(), SeckillHelper::IS_CACHE);
    }

    /**
     * 移除购物车秒杀商品
     *
     * @param Product $product
     * @throws \Exception
     * @return boolean
     */
    private function removeSecKillProduct($product)
    {
        /** @var Product $product */
        /* 先判断活动是否存在 */
        if (!$activity = $this->getCurSecKillActivity()) {
            // throw new \Exception('商品所在的活动不存在或已被删除');
            return true;
        }

        $actId = $activity['entity_id'];
        $productId = $product->getProductId();
        $customerId = $this->customer->getCustomerId();
        $areaId = $this->customer->getAreaId();

        return ToolsAbstract::removeSecKillCartProduct($actId, $productId, $customerId, $areaId);
    }

    /**
     * 更新购物车秒杀商品
     *
     * @param SpecialProduct $product
     * @param integer $num
     * @param $selected
     * @return bool
     * @throws \Exception
     */
    protected function updateSecKillProductCartNum($product, $num, $selected)
    {
        /** @var SpecialProduct $product */
        if (empty($product->activity_id)) {
            throw new \Exception('参数错误');
        }

        /* 先判断活动是否存在 */
        if (!$activity = $this->getCurSecKillActivity()) {
            throw new \Exception('商品“' . $product->name . '”已经被抢光啦~');
        }

        if (!empty($product->restrict_daily) && $num > $product->restrict_daily) {
            $num = $product->restrict_daily;
        }

        /* 判断是否有商品库存，有库存则只存储库存信息 */
        $actId = $activity['entity_id'];
        $customerId = $this->customer->getCustomerId();
        $areaId = $this->customer->getAreaId();
        $productId = $product->entity_id;
        $wholesalerId = $product->wholesaler_id;
        $leftSeconds = strtotime($activity['end_time']) - ToolsAbstract::getDate()->timestamp();
        $leftSeconds = $leftSeconds > 0 ? $leftSeconds : 0;

        SpecialProduct::setSecKillProductIsSelected($customerId, $productId, $selected);

        return ToolsAbstract::updateCartSecKillProduct($actId, $productId, $num, $customerId, $areaId, $leftSeconds, $wholesalerId);
    }


}