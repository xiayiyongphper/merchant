<?php
namespace service\models;

use service\components\Tools;
use service\message\common\Product;
use service\message\merchant\searchProductResponse;
use service\message\merchant\wishlistRequest;
use service\resources\MerchantResourceAbstract;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-27
 * Time: 下午6:30
 */

/**
 * 愿望清单，里面包含浏览历史、购买历史、收藏历史
 * Class wishlist
 * @package service\resources\merchant\v1
 */
final class Wishlist
{
    const OFTEN_BUY_COLLECTION = 'customer_wishlist';
    /**
     * 浏览记录
     */
    const VIEW_HISTORY = 0b00000001;
    /**
     * 购买记录
     */
    const PURCHASE_HISTORY = 0b00000010;
    /**
     * 收藏记录
     */
    const COLLECT_HISTORY = 0b00000100;


    const NONE_HISTORY = 0b00000000;


    public static function addCollect($products, $customerId, $city)
    {
        /** @var wishlistRequest $request */
        if (is_array($products) && count($products) > 0) {
            $redis = Tools::getRedis();
            $key = self::getKey($customerId, $city);
            /** @var Product $product */
            foreach ($products as $product) {
                $hashKey = self::getHashKey($product->getProductId());
                if ($redis->hExists($key, $hashKey)) {
                    $json = $redis->hGet($key, $hashKey);
                    if (self::validJsonSchema($json)) {
                        $value = json_decode($json, true);
                        $value['type'] |= self::COLLECT_HISTORY;
                        $value['collect_at'] = time();
                        $value['updated_at'] = time();
                        if ($value['type'] > 0) {
                            $redis->hSet($key, $hashKey, json_encode($value));
                        } else {
                            $redis->hDel($key, $hashKey);
                        }
                    } else {
                        $value = [
                            'type' => self::COLLECT_HISTORY,
                            'product_id' => intval($product->getProductId()),
                            'wholesaler_id' => intval($product->getWholesalerId()),
                            'created_at' => time(),
                            'updated_at' => time(),
                            'collect_at' => time(),
                        ];
                        $redis->hSet($key, $hashKey, json_encode($value));
                    }

                } else {
                    $value = [
                        'type' => self::COLLECT_HISTORY,
                        'product_id' => intval($product->getProductId()),
                        'wholesaler_id' => intval($product->getWholesalerId()),
                        'created_at' => time(),
                        'updated_at' => time(),
                        'collect_at' => time(),
                    ];
                    $redis->hSet($key, $hashKey, json_encode($value));
                }
            }
        }
    }


    public static function removeCollect($products, $customerId, $city)
    {
        /** @var wishlistRequest $request */
        if (is_array($products) && count($products) > 0) {
            $redis = Tools::getRedis();
            $key = self::getKey($customerId, $city);
            /** @var Product $product */
            foreach ($products as $product) {
                $hashKey = self::getHashKey($product->getProductId());
                if ($redis->hExists($key, $hashKey)) {
                    $json = $redis->hGet($key, $hashKey);
                    if (self::validJsonSchema($json)) {
                        $value = json_decode($json, true);
                        $value['type'] &= (~self::COLLECT_HISTORY);
                        $value['updated_at'] = time();
                        if ($value['type'] > 0) {
                            $redis->hSet($key, $hashKey, json_encode($value));
                        } else {
                            $redis->hDel($key, $hashKey);
                        }
                    } else {
                        $redis->hDel($key, $hashKey);
                    }
                }
            }
        }
    }

    /**
     * 添加购买记录
     */
    public static function addPurchaseHistory($products, $customerId, $city)
    {
        /** @var wishlistRequest $request */
        if (is_array($products) && count($products) > 0) {
            $redis = Tools::getRedis();
            $key = self::getKey($customerId, $city);
            /** @var Product $product */
            foreach ($products as $product) {
                $hashKey = self::getHashKey($product->getProductId());
                if ($redis->hExists($key, $hashKey)) {
                    $json = $redis->hGet($key, $hashKey);
                    if (self::validJsonSchema($json)) {
                        $value = json_decode($json, true);
                        $value['type'] |= self::PURCHASE_HISTORY;
                        $value['updated_at'] = time();
                        $value['purchase_at'] = time();
                        if ($value['type'] > 0) {
                            $redis->hSet($key, $hashKey, json_encode($value));
                        } else {
                            $redis->hDel($key, $hashKey);
                        }
                    } else {
                        $value = [
                            'type' => self::PURCHASE_HISTORY,
                            'product_id' => intval($product->getProductId()),
                            'wholesaler_id' => intval($product->getWholesalerId()),
                            'created_at' => time(),
                            'updated_at' => time(),
                            'purchase_at' => time(),
                        ];
                        $redis->hSet($key, $hashKey, json_encode($value));
                    }

                } else {
                    $value = [
                        'type' => self::PURCHASE_HISTORY,
                        'product_id' => intval($product->getProductId()),
                        'wholesaler_id' => intval($product->getWholesalerId()),
                        'created_at' => time(),
                        'updated_at' => time(),
                        'purchase_at' => time(),
                    ];
                    $redis->hSet($key, $hashKey, json_encode($value));
                }
            }
        }
    }

    /**
     * 添加浏览记录
     */
    public static function addViewHistory($products, $customerId, $city)
    {
        /** @var wishlistRequest $request */
        if (is_array($products) && count($products) > 0) {
            $redis = Tools::getRedis();
            $key = self::getKey($customerId, $city);
            /** @var Product $product */
            foreach ($products as $product) {
                $hashKey = self::getHashKey($product->getProductId());
                if ($redis->hExists($key, $hashKey)) {
                    $json = $redis->hGet($key, $hashKey);
                    if (self::validJsonSchema($json)) {
                        $value = json_decode($json, true);
                        $value['type'] |= self::VIEW_HISTORY;
                        $value['view_at'] = time();
                        $value['updated_at'] = time();
                        if ($value['type'] > 0) {
                            $redis->hSet($key, $hashKey, json_encode($value));
                        } else {
                            $redis->hDel($key, $hashKey);
                        }
                    } else {
                        $value = [
                            'type' => self::VIEW_HISTORY,
                            'product_id' => intval($product->getProductId()),
                            'wholesaler_id' => intval($product->getWholesalerId()),
                            'created_at' => time(),
                            'updated_at' => time(),
                            'view_at' => time(),
                        ];
                        $redis->hSet($key, $hashKey, json_encode($value));
                    }
                } else {
                    $value = [
                        'type' => self::VIEW_HISTORY,
                        'product_id' => intval($product->getProductId()),
                        'wholesaler_id' => intval($product->getWholesalerId()),
                        'created_at' => time(),
                        'updated_at' => time(),
                        'view_at' => time(),
                    ];
                    $redis->hSet($key, $hashKey, json_encode($value));
                }
            }
        }
    }

    public static function getKey($customerId, $city)
    {
        return self::OFTEN_BUY_COLLECTION . '_' . $customerId . '_' . $city;
    }

    public static function getHashKey($productId)
    {
        return $productId;
    }

    public static function validJsonSchema($json)
    {
        return Tools::validJsonSchema($json, 'wishlist-schema.json');
    }

    public static function getType($customerId, $city, $productId)
    {
        $key = self::getKey($customerId, $city);
        $hashKey = self::getHashKey($productId);
        $redis = Tools::getRedis();
        if ($redis->hExists($key, $hashKey)) {
            $json = $redis->hGet($key, $hashKey);
            if (self::validJsonSchema($json)) {
                $value = json_decode($json, true);
                return $value['type'];
            }
        }
        return self::NONE_HISTORY;
    }

    /**
     * @param $type
     * @return bool
     */
    public static function isViewHistory($type)
    {
        return boolval($type & self::VIEW_HISTORY);
    }

    /**
     * @param $type
     * @return bool
     */
    public static function isCollectHistory($type)
    {
        return boolval($type & self::COLLECT_HISTORY);
    }

    /**
     * @param $type
     * @return bool
     */
    public static function isPurchaseHistory($type)
    {
        return boolval($type & self::PURCHASE_HISTORY);
    }
}