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
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\getProductRequest;
use service\message\merchant\getProductResponse;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;
use framework\db\ActiveRecord;


class getProduct extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var getProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = null;
        $wholesalerIds = [];
        if ($request->getCustomerId() && $request->getAuthToken()) {
            $customer = $this->_initCustomer($request);
            $wholesalerIds = self::getWholesalerIdsByAreaId($customer->getAreaId());
        }

        if ($customer) {
            $merchantInfo = $this->getStoreDetailBrief([$request->getWholesalerId()], $customer->getAreaId());
            if(empty($merchantInfo[$request->getWholesalerId()])){

            }
            $merchantInfo = $merchantInfo[$request->getWholesalerId()];
        } else {
            // 查商家
            /** @var ActiveRecord $merchantModel */
            $merchantModel = $this->getWholesaler($request->getWholesalerId());
            $merchantInfo = $this->getStoreDetail($merchantModel);
        }

        if(empty($merchantInfo)){
            Exception::productNotInDeliveryRange();
        }

        // redis查询(数据库级的缓存)
        $city = $merchantInfo['city'];
        $productIds = $request->getProductIds();
        // 组装返回
        $response = $this->response();
        if (count($productIds) > 0) {
            $result = [];
            /** @var Products $item */
            foreach ($productIds as $key => $productId) {
                if (!$data = self::getProductData($productId, $city)) {
                    continue;
                }

                $productData = $data[$productId];
                if ($customer) {
                    if (!in_array($productData['wholesaler_id'], $wholesalerIds)) {
                        Exception::productNotInDeliveryRange();
                    }
                    // 购买数量
                    $productData['purchased_qty'] = Tools::getPurchasedQty(
                        $customer->getCustomerId(),
                        $customer->getCity(),
                        $productId
                    );
                }

                /* 如果是特殊商品，则把id改为原来的关联商品id */
                $originProductData = $productData;
                if (SpecialProduct::isSpecialProduct($productId)) {
                    $originProductData['entity_id'] = $data['ori_product_id'];
                    /* 秒杀商品返回倒计时还有状态值 */
                    if (SpecialProduct::isSecKillProduct($productData,'product_id')) {
                        $productData['seckill_status'] = $data['sk_status'];
                        $productData['seckill_status_str'] = $data['sk_status_str'];
                        $productData['seckill_lefttime'] = $data['sk_left_time'];
                    }
                }

                // 查询相关商品
                $recommendNum = intval($request->getRecommendNum());
                if ($recommendNum) {
                    $rProductList = $this->getRelatedProducts($city, $originProductData, $recommendNum);
                } else {
                    $rProductList = $this->getRelatedProducts($city, $originProductData);
                }
                Tools::log($data,'getProduct.log');
                $productData['recommend_list'] = (new ProductHelper())
                    ->initWithProductArray($rProductList, $city)
                    ->getTags()
                    ->getData();

                unset($productData['ori_product_id'], $productData['sp_type']);
                array_push($result, $productData);
            }

            if (count($result) == 0) {
                throw new \Exception('商品已经下架', 4501);
            }

            $result = [
                'product_list' => $result,
                'wholesaler_info' => $merchantInfo,
            ];
            $response->setFrom(Tools::pb_array_filter($result));
        } else {
            throw new \Exception('商品已经下架', 4501);
        }
        return $response;
    }

    /**
     * @param integer $productId
     * @return array|null
     */
    private static function getProductData($productId, $city)
    {
        if (SpecialProduct::isSpecialProduct($productId)) { // 特殊商品
            /** @var SpecialProduct $product*/
            $product = SpecialProduct::findOne([
                'entity_id' => $productId,
                'status' => SpecialProduct::STATUS_ENABLED,
            ]);

            if (!$product) {
                return null;
            }

            // 格式化详情数组
            $data = (new ProductHelper())->initWithProductArray([$product], $city, '600x600')
                ->getMoreProperty()
                ->getTags(['detail' => 1])
                ->getParameters()
                ->getCouponReceive()
                ->getData();

            /* 如果有数据返回，则要返回额外的信息（来源商品id等）。秒杀商品还要返回状态倒计时等 */
            if (!empty($data) && is_array($data)) {
                $data['ori_product_id'] = $product->ori_product_id;
                if ($product->type2 == SpecialProduct::TYPE_SECKILL) {
                    /* 获取活动信息来判断状态，活动不存在显示已结束 */
                    $activity = SecKillActivity::findOne([
                        'entity_id' => $product->activity_id,
                        'status' => SecKillActivity::STATUS_ENABLED
                    ]);
                    $status = $activity ? SecKillActivity::getStatusInfo($activity) : SecKillActivity::INT_STATUS_END;
                    if($status == SecKillActivity::INT_STATUS_END) {
                        $data['sk_status'] = SpecialProduct::STATUS_END;
                        $data['sk_left_time'] = 0;
                    } elseif ($status == SecKillActivity::INT_STATUS_PREPARED) {
                        $data['sk_status'] = SpecialProduct::STATUS_PREPARED;
                        $data['sk_left_time'] = SecKillActivity::getLeftTime($activity, $status);
                    } else {
                        if($stocks = ToolsAbstract::getSecKillProductsStocks($activity->entity_id, [$productId])) {
                            $data['sk_status'] = $stocks[$productId] > 0 ? SpecialProduct::STATUS_STARTED_HAS_STOCK
                                : SpecialProduct::STATUS_STARTED_NO_STOCK;
                        } else {
                            $data['sk_status'] = SpecialProduct::STATUS_STARTED_NO_STOCK;
                        }
                        $data['sk_left_time'] = SecKillActivity::getLeftTime($activity, $status);
                    }
                    $data['sk_status_str'] = SpecialProduct::getStatusStr($data['sk_status']);
                }
            }
        } else {
            Tools::log($productId,'getProduct.log');
            // 格式化详情数组
            $data = (new ProductHelper())->initWithProductIds($productId, $city, [], '600x600')
                ->getMoreProperty()
                ->getTags(['detail' => 1])
                ->getParameters()
                ->getCouponReceive()
                ->getData();

        }
        return $data;
    }

    public static function request()
    {
        return new getProductRequest();
    }

    public static function response()
    {
        return new getProductResponse();
    }
}