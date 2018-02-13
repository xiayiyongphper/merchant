<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/26
 * Time: 10:59
 */

namespace service\resources\merchant\v1;

use common\models\Products;
use service\message\common\CalculateData;
use service\message\common\DiscountNote;
use service\message\common\Product;
use service\message\common\PromotionInfo;
use service\message\customer\CustomerResponse;
use service\message\sales\CalculatePromotionsRequest;
use service\message\sales\CalculatePromotionsResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;

class calculatePromotions extends MerchantResourceAbstract
{
    public function run($data){
        /** @var CalculatePromotionsRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $items = $request->getItems();
        $response = new CalculatePromotionsResponse();
        
        /** @var CalculateData $item */
        foreach ($items as $item){
            $discountNote = new DiscountNote();
            $products = $item->getProductList();
            $wholesaler_id = $item->getWholesalerId();
            $promotion = $this->calculatePrice($customer,$products,$wholesaler_id);
            //现阶段只有返现优惠，当有其他优惠时，这里需要修改
            if($promotion->getText()){
                $discountNote->appendPromotions($promotion);
            }
            $discountNote->setWholesalerId($wholesaler_id);
            $response->appendDiscountNote($discountNote);
        }
        return $response;
    }

    /**
     * @param CustomerResponse $customer
     * @param $products
     * @param $wholesaler_id
     * @return PromotionInfo
     */
    protected function calculatePrice($customer,$products,$wholesaler_id){

        $data = [];
        /** @var Product $product */
        foreach ($products as $product){
            $data[$product->getProductId()] = $product->getNum();
        }
        $product_ids = array_keys($data);
        $products = (new ProductHelper())->initWithProductIds($product_ids,$customer->getCity())
            ->getTags()
            ->getData();
//        $products = self::getProductsArrayPro2($product_ids,$customer->getCity());
        $off_money = 0;
        $promotionModel = new PromotionInfo();
        /** @var Products $product */
        foreach ($products as $product){
            if(isset($product['rebates_all']) && $product['rebates_all'] > 0){
                $off_money += $product['price'] * $data[$product['product_id']] * ($product['rebates_all']/100);
            }
        }
        if($off_money > 0){
            $promotion = '本单预计可返现¥'.number_format($off_money,2,null,'');
            $promotionModel->setText($promotion);
        }
        return $promotionModel;
    }

    public static function request(){
        return new CalculatePromotionsRequest();
    }

    public static function response(){
        return new CalculatePromotionsResponse();
    }
}