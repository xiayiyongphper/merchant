<?php

namespace service\components\sales;

use framework\db\readonly\models\core\Rule;
use service\components\Tools;

class WholesalerValidator extends Validator
{

    protected $_stopFurtherRules = false;

    public function initTotals()
    {
        $rule = $this->_quote->_rule;
        //有优惠是才需要计算优惠
        if ($rule) {
//            Tools::log('商家有优惠：', 'setGrandTotal.log');
//            Tools::log($rule->toArray(), 'setGrandTotal.log');
            $this->processWholesalerQuote();
        }
        $this->_quote->discountAmount = $this->_quote->subTotal - $this->_quote->grandTotal;
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
        $this->initWholesalerQuote();
        return $this;
    }

    /**
     * Author Jason Y.Wang
     * 购物车计算
     */
    private function initWholesalerQuote()
    {
        /** @var WholesalerQuote $wholesalerQuote */
        $wholesalerQuote = $this->_quote;
        /** @var RuleQuote $quote */
        foreach ($wholesalerQuote->_quotes as $quote) {
            $wholesalerQuote->subTotal += $quote->subTotal;
            $wholesalerQuote->grandTotal += $quote->grandTotal;
            $wholesalerQuote->qty += $quote->qty;
            $wholesalerQuote->rebates += $quote->rebates;

            if ($quote->_stop_rules_processing) {
                //不计算订单级活动
                continue;
            }

            if ($quote->subsidies_lelai_included) { //包含特价
                $wholesalerQuote->grandTotalToValidate += $quote->grandTotal;
                $wholesalerQuote->qtyToValidate += $quote->qty;
            } else {
                $wholesalerQuote->grandTotalToValidate += $quote->grandTotalNotIncludeSpecialPriceProduct;
                $wholesalerQuote->qtyToValidate += $quote->qtyNotIncludeSpecialPriceProduct;
            }

        }
    }

    /**
     * @return $this
     */
    protected function processWholesalerQuote()
    {
        /** @var WholesalerQuote $wholesalerQuote */
        $wholesalerQuote = $this->_quote;
        /** @var Rule $rule */
        $rule = $wholesalerQuote->_rule;
        //满足的优惠级数  false:没有满足任何级别
        $ruleValidResult = $this->_canProcessRule($rule, $wholesalerQuote);
        //当前优惠金额
        $discountAmount = $rule->getCurrentDiscountAmount($ruleValidResult);
        //下一级的优惠金额
        $nextDiscountAmount = $rule->getNextDiscountAmount($ruleValidResult);
        switch ($rule->simple_action) {
            case Rule::BY_FIXED_ACTION:
                $wholesalerQuote->grandTotal = $wholesalerQuote->grandTotal - $discountAmount;
                break;
            case Rule::BY_PERCENT_ACTION:
                $discountPercent = $rule->getCurrentDiscountAmount($ruleValidResult);
                // 计算折扣百分比
                $rulePercent = min(100, $discountPercent);
                if ($rulePercent >= 100 || $rulePercent <= 0) {
                    return $this;
                }
                $wholesalerQuote->grandTotal = $wholesalerQuote->grandTotal * $discountPercent / 100;
                break;
            case Rule::BUY_X_GET_Y_FREE_ACTION:
                $discountAmount = $rule->getCurrentDiscountAmount($ruleValidResult);
                break;
        }

        $wholesalerQuote->setRuleStr($ruleValidResult, $discountAmount, $nextDiscountAmount);
        return $this;
    }

}
