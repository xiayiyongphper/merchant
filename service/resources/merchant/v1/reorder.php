<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/9/11
 * Time: 17:47
 */

namespace service\resources\merchant\v1;


use common\models\Products;
use common\models\SalesFlatOrderItem;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\customer\CustomerResponse;
use service\message\merchant\reorderRequest;
use service\message\merchant\reorderResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

/**
 * 再次购买
 * Class reorder
 * @package service\resources\merchant\v1
 */
class reorder extends MerchantResourceAbstract
{
    const STATUS_ON_SHELVES = 1;
    const STATUS_OFF_SHELVES = 2;
    const STATUS_DELETED = 3;
    const STATUS_SOLD_OUT = 4;

    public function run($data)
    {
        /** @var reorderResponse $request */
        $request = self::request();
        $request->parseFromString($data);

        if (empty($request->getOrderId())) {
            throw new \Exception('缺少必要参数');
        }

        /** @var CustomerResponse $customer */
        $customer = $this->_initCustomer($request);
        /** @var SalesFlatOrderItem[] $orderItems */
        $orderItems = SalesFlatOrderItem::findAll([
            'order_id' => $request->getOrderId(),
            'parent_id' => 0
        ]);

        if (empty($orderItems)) {
            throw new \Exception('参数错误');
        }

        /* 查找最新的商品信息 */
        $normalProductIds = [];
        $specialProductIds = [];
        $respProducts = [];
        foreach ($orderItems as $orderItem) {
            if (isset($respProducts[$orderItem->product_id])) {
                continue;
            }

            if (SpecialProduct::isSpecialProduct($orderItem->product_id)) {
                $specailProductIds[] = $orderItem->product_id;
            } else {
                $normalProductIds[] = $orderItem->product_id;
            }

            $respProducts[$orderItem->product_id] = [
                'product_id' => $orderItem->product_id,
                'wholesaler_id' => $orderItem->wholesaler_id,
                'name' => $orderItem->name,
                'price' => $orderItem->price,
                'original_price' => $orderItem->original_price,
                'status' => self::STATUS_DELETED,
                'num' => $orderItem->qty,   // 购买数量
                'qty' => 0,
                'image' => Tools::getImage($orderItem->image, '388x388'),
            ];

            /* 因为两个表的字段都用type！！！这里是特殊商品用的，秒杀相关字段，没有则不设置 */
            if (SpecialProduct::isSpecialProduct($orderItem->product_id) && !empty($orderItem->product_type)) {
                $respProducts[$orderItem->product_id]['type'] = $orderItem->product_type;
            }
        }

        $normalProducts = [];
        $specialProducts = [];
        if ($normalProductIds) {
            $normalProducts = (new ProductHelper())
                ->initWithProductIds($normalProductIds, $customer->getCity(), [], '388x388', false)->getData();
        }
        if ($specialProductIds) {
            if ($cursor = SpecialProduct::findAll(['entity_id' => $specialProductIds])) {
                $specialProducts = (new ProductHelper())
                    ->initWithProductArray($cursor, $customer->getCity())->getData();
            }
        }

        $products = $normalProducts + $specialProducts;
        foreach ($products as $productId => $product) {
            $respProducts[$productId] = [
                'product_id' => $productId,
                'wholesaler_id' => $product['wholesaler_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'original_price' => $product['original_price'],
                'status' => $this->getProductStatus($product),
                'qty' => $product['qty'],
                'num' => $respProducts[$productId]['num'],  // 购买数量
                'image' => $product['image'],
                'type' => isset($product['type2']) ? $product['type2'] : Products::TYPE_SIMPLE,
                'minimum_order' => $product['minimum_order'],
                'sale_unit' => $product['sale_unit']
            ];
        }

        $response = self::response();
        $response->setFrom(['products' => $respProducts]);
        return $response;
    }

    /**
     * @param array $product
     * @return int
     */
    private function getProductStatus($product)
    {
        if ($product['status'] == 0 || $product['status'] == 2) {
            return self::STATUS_OFF_SHELVES;
        } else {
            if ($product['qty'] <= 0) {
                return self::STATUS_SOLD_OUT;
            }
        }
        return self::STATUS_ON_SHELVES;
    }

    /**
     * @return reorderRequest
     */
    public static function request()
    {
        return new reorderRequest();
    }

    /**
     * @return reorderResponse
     */
    public static function response()
    {
        return new reorderResponse();
    }
}