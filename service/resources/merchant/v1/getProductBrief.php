<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use common\models\Products;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Redis;
use service\components\Tools;
use service\message\common\Product;
use service\message\merchant\getProductBriefRequest;
use service\message\merchant\getProductBriefResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;


class getProductBrief extends MerchantResourceAbstract
{
    /**
     * 活动不存在或已过期
     */
    const EX_ACTIVITY_NOT_FOUND = 66666;
    /**
     * 秒杀商品过期
     */
    const EX_SK_PRODUCT_EXPIRED = self::EX_ACTIVITY_NOT_FOUND + 1;

    public function run($data)
    {
        /** @var getProductBriefRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $response = $this->response();
        $productIds = $request->getProductIds();

        $normalProductIds = [];
        $specialProductIds = [];
        foreach ($productIds as $productId) {
            if (SpecialProduct::isSpecialProduct($productId)) {
                $specialProductIds[] = $productId;
            } else {
                $normalProductIds[] = $productId;
            }
        }

        $normalProducts = [];
        if ($normalProductIds) { // 普通商品
            $normalProducts = (new ProductHelper())
                ->initWithProductIds($normalProductIds, $request->getCity())
                ->getTags()
                ->getData();
        }

        /* 特殊商品处理逻辑 */
        $specialProducts = [];
        if ($specialProductIds) {
            $specialProducts = $this->getSpecialProducts($specialProductIds, $request);
        }

        /* 合并商品列表 */
        $products = $specialProducts + $normalProducts;
        $result = [
            'product_list' => Tools::pb_array_filter($products)
        ];

        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    /**
     * @param array $specialProductIds
     * @param getProductBriefRequest $request
     * @return Product[]
     * @throws \Exception
     */
    private function getSpecialProducts($specialProductIds, $request)
    {
        $specialProducts = SpecialProduct::findAll([
            'status' => SpecialProduct::STATUS_ENABLED,
            'entity_id' => $specialProductIds
        ]);

        /* 如果是秒杀商品，则判断是不是当前活动，如果不是，则过滤掉；是则要判断库存 */
        $invalidProduct = []; // 格式：[productId1 => productName1, ...]
        $checkStockProducts = [];   // 格式：[productId1 => productName1, ...]
        $curSecKillActivity = false;
        if ($specialProducts) {
            foreach ($specialProducts as $specialProduct) {
                /* 目前这里只处理秒杀商品 */
                if ($specialProduct->type2 != SpecialProduct::TYPE_SECKILL) {
                    continue;
                }

                if ($curSecKillActivity === false) { // 如果没有查找过，则查找活动
                    $curSecKillActivity = SecKillActivity::getCityCurActivity($request->getCity());
                }

                /** @var SecKillActivity $curSecKillActivity */
                if (!$curSecKillActivity || $curSecKillActivity['entity_id'] != $specialProduct->activity_id) {
                    $invalidProduct[$specialProduct->entity_id] = Products::getProductNameText($specialProduct);
                } else {
                    $checkStockProducts[$specialProduct->entity_id] = Products::getProductNameText($specialProduct);
                }
            }

            /* 检查黑名单和灰名单 */
            if ($curSecKillActivity && !SeckillHelper::checkAccess(
                    $curSecKillActivity,
                    $request->getCustomerId(),
                    $request->getCity(),
                    $request->getAreaId()
                )) {
                throw new \Exception(json_encode($checkStockProducts + $invalidProduct), self::EX_ACTIVITY_NOT_FOUND);
            }

            /* 判断库存，活动不存在则$checkStockProductIds一定为空 */
            if ($checkStockProducts) {
                $stocks = ToolsAbstract::getUserCartSecKillProducts(
                    $curSecKillActivity['entity_id'],
                    $request->getCustomerId(),
                    $request->getAreaId()
                );
                foreach ($checkStockProducts as $productId => $productName) {
                    if (empty($stocks[$productId])) {
                        $invalidProduct[$productId] = $productName;
                    }
                }
            }
        }

        /* 有不可用的商品，则抛异常，返回的是商品ID数组的json串 */
        if ($invalidProduct) {
            if (!$curSecKillActivity) {
                $errCode = self::EX_ACTIVITY_NOT_FOUND;
            } else {
                $errCode = self::EX_SK_PRODUCT_EXPIRED;
            }
            throw new \Exception(json_encode($invalidProduct), $errCode);
        }

        /* 格式化输出 */
        $specialProducts = (new ProductHelper())
            ->initWithProductArray($specialProducts, $request->getCity())
            ->getTags(['show_seckill' => 1])
            ->getData();

        return $specialProducts;
    }

    public static function request()
    {
        return new getProductBriefRequest();
    }

    public static function response()
    {
        return new getProductBriefResponse();
    }
}