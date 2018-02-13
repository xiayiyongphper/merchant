<?php

namespace service\components\sales;

use framework\db\readonly\models\core\Rule;
use service\components\Tools;
use service\message\customer\CustomerResponse;
use service\models\VarienObject;

class Quote extends VarienObject
{

    /** @var  Rule $_rule */
    public $_rule;
    public $_rule_title;
    public $_rule_str;
    public $_promotion_str;
    //还差多少到下一级优惠
    public $_gap_to_next = -1;
    public $_max_value = -1;
    //什么类型的优惠，如满量，满额
    public $attribute;

    public $grandTotal = 0;
    public $subTotal = 0;
    public $discountAmount = 0;
    public $qty = 0;
    public $rebates = 0; //返现

    public $grandTotalNotIncludeSpecialPriceProduct = 0;
    public $qtyNotIncludeSpecialPriceProduct = 0;

    public $grandTotalToValidate = 0;
    public $qtyToValidate = 0;

    public $rule_type; //0.无活动商品 1.单品级,2.多品级,3.订单级,4.秒杀商品

    public $subsidies_lelai_included; //特价商品是否参与该活动

    public $activity_url = null;

    /** @var  CustomerResponse $customer */
    protected $customer;

    //是否与订单级互斥,0为不互斥，1为互斥
    public $_stop_rules_processing = 0;

    /**
     * Quote constructor.
     * @param $customer
     * @param Rule|null $rule
     */
    public function __construct($customer, $rule = null)
    {
        parent::_construct();
        $this->_rule = $rule;
        if (!empty($rule)) {
            $this->rule_type = $rule->type;
            $this->subsidies_lelai_included = $rule->subsidies_lelai_included;
            $this->activity_url = 'lelaishop://topicV3/list?rid=' . $rule->rule_id;//活动跳转链接
        }
        $this->customer = $customer;
    }

    /**
     * Author Jason Y.Wang
     * @param $customer
     * @param $rule
     * @return static
     */
    public static function init($customer, $rule = [])
    {
        return new static($customer, $rule);
    }


    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Author Jason Y.Wang
     * @param $ruleValidResult //当前优惠在哪个级别
     * @param $discountAmount //当前优惠是什么
     * @param $nextDiscountAmount //下一级优惠是什么
     */
    public function setRuleStr($ruleValidResult, $discountAmount, $nextDiscountAmount)
    {
        switch ($this->attribute) {
            case 'subtotal':  //差多少元
//                Tools::log('subtotal', 'setGrandTotal.log');
                $this->_rule_str = $this->getPromotionStr('元', $ruleValidResult, $discountAmount, $nextDiscountAmount);
                break;
            case 'total_qty': //差多少件
                $this->_rule_str = $this->getPromotionStr('件', $ruleValidResult, $discountAmount, $nextDiscountAmount);
                break;
            default:
                break;
        }

    }

    private function getPromotionStr($unit, $ruleValidResult, $discountAmount, $nextDiscountAmount)
    {
//        Tools::log('getPromotionStr', 'setGrandTotal.log');
//        Tools::log($ruleValidResult, 'setGrandTotal.log');
//        Tools::log($discountAmount, 'setGrandTotal.log');
//        Tools::log($nextDiscountAmount, 'setGrandTotal.log');
//        Tools::log($this->_gap_to_next, 'setGrandTotal.log');
//        Tools::log('getPromotionStr', 'setGrandTotal.log');
        $tips = '';
        switch ($this->_rule->simple_action) {
            case Rule::BY_FIXED_ACTION:
                $this->_rule_title = '满减';
                if ($ruleValidResult === false) { //一个级别的优惠都不满足
                    $tips = "再买<font color=\"red\">{$this->_gap_to_next}</font>{$unit}，可减{$nextDiscountAmount}元";
                } else { //满足某个级别的优惠
                    if ($this->_gap_to_next == -1) {
                        //已经最高
                        $tips = "已购满{$this->_max_value}{$unit}，已减{$discountAmount}元";
                    } else {
                        $tips = "可减{$discountAmount}元，还差{$this->_gap_to_next}{$unit}，可减{$nextDiscountAmount}元";
                    }
                }

                break;
            case Rule::BY_PERCENT_ACTION:
                $this->_rule_title = '满折';
                $nextDiscountAmount = $nextDiscountAmount / 10;
                $discountAmount = $discountAmount / 10;
                if ($ruleValidResult === false) { //一个级别的优惠都不满足
                    $tips = "再买<font color=\"red\">{$this->_gap_to_next}</font>{$unit}，可打{$nextDiscountAmount}折";
                } else { //满足某个级别的优惠
                    if ($this->_gap_to_next == -1) {
                        //已经最高
                        $tips = "已购满{$this->_max_value}{$unit}，已打{$discountAmount}折";
                    } else {
                        $tips = "可打{$discountAmount}折，还差{$this->_gap_to_next}{$unit}，可打{$nextDiscountAmount}折";
                    }
                }
                break;
            case Rule::BUY_X_GET_Y_FREE_ACTION:
                $this->_rule_title = '满赠';
                if ($ruleValidResult === false) { //一个级别的优惠都不满足
                    $tips = "再买<font color=\"red\">{$this->_gap_to_next}</font>{$unit}，可赠{$nextDiscountAmount}";
                } else { //满足某个级别的优惠
                    if ($this->_gap_to_next == -1) {
                        //已经最高
                        $tips = "已购满{$this->_max_value}{$unit}，已赠{$discountAmount}";
                    } else {
                        $tips = "可赠{$discountAmount}，还差{$this->_gap_to_next}{$unit}，可赠{$nextDiscountAmount}";
                    }
                }
                break;
            default:
                break;
        }

        return $tips;
    }

}
