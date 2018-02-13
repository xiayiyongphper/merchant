<?php

namespace service\components\sales;


use framework\db\readonly\models\core\Rule;
use service\components\sales\quote\Item;
use service\components\Tools;


class RuleValidator extends Validator
{

    public function initTotals()
    {
        $rule = $this->_quote->_rule;
        if (!empty($rule)) {
//            Tools::log('商品有优惠：', 'setGrandTotal.log');
            $this->processRuleQuote();
        }
        return $this;
    }

    /**
     * Author Jason Y.Wang
     * @return $this
     * 普通商品小购物车计算
     */
    public function init()
    {
        parent::init();
        $this->initRuleQuote();
        return $this;
    }

    /**
     * Author Jason Y.Wang
     * 小购物车计算
     */
    private function initRuleQuote()
    {
        $ruleQuote = $this->_quote;
        /** @var Item $item */
        foreach ($ruleQuote->getItems() as $item) {
            $product = $item->getProduct();
            if ($product['selected']) {
                $ruleQuote->subTotal = $ruleQuote->subTotal + $item->getSubTotal();
                //商品级别活动验证是否参加活动，包含所有商品
                $ruleQuote->grandTotalToValidate = $ruleQuote->grandTotal = $ruleQuote->grandTotal + $item->getGrandTotal();
                $ruleQuote->qtyToValidate = $ruleQuote->qty = $ruleQuote->qty + $product['num'];
                $ruleQuote->rebates += $item->getRebatesMoney();

                if ($item->isSpecialPriceProduct()) { //特价商品跳过计算
                    continue;
                }
                //不包含特价商品的总价格
                $ruleQuote->grandTotalNotIncludeSpecialPriceProduct += $item->getGrandTotal();
                //不包含特价商品的总数量
                $ruleQuote->qtyNotIncludeSpecialPriceProduct += $product['num'];

            }
        }
    }

    /**
     * @return $this
     */
    protected function processRuleQuote()
    {
        /** @var RuleQuote $ruleQuote */
        $ruleQuote = $this->_quote;
        /** @var Rule $rule */
        $rule = $ruleQuote->_rule;

        //满足的优惠级数  false:没有满足任何级别
        $ruleValidResult = $this->_canProcessRule($rule, $ruleQuote);

        if ($ruleValidResult) {
            //如果满足了优惠，则需要判断该规则是否可用于订单级活动
            $ruleQuote->_stop_rules_processing = $rule->stop_rules_processing;
        } else {
            //如果未满足了优惠，则不管怎样都可以参加订单级活动
            $ruleQuote->_stop_rules_processing = 0;
        }
        //当前优惠金额
        $discountAmount = $rule->getCurrentDiscountAmount($ruleValidResult);
        //下一级的优惠金额
        $nextDiscountAmount = $rule->getNextDiscountAmount($ruleValidResult);
//        Tools::log('grandTotal', 'setGrandTotal.log');
//        Tools::log($ruleQuote->grandTotal, 'setGrandTotal.log');

        switch ($rule->simple_action) {
            case Rule::BY_FIXED_ACTION:
                $ruleQuote->grandTotal = $ruleQuote->grandTotal - $discountAmount;
                break;
            case Rule::BY_PERCENT_ACTION:
                $discountPercent = $rule->getCurrentDiscountAmount($ruleValidResult);
                if ($discountPercent < 100 && $discountPercent > 0) {
                    $ruleQuote->grandTotal = $ruleQuote->grandTotal * $discountPercent / 100;
                }
                break;
            case Rule::BUY_X_GET_Y_FREE_ACTION:
                $discountAmount = $rule->getCurrentDiscountAmount($ruleValidResult);
                break;
        }

        $ruleQuote->setRuleStr($ruleValidResult, $discountAmount, $nextDiscountAmount);

        return $this;
    }


}
