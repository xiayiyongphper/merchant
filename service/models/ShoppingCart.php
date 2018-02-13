<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/26
 * Time: 11:03
 */

namespace service\models;

use common\models\ProductDecorator;
use common\models\Products;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Redis;
use service\components\Tools;
use service\message\common\Product;
use service\message\customer\CartItemsResponse;
use service\message\customer\CartItemsResponse2;
use service\message\customer\CustomerResponse;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use yii\helpers\ArrayHelper;
use common\redis\Keys;

/**
 * Author: Jason Y. Wang
 * Class LE_Sales_Model_ShoppingCart
 */
class ShoppingCart
{
    //购物车前缀
    const CART_KEY_PREFIX = 'cart_key';
    /** 购物车秒杀商品前缀 */
    const CART_SECKILL_KEY_PREFIX = SpecialProduct::SECKILL_KEY_PREFIX;
    /** 用户在Redis购物车中普通商品的key */
    protected $normalCartKey;
    /** @var array Redis中的购物车原型 */
    protected $redisCartItems;
    /** @var CustomerResponse $customer */
    protected $customer;

    /**
     * @param  CustomerResponse $customer
     *
     */
    public function __construct($customer)
    {
        $this->customer = $customer;
        //获取Redis中某个用户购物车的key
        $this->normalCartKey = sprintf('%s_%s_%s', self::CART_KEY_PREFIX, $customer->getCustomerId(), $customer->getAreaId());
        //在Redis中取得数据
        $this->newCartToOldCart();

//        $cart = unserialize(Tools::getRedis()->get($this->normalCartKey));
//        if (is_array($cart)) {
//            $this->redisCartItems = $cart;
//        } else {
//            $this->redisCartItems = [];
//        }

    }

    private function newCartToOldCart()
    {
        $newCart = new \service\components\shoppingcart\ShoppingCart($this->customer);
        $newCartItems = $newCart->cartItemsFromRedis($this->customer->getCustomerId());
        $cartItems = [];
        foreach ($newCartItems as $wholesaler_id => $products) {
            foreach ($products as $product_id => $num) {
                $cartItems[$wholesaler_id]['list'][$product_id]['productId'] = $product_id;
                $cartItems[$wholesaler_id]['list'][$product_id]['num'] = abs($num);
            }
        }
        $this->redisCartItems = $cartItems;
    }


    /**
     * Function: loadShoppingCartByCustomer
     * Author: Jason Y. Wang
     * 格式化redis中的购物车
     * @return object
     */
    public function formatShoppingCart()
    {
        $data = array();
        $cartItems = new CartItemsResponse();
        if (!$this->redisCartItems) {
            return $cartItems;
        }
        $wholesaler_ids = array_keys($this->redisCartItems);
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $this->customer->getAreaId());
        //Tools::log($storeDetails,'wangyang.log');
        //Tools::log($this->redisCartItems,'wangyang.log');
        //格式化购物车中的商品
        foreach ($this->redisCartItems as $wholesaler_id => $products) {
            if (!is_numeric($wholesaler_id)) {
                //去掉key不是数字的数据，避免购物车数据混乱错误导到的问题
                continue;
            }

            if (!isset($storeDetails[$wholesaler_id])) {
                //如果load不到店铺，则不处理这个商品
                continue;
            }

            $storeDetail = $storeDetails[$wholesaler_id];
            $orderCountToday = Proxy::getOrderCountToday($this->customer, $wholesaler_id);
            $wholesaler_cart = array();
            $productIds = array_keys($products['list']);
            //$productList = MerchantResourceAbstract::getProductsArrayPro2($productIds, $this->customer->getCity());
            $productList = (new ProductHelper())->initWithProductIds($productIds, $this->customer->getCity())
                ->getTags()->getData();
            //购物车中的ID，没有查到商品时，则不返回店铺
            if (count($productList) == 0) {
                continue;
            }

            $productsTmp = array();
            /** @var Products $product */
            foreach ($productList as $product) {
                $product_num = $products['list'][$product['product_id']]['num'];
                $product_info = $product;
                $product_info['num'] = $product_num;
                $product_info['purchased_qty'] = Tools::getPurchasedQty($this->customer->getCustomerId(), $this->customer->getCity(), $product['product_id']);
                $productsTmp[$product['product_id']] = $product_info;
            }

            //起送价向上取整
            $wholesaler_cart['list'] = $productsTmp;
            if (isset($storeDetail['min_trade_amount']) && $storeDetail['min_trade_amount'] != 0) {
                $wholesaler_cart['tips'] = '每天首单满' . ceil($storeDetail['min_trade_amount']) . '元起送';
                $wholesaler_cart['min_trade_amount'] = $orderCountToday > 0 ? 0 : ceil($storeDetail['min_trade_amount']);
            } else {
                $wholesaler_cart['tips'] = '本单免起送价';
                $wholesaler_cart['min_trade_amount'] = 0;
            }

            $wholesaler_cart['wholesaler_id'] = $wholesaler_id;
            $wholesaler_cart['wholesaler_name'] = $storeDetail['wholesaler_name'];
            $data[] = $wholesaler_cart;
        }
        $cartItemsData = array('data' => $data);
        $cartItems->setFrom(Tools::pb_array_filter($cartItemsData));
        //返回数组
        return $cartItems;
    }

    /**
     * Function: formatShoppingCart2
     * Author: Jason Y. Wang
     * 格式化redis中的购物车
     * app 2.4版本后请求
     * 按优惠规则商品分组，返回特定结果由js解析，app展示
     *
     */
    public function formatShoppingCart2()
    {
        $data = array();
        $cartItems = new CartItemsResponse2();
        if (!$this->redisCartItems) {
            return $cartItems;
        }
        $wholesaler_ids = array_keys($this->redisCartItems);
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $this->customer->getAreaId());
        //订单级优惠
        $wholesalerPromotionsPre = Tools::getWholesalerCartPromotions($wholesaler_ids);
        Tools::log($wholesalerPromotionsPre, 'hl.log');
        // 过滤
        $customer_id = $this->customer->getCustomerId();
        $wholesalerPromotions = [];
        foreach ($wholesalerPromotionsPre as $wholesaler_id => $rule) {
            $rule_id = $rule['rule_id'];
            //如果优惠活动，该用户已使用达到单个用户享受次数上线，则不可再享受
            //获取Redis中某个用户某个优惠活动享受次数的key
            $enjoy_times_key = Keys::getEnjoyTimesKey($customer_id, $rule_id);
            //在Redis中取得数据
            $enjoy_times = Tools::getRedis()->get($enjoy_times_key);
            //Tools::log($enjoy_times,'hl.log');
            //Tools::log("type of enjoy_times========".gettype($enjoy_times),'hl.log');
            //Tools::log("rule_uses_limit========".$rule['rule_uses_limit'],'hl.log');
            if ($rule['rule_uses_limit'] > 0 && $enjoy_times >= $rule['rule_uses_limit']) {
                continue;
            }
            // 加进去
            $wholesalerPromotions[$wholesaler_id] = $rule;
        }

        //格式化购物车中的商品
        foreach ($this->redisCartItems as $wholesaler_id => $products) {
            if (!is_numeric($wholesaler_id)) {
                //去掉key不是数字的数据，避免购物车数据混乱错误导到的问题
                continue;
            }

            if (!isset($storeDetails[$wholesaler_id])) {
                //如果load不到店铺，则不处理这个商品
                continue;
            }

            //今天是否已下过订单
            $storeDetail = $storeDetails[$wholesaler_id];
            $orderCountToday = Proxy::getOrderCountToday($this->customer, $wholesaler_id);

            //是否展示领取优惠券按钮
            $coupons = Proxy::getCouponReceiveList(4, 0, $wholesaler_id);
            //Tools::wLog($coupons);
            $wholesaler_cart = array();
            if ($coupons) {
                $wholesaler_cart['coupon_receive_layout'] = [
                    'button_image' => 'http://assets.lelai.com/assets/coupon/lingquan.png',
                ];
            }

            $productIds = array_keys($products['list']);

            $productList = (new ProductHelper())->initWithProductIds($productIds, $this->customer->getCity())
                ->getTags()->getData();

            //Tools::log($productList, 'wangyang.log');
            //购物车中的ID，没有查到商品时，则不返回店铺
            if (count($productList) == 0) {
                continue;
            }

            ///////////商品按规则ID分组//////////
            //分组后的商品
            $promotionGroupProducts = [];

            //获取商品中的促销规则ID
            $rule_ids = array_filter(array_unique(ArrayHelper::getColumn($productList, 'rule_id')));
            //Tools::log('rule_ids','wangyang.log');
            //Tools::log($rule_ids,'wangyang.log');
            //查询优惠条件标签
            $rules = [];
            if (count($rule_ids) > 0) {
                $rules = Tools::getProductPromotions($rule_ids);

            }
            //Tools::log('Line:'.__LINE__,'wangyang.log');
            //Tools::log($rules, 'wangyang.log');
            //根据商品是否参加活动分组
            foreach ($rules as $rule_id => $rule) {
                //只有优惠规则才在购物车展示并计算
                if ($rule['coupon_type'] == 1) {
                    //如果优惠活动，该用户已使用达到单个用户享受次数上线，则不可再享受
                    //获取Redis中某个用户某个优惠活动享受次数的key
                    $enjoy_times_key = Keys::getEnjoyTimesKey($this->customer->getCustomerId(), $rule_id);
                    //在Redis中取得数据
                    $enjoy_times = Tools::getRedis()->get($enjoy_times_key);
                    Tools::log($enjoy_times, 'hl.log');
                    Tools::log("type of enjoy_times========" . gettype($enjoy_times), 'hl.log');
                    Tools::log("rule_uses_limit========" . $rule['rule_uses_limit'], 'hl.log');
                    if ($rule['rule_uses_limit'] > 0 && $enjoy_times >= $rule['rule_uses_limit']) {
                        continue;
                    }

                    foreach ($productList as $key => $product) {
                        $product_num = $products['list'][$product['product_id']]['num'];
                        $product_info = $product;
                        $product_info['num'] = $product_num;
                        $product_info['purchased_qty'] = Tools::getPurchasedQty($this->customer->getCustomerId(), $this->customer->getCity(), $product['product_id']);
                        if ($product['rule_id'] == $rule_id) {
                            $promotionGroupProducts[$rule_id][] = $product_info;
                            unset($productList[$key]);
                        }
                    }
                }
            }


            //没有参加活动的商品
            foreach ($productList as $key => $product) {
                $product_num = $products['list'][$product['product_id']]['num'];
                $product_info = $product;
                $product_info['num'] = $product_num;
                $product_info['purchased_qty'] = Tools::getPurchasedQty($this->customer->getCustomerId(), $this->customer->getCity(), $product['product_id']);
                $promotionGroupProducts['cart' . $key][] = $product_info;

            }

            $promotionProducts = [];
            foreach ($promotionGroupProducts as $key => $productArray) {

                $productGroupList = [];
                if (is_numeric($key)) {
                    $promotion = isset($rules[$key]) ? $rules[$key] : null;


                    if (!$promotion) {
                        //无优惠活动的商品
                        foreach ($productArray as $product) {
                            $list = [];
                            array_push($list, $product);
                            $productGroupList['promotion'] = $promotion;
                            $productGroupList['list'] = $list;
                            array_push($promotionProducts, $productGroupList);
                        }

                    } else if ($promotion['type'] == 1) {
                        //单品级活动
                        foreach ($productArray as $product) {
                            $list = [];
                            array_push($list, $product);
                            $productGroupList['promotion'] = $promotion;
                            $productGroupList['list'] = $list;
                            array_push($promotionProducts, $productGroupList);
                        }
                    } else {
                        $list = [];
                        //多品级活动
                        foreach ($productArray as $product) {
                            array_push($list, $product);
                        }
                        $productGroupList['promotion'] = $promotion;
                        $productGroupList['list'] = $list;
                        array_push($promotionProducts, $productGroupList);
                    }
                } else {
                    //没有参加优惠活动的商品
                    $promotion = null;
                    $list = [];
                    foreach ($productArray as $product) {
                        array_push($list, $product);
                        $productGroupList['promotion'] = $promotion;
                        $productGroupList['list'] = $list;
                        array_push($promotionProducts, $productGroupList);
                    }
                }
            }

            if ($wholesalerPromotions && isset($wholesalerPromotions[$wholesaler_id])) {
                $wholesaler_cart['wholesaler_promotion'] = $wholesalerPromotions[$wholesaler_id];
            }
            //Tools::log('============','wangyang.log');
            //Tools::log($wholesalerPromotion,'wangyang.log');
            if (isset($storeDetail['min_trade_amount']) && $storeDetail['min_trade_amount'] != 0) {
                $wholesaler_cart['tips'] = '每天首单满' . ceil($storeDetail['min_trade_amount']) . '元起送';
                $wholesaler_cart['min_trade_amount'] = $orderCountToday > 0 ? 0 : ceil($storeDetail['min_trade_amount']);
            } else {
                $wholesaler_cart['tips'] = '本单免起送价';
                $wholesaler_cart['min_trade_amount'] = 0;
            }

            $wholesaler_cart['wholesaler_id'] = $wholesaler_id;
            $wholesaler_cart['wholesaler_name'] = $storeDetail['wholesaler_name'];
            $wholesaler_cart['product_group'] = $promotionProducts;
            $data[] = $wholesaler_cart;
        }
        $cartItemsData = array('data' => $data);
        //Tools::log($cartItemsData, 'wangyang.log');
        //Tools::log(json_encode($cartItemsData), 'wangyang.log');
        $cartItems->setFrom(Tools::pb_array_filter($cartItemsData));
        //返回数组
        return $cartItems;
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
            $product['type'] = 1;//2.9版本客户端判断商品类型，秒杀商品设置为1
            $product->num = $result[$product->entity_id]['n'];
            $product->left_time = $result[$product->entity_id]['t'];
            $ret[$product->wholesaler_id][$product->entity_id] = $product;
        }

        return $ret;
    }

    /**
     * 格式化购物车（比formatShoppingCart2增加了秒杀商品的显示）
     * 按优惠规则商品分组，返回特定结果由js解析，app展示
     *
     * @author: Jason Y. Wang
     * @author zqy
     * @since 2.9
     * @throws \Exception
     * @return CartItemsResponse2
     */
    public function formatShoppingCart3()
    {
        // 秒杀商品
        $seckillProductInfo = $this->getSecKillProductInfo();

        $cartItems = new CartItemsResponse2();
        if (!($this->redisCartItems || $seckillProductInfo)) {
            return $cartItems;
        }

        $data = [];
        $wholesaler_ids = array_unique(array_merge(array_keys($seckillProductInfo), array_keys($this->redisCartItems)));
        $storeDetails = MerchantResourceAbstract::getStoreDetailBrief($wholesaler_ids, $this->customer->getAreaId());
        //订单级优惠
        $wholesalerPromotionsPre = Tools::getWholesalerCartPromotions($wholesaler_ids);
        // 过滤
        $customer_id = $this->customer->getCustomerId();
        $wholesalerPromotions = [];
        foreach ($wholesalerPromotionsPre as $wholesaler_id => $rule) {
            $rule_id = $rule['rule_id'];
            //如果优惠活动，该用户已使用达到单个用户享受次数上线，则不可再享受
            //获取Redis中某个用户某个优惠活动享受次数的key
            $enjoy_times_key = Keys::getEnjoyTimesKey($customer_id, $rule_id);
            //在Redis中取得数据
            $enjoy_times = Tools::getRedis()->get($enjoy_times_key);
            if ($rule['rule_uses_limit'] > 0 && $enjoy_times >= $rule['rule_uses_limit']) {
                continue;
            }
            // 加进去
            $wholesalerPromotions[$wholesaler_id] = $rule;
        }

        //格式化购物车中的商品
        foreach ($wholesaler_ids as $wholesaler_id) {
            if (!is_numeric($wholesaler_id)) {
                //去掉key不是数字的数据，避免购物车数据混乱错误导到的问题
                continue;
            }

            if (!isset($storeDetails[$wholesaler_id])) {
                //如果load不到店铺，则不处理这个商品
                continue;
            }

            //今天是否已下过订单
            $storeDetail = $storeDetails[$wholesaler_id];
            $orderCountToday = Proxy::getOrderCountToday($this->customer, $wholesaler_id);

            //是否展示领取优惠券按钮
            $coupons = Proxy::getCouponReceiveList(4, 0, $wholesaler_id);
            $wholesaler_cart = [];
            if ($coupons) {
                $wholesaler_cart['coupon_receive_layout'] = [
                    'button_image' => 'http://assets.lelai.com/assets/coupon/lingquan.png',
                ];
            }

            /* 秒杀商品数据整理 */
            $cartSeckillProducts = empty($seckillProductInfo[$wholesaler_id]) ? [] : $seckillProductInfo[$wholesaler_id];
            $seckillProducts = (new ProductHelper())
                ->initWithProductArray($cartSeckillProducts, $this->customer->getCity())
                ->getTags(['show_seckill' => 1])->getData();

            /* 普通商品数据整理 */
            $cartNormalProducts = [];
            if (!empty($this->redisCartItems[$wholesaler_id]['list'])) {
                $cartNormalProducts = $this->redisCartItems[$wholesaler_id]['list'];

                $allProductIds = array_keys($this->redisCartItems[$wholesaler_id]['list']);
                $specialProductIds = [];
                $normalProductIds = [];
                //区分普通商品和特殊商品
                foreach ($allProductIds as $productId) {
                    if (SpecialProduct::isSpecialProduct($productId)) {
                        $specialProductIds[] = $productId;
                    } else {
                        $normalProductIds[] = $productId;
                    }
                }
                $normalProducts = [];
                if ($normalProductIds) { // 普通商品
                    $normalProducts = (new ProductHelper())
                        ->initWithProductIds($normalProductIds, $this->customer->getCity())
                        ->getTags()
                        ->getData();
                }

                /* 特殊商品处理逻辑 */
                $specialProducts = [];
                if ($specialProductIds) {
                    $specialProducts = SpecialProduct::find()->where([
                        'status' => SpecialProduct::STATUS_ENABLED,
                        'type2' => SpecialProduct::TYPE_SPECIAL,
                        'entity_id' => $specialProductIds
                    ])->asArray()->all();
                    /* 格式化输出 */
                    $specialProducts = (new ProductHelper())
                        ->initWithProductArray($specialProducts, $this->customer->getCity())
                        ->getTags(['show_seckill' => 1])
                        ->getData();
                }

                /* 合并商品列表 */
                $normalProducts = $specialProducts + $normalProducts;

            } else {
                $normalProducts = [];
            }

            //购物车中的ID，没有查到商品时，则不返回店铺
            if (count($normalProducts) == 0 && count($seckillProducts) == 0) {
                continue;
            }

            // 合并秒杀商品和普通商品列表
            $productList = $seckillProducts + $normalProducts;

            ///////////商品按规则ID分组//////////
            //分组后的商品
            $promotionGroupProducts = [];

            //获取商品中的促销规则ID
            $rule_ids = array_filter(array_unique(ArrayHelper::getColumn($productList, 'rule_id')));
            //查询优惠条件标签
            $rules = [];
            if (count($rule_ids) > 0) {
                $rules = Tools::getProductPromotions($rule_ids);
            }

            //根据商品是否参加活动分组
            foreach ($rules as $rule_id => $rule) {
                //只有优惠规则才在购物车展示并计算
                if ($rule['coupon_type'] == 1) {
                    //如果优惠活动，该用户已使用达到单个用户享受次数上线，则不可再享受
                    //获取Redis中某个用户某个优惠活动享受次数的key
                    $enjoy_times_key = Keys::getEnjoyTimesKey($this->customer->getCustomerId(), $rule_id);
                    //在Redis中取得数据
                    $enjoy_times = Tools::getRedis()->get($enjoy_times_key);
                    if ($rule['rule_uses_limit'] > 0 && $enjoy_times >= $rule['rule_uses_limit']) {
                        continue;
                    }

                    foreach ($productList as $key => $product) {
                        if (SpecialProduct::isSecKillProductByIdTypeOld($product['product_id'], $product['type'])) {
                            if (!empty($cartSeckillProducts[$product['product_id']])) {
                                $product['num'] = $cartSeckillProducts[$product['product_id']]['num'];
                                $product['type'] = 1;
                                $product['seckill_lefttime'] = $cartSeckillProducts[$product['product_id']]['left_time'];
                            }
                        } else {
                            $product['num'] = $cartNormalProducts[$product['product_id']]['num'];
                        }

                        $product['purchased_qty'] = Tools::getPurchasedQty(
                            $this->customer->getCustomerId(),
                            $this->customer->getCity(),
                            $product['product_id']
                        );
                        if ($product['rule_id'] == $rule_id) {
                            $promotionGroupProducts[$rule_id][] = $product;
                            unset($productList[$key]);
                        }
                    }
                }
            }

            // 没有参加活动的商品
            foreach ($productList as $key => $product) {
                if (SpecialProduct::isSecKillProductByIdTypeOld($product['product_id'], $product['type'])) {
                    if (!empty($cartSeckillProducts[$product['product_id']])) {
                        $product['num'] = $cartSeckillProducts[$product['product_id']]['num'];
                        $product['type'] = 1;
                        $product['seckill_lefttime'] = $cartSeckillProducts[$product['product_id']]['left_time'];
                    }
                } else {
                    $product['num'] = $cartNormalProducts[$product['product_id']]['num'];
                }

                $product['purchased_qty'] = Tools::getPurchasedQty(
                    $this->customer->getCustomerId(),
                    $this->customer->getCity(),
                    $product['product_id']
                );
                $promotionGroupProducts['cart' . $key][] = $product;
            }

            $promotionProducts = [];
            foreach ($promotionGroupProducts as $key => $productArray) {
                $productGroupList = [];
                if (is_numeric($key)) {
                    $promotion = isset($rules[$key]) ? $rules[$key] : null;
                    if (!$promotion) {
                        //无优惠活动的商品
                        foreach ($productArray as $product) {
                            $list = [];
                            array_push($list, $product);
                            $productGroupList['promotion'] = $promotion;
                            $productGroupList['list'] = $list;
                            array_push($promotionProducts, $productGroupList);
                        }

                    } else if ($promotion['type'] == 1) {
                        //单品级活动
                        foreach ($productArray as $product) {
                            $list = [];
                            array_push($list, $product);
                            $productGroupList['promotion'] = $promotion;
                            $productGroupList['list'] = $list;
                            array_push($promotionProducts, $productGroupList);
                        }
                    } else {
                        $list = [];
                        //多品级活动
                        foreach ($productArray as $product) {
                            array_push($list, $product);
                        }
                        $productGroupList['promotion'] = $promotion;
                        $productGroupList['list'] = $list;
                        array_push($promotionProducts, $productGroupList);
                    }
                } else {
                    //没有参加优惠活动的商品
                    $promotion = null;
                    $list = [];
                    foreach ($productArray as $product) {
                        array_push($list, $product);
                        $productGroupList['promotion'] = $promotion;
                        $productGroupList['list'] = $list;
                        array_push($promotionProducts, $productGroupList);
                    }
                }
            }

            if ($wholesalerPromotions && isset($wholesalerPromotions[$wholesaler_id])) {
                $wholesaler_cart['wholesaler_promotion'] = $wholesalerPromotions[$wholesaler_id];
            }

            if (isset($storeDetail['min_trade_amount']) && $storeDetail['min_trade_amount'] != 0) {
                $wholesaler_cart['tips'] = '每天首单满' . ceil($storeDetail['min_trade_amount']) . '元起送';
                $wholesaler_cart['min_trade_amount'] = $orderCountToday > 0 ? 0 : ceil($storeDetail['min_trade_amount']);
            } else {
                $wholesaler_cart['tips'] = '本单免起送价';
                $wholesaler_cart['min_trade_amount'] = 0;
            }

            $wholesaler_cart['wholesaler_id'] = $wholesaler_id;
            $wholesaler_cart['wholesaler_name'] = $storeDetail['wholesaler_name'];
            $wholesaler_cart['product_group'] = $promotionProducts;
            $data[] = $wholesaler_cart;
        }

        $cartItemsData = array('data' => $data);
        $cartItems->setFrom(Tools::pb_array_filter($cartItemsData));
        //返回数组
        return $cartItems;
    }

    /**
     * Function: updateCartItems
     * Author: Jason Y. Wang
     * 更新购物车中的商品信息
     * @param mixed $products
     * @throws \Exception
     * @return string
     */
    public function updateCartItems($products)
    {
        $productExistFlag = 0;
        $productQtyLowFlag = 0;
        $productRestrictDaily = 0;
        $productName = '';
        //修改redis数据
        foreach ($products as $key => $product) {
            /** @var Product $product */
            $wholesalerId = $product->getWholesalerId();
            $productId = $product->getProductId();
            $num = $product->getNum();
            //传入数据为空时，不做处理 num只处理数字
            if (empty($wholesalerId) || empty($productId) || empty($num)
                || filter_var($wholesalerId, FILTER_VALIDATE_INT) === false
                || filter_var($productId, FILTER_VALIDATE_INT) === false
                || filter_var($num, FILTER_VALIDATE_INT) === false
            ) {
                Exception::systemNotFound();
            }

            $wholesalersInfo = Redis::getWholesalers($wholesalerId);
            $wholesalerInfo = unserialize($wholesalersInfo[$wholesalerId]);

            if (!$wholesalerInfo) {
                //如果load不到店铺，则不处理这个商品
                Exception::storeNotExisted();
            }

            /* 得到商品模型，秒杀和其他普通商品区别开来 */
            /** @var Products $productModel */
            $productModel = $this->getProductModel($this->customer->getCity(), $productId);
            $productToCart = $productModel::findOne(['entity_id' => $productId]);
            if (!$productToCart) {
                $productExistFlag++;
                continue;
            }

            if ($productToCart->status != 1 || $productToCart->state != 2) {
                $productExistFlag++;
                $productName = $productToCart->name;
                continue;
            }

            if ($num > $productToCart->qty) {
                $productQtyLowFlag++;
                $productName = $productToCart->name;
                continue;
            }

            /* 判断限购 */
            if (!(new ProductDecorator($productToCart))->checkRestrictDaily($num, $this->customer)) {
                $productRestrictDaily++;
                $productName = $productToCart->name;
                continue;
            }

            if ($num > 0) {
                /* 先判断是否是秒杀商品，不是秒杀商品，则更新普通商品的购物车 */
                if (SpecialProduct::isSpecialProduct($productId)) {
                    if (SpecialProduct::isSecKillProductByIdTypeOld($productId, $product->getType())) {
                        if (!$this->updateSecKillProductCartNum($productToCart, $product->getNum())) {
                            $productQtyLowFlag++;
                            $productName = $productToCart->name;
                        }
                    } else if ($product->getType() == 2) { //特殊商品旧版本type
                        //特殊专题的商品，算做是普通商品
                        //$this->updateNormalProductCartNum($productToCart, $product->getNum());
                        $this->updateNormalProductCartNum2($this->customer, $product);
                    } else {
                        Tools::log('xxxxxx', 'updateItems.log');
                    }
                } else {
                    //$this->updateNormalProductCartNum($productToCart, $product->getNum());
                    $this->updateNormalProductCartNum2($this->customer, $product);
                }
            }
        }

        /* 修改则更新到redis */
        Tools::getRedis()->set($this->normalCartKey, serialize($this->redisCartItems));

        if ($productQtyLowFlag) {
            /* 坑爹的需求，1种商品显示名称，2种及以上显示N种 */
            if ($productQtyLowFlag == 1) {
                throw new \Exception(sprintf('“%s”库存不足，无法加入购物车', $productName));
            } else {
                throw new \Exception($productQtyLowFlag . '种商品库存不足，无法加入购物车');
            }
        }

        if ($productExistFlag) {
            if ($productExistFlag == 1) {
                throw new \Exception(sprintf('“%s”已下架，无法加入购物车', $productName));
            } else {
                throw new \Exception($productExistFlag . '种商品已下架，无法加入购物车');
            }
        }

        if ($productRestrictDaily) {
            if ($productRestrictDaily == 1) {
                throw new \Exception(sprintf('“%s”超过限购数，无法加入购物车', $productName));
            } else {
                throw new \Exception($productRestrictDaily . '种商品超过限购数，无法加入购物车');
            }
        }

        return true;
    }

    /**
     * Function: removeCartItems
     * Author: Jason Y. Wang
     * 删除购物车中的商品信息
     * @param $products
     */
    public function removeCartItems($products)
    {
        //修改redis数据
        foreach ($products as $key => $product) {
            /** @var Product $product */
            $wholesalerId = $product->getWholesalerId();
            $productId = $product->getProductId();

            if (SpecialProduct::isSecKillProductByIdTypeOld($productId, $product->getType())) {
                $this->removeSecKillProduct($product);
            } else {
                //删除商品
                if (isset($this->redisCartItems[$wholesalerId])) {
                    if (isset($this->redisCartItems[$wholesalerId]['list'][$productId])) {
                        //删除商品
                        unset($this->redisCartItems[$wholesalerId]['list'][$productId]);
                        if (count($this->redisCartItems[$wholesalerId]['list']) == 0) {
                            //是否删除店铺
                            unset($this->redisCartItems[$wholesalerId]);
                        }
                    }
                }
            }
        }

        Tools::getRedis()->set($this->normalCartKey, serialize($this->redisCartItems));
    }

    protected function updateNormalProductCartNum2($customer, $product)
    {
        $cart = new \service\components\shoppingcart\ShoppingCart($customer);
        $cart->updateItems([$product]);
    }

    /**
     * @param Products $product
     * @param integer $num
     */
    protected function updateNormalProductCartNum($product, $num)
    {
        /** @var Products $product */
        $wholesalerId = $product->wholesaler_id;
        $productId = $product->entity_id;
        if (isset($this->redisCartItems[$wholesalerId])) {
            //购物车中已有店铺修改商品
            if (isset($this->redisCartItems[$wholesalerId]['list'][$productId])) {
                //修改已有商品   并将已修改的店铺放到购物车的第一位
                $this->redisCartItems[$wholesalerId]['list'][$productId]['num'] = $num;
                //新修改的商品排到第一位
                $productCart = [$productId => $this->redisCartItems[$wholesalerId]['list'][$productId]];
                $this->redisCartItems[$wholesalerId]['list'] = $productCart + $this->redisCartItems[$wholesalerId]['list'];
                //店铺的顺序排到第一位
                $wholesalerCart = [$wholesalerId => $this->redisCartItems[$wholesalerId]];
                $this->redisCartItems = $wholesalerCart + $this->redisCartItems;
            } else {
                //新增商品，数量不能为0
                $productItem = [
                    'productId' => $productId,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'num' => $num
                ];
                $this->redisCartItems[$wholesalerId]['list'][$productId] = $productItem;
                //新修改的商品排到第一位
                $productCart = [$productId => $this->redisCartItems[$wholesalerId]['list'][$productId]];
                $this->redisCartItems[$wholesalerId]['list'] = $productCart + $this->redisCartItems[$wholesalerId]['list'];
                //店铺的顺序排到第一位
                $wholesalerCart = [$wholesalerId => $this->redisCartItems[$wholesalerId]];
                $this->redisCartItems = $wholesalerCart + $this->redisCartItems;
            }
        } else {
            //商品信息
            $productItem = [
                'productId' => $productId,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'num' => $num
            ];
            //店铺信息
            $wholesalerCart = [
                'list' => [
                    $productId => $productItem,
                ],
            ];
            $this->redisCartItems = [$wholesalerId => $wholesalerCart] + $this->redisCartItems;
        }
    }

    /**
     * 移除购物车秒杀商品
     *
     * @param Product $product
     * @throws \Exception
     * @return boolean
     */
    protected function removeSecKillProduct($product)
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
     * @return boolean
     * @throws \Exception
     */
    protected function updateSecKillProductCartNum($product, $num)
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
        $leftSeconds = strtotime($activity['end_time']) - ToolsAbstract::getDate()->timestamp();
        $leftSeconds = $leftSeconds > 0 ? $leftSeconds : 0;
        Tools::log($actId, 'updateCartItems.log');
        Tools::log($productId, 'updateCartItems.log');
        Tools::log($num, 'updateCartItems.log');
        Tools::log($customerId, 'updateCartItems.log');
        Tools::log($areaId, 'updateCartItems.log');
        Tools::log($leftSeconds, 'updateCartItems.log');
        return ToolsAbstract::updateCartSecKillProduct($actId, $productId, $num, $customerId, $areaId, $leftSeconds);
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

    /**
     * @return SecKillActivity
     */
    private function getCurSecKillActivity()
    {
        return SecKillActivity::getCityCurActivity($this->customer->getCity(), SeckillHelper::IS_CACHE);
    }
}
