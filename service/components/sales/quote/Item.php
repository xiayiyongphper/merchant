<?php

namespace service\components\sales\quote;

use common\models\Products;
use service\components\Tools;
use common\models\ProductDecorator;
use service\message\customer\CustomerResponse;


class Item
{
    private $product;
    private $grandTotal;
    /** @var  CustomerResponse $customer */
    private $customer;
    private $rebatesMoney = 0;
    private $subTotal = 0;

    /**
     * Author Jason Y.Wang
     * @param $customer
     * @param $product
     * @return $this 计算优惠时，使用的信息
     * 计算优惠时，使用的信息
     */
    public function setProduct($customer, $product)
    {
        $this->customer = $customer;
        $this->product = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'wholesaler_id' => $product['wholesaler_id'],
            'wholesaler_name' => isset($product['wholesaler_name']) ? $product['wholesaler_name'] : '',
            'barcode' => $product['barcode'],
            'original_price' => $product['original_price'],
            'price' => $product['price'],
            'special_price' => $product['special_price'],
            'status' => $product['status'],
            'rule_id' => $product['rule_id'],
            'qty' => $product['qty'] > 0 ? $product['qty'] : 0,
            'rebates_all' => $product['rebates_all'],
            'restrict_daily' => $product['restrict_daily'],
            'type' => $product['type'],  //producthelper传过来是type =》 type2
            'num' => $product['num'],
            'is_special' => $product['is_special'],
            'sale_unit' => Products::getSaleUnit($product),
            'minimum_order' => $product['minimum_order'],
        ];
        $this->setNum($product);
        $this->setRestrictInfo($product);
        $this->setLifeTime($product);
        $this->setSelected($product);
        $this->setSubTotal();
        $this->setGrandTotal();
        $this->setRebatesMoney();
        return $this;
    }

    public function isSpecialPriceProduct()
    {
        return $this->product['is_special'];
    }

    public function toArray()
    {
        $product = $this->product;
        $product['num'] = abs($product['num']);
        return $product;
    }

    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Author Jason Y.Wang
     * @param $product
     * @return int
     * 检测商品是否可以选中
     */
    private function isSelected($product)
    {
        //不为上架状态置为不选中状态
//        if ($product['status'] != Products::STATUS_ENABLED) {
//            return 0;
//        }
        //不为审核通过状态置为不选中状态
//        if ($product['state'] != Products::STATE_APPROVED) {
//            return 0;
//        }
        //本身就为不选中状态  redis中num大于0则为选中，小于0则为不选中状态
        if ($product['num'] <= 0) {
            return 0;
        }

        return 1;
    }

    private function setRestrictInfo($product)
    {
        //商品限购
        if (isset($product['restrict_daily']) && $product['restrict_daily'] > 0) {
            $this->product['restrict_info'] = '特价限购' . $product['restrict_daily'] . '件，超出部分按原价购买';
        }
    }

    private function setNum($product)
    {
        $this->product['num'] = $product['num'];
        if ($product['num'] > 0) {
            $this->_setNum($product, true);
        } else {
            $this->_setNum($product, false);
        }
    }

    private function _setNum($product, $selected)
    {
        //商品数量大于库存时，修改商品数量
        if (abs($product['num']) > $product['qty'] && $product['qty'] > 0) {
            $this->product['num'] = $product['qty'];
        }
        //商品数量小于起订数量时，修改商品数量
        if (abs($product['num']) < $product['minimum_order'] && $product['minimum_order'] > 0) {
            $this->product['num'] = $product['minimum_order'];
        }

        if (!$selected) {
            $this->product['num'] = -$this->product['num'];
        }
    }

    private function setSelected($product)
    {
        Tools::log($product, 'Item.log');
        if (!isset($product['num'])) {
            $this->product['selected'] = 0;
        }

        if ($product['num'] <= 0) {
            $this->product['selected'] = 0;
        } else {
            $this->product['selected'] = 1;
        }

        if (isset($product['selected'])) {
            Tools::log('selected', 'Item.log');
            Tools::log($product['selected'], 'Item.log');
            $this->product['selected'] = $product['selected'];
        }

        if ($product['state'] != Products::STATE_APPROVED) {
            $this->product['selected'] = 0;
        }

        if ($product['status'] != Products::STATUS_ENABLED) {
            $this->product['selected'] = 0;
        }

        if ($product['qty'] <= 0) {
            $this->product['selected'] = 0;
        }
    }

    private function setLifeTime($product)
    {
        //秒杀商品才有这个属性
        if (isset($product['left_time']) && $product['left_time'] > 0) {
            $this->product['seckill_lefttime'] = $product['left_time'];
        }
    }

    private function setGrandTotal()
    {
        //无限购
        if ($this->product['restrict_daily'] == 0) {
//            Tools::log('无限购', 'setGrandTotal.log');
            $this->grandTotal = $this->product['num'] * $this->product['price'];
            return;
        }

        //已经购买的商品数量
        $already_buy_num = ProductDecorator::getAlreadyBuyNum($this->customer, $this->product['product_id']) ?: 0;
        if ($already_buy_num >= $this->product['restrict_daily']) {
            //有限购，已购数量大于等于限购数量，原价购买
//            Tools::log('有限购，已购数量大于等于限购数量，原价购买', 'setGrandTotal.log');
            $this->grandTotal = $this->product['num'] * $this->product['original_price'];
            return;
        }

        //已经购买的数量小于限购数量
        $can_also_buy_num = $this->product['restrict_daily'] - $already_buy_num;

//        Tools::log('already_buy_num:' . $already_buy_num, 'setGrandTotal.log');
//        Tools::log('restrict_daily:' . $this->product['restrict_daily'], 'setGrandTotal.log');
//        Tools::log('can_also_buy_num:' . $can_also_buy_num, 'setGrandTotal.log');
        if ($can_also_buy_num >= $this->product['num']) {
            //还可以购买数量小于购买数量，所有商品按特价购买
//            Tools::log($this->product['product_id'] . '=>' . $this->product['price'] . '=>' . $this->product['num'], 'setGrandTotal.log');
            $this->grandTotal = $this->product['num'] * $this->product['price'];
            return;
        } else {
            //部分按原价购买，部分按特价购买
            $origin_price_num = $this->product['num'] - $can_also_buy_num; //原价购买部分
            $this->grandTotal = $can_also_buy_num * $this->product['price'] + $origin_price_num * $this->product['original_price'];
            return;
        }
    }

    private function setSubTotal()
    {
        $this->subTotal = $this->product['num'] * $this->product['original_price'];
    }

    public function getSubTotal()
    {
        return $this->subTotal;
    }

    public function getGrandTotal()
    {
        return $this->grandTotal;
    }

    private function setRebatesMoney()
    {
        if (isset($this->product['rebates_all']) && $this->product['rebates_all'] > 0) {
            $this->rebatesMoney = $this->product['price'] * $this->product['num'] * ($this->product['rebates_all'] / 100);
        }
    }

    public function getRebatesMoney()
    {
        return $this->rebatesMoney;
    }
}
