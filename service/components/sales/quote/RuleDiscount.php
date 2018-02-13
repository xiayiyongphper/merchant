<?php
namespace service\components\sales\quote;
use service\components\sales\RuleValidator;


/**
 * Author Jason Y.Wang
 * Class Discount
 * @package framework\components\sales\sales\quote
 * 优惠计算模块
 */
class RuleDiscount extends Discount
{

    protected $_calculator;

    public function __construct()
    {
        $this->_calculator = new RuleValidator();
    }

    public function collect($quote)
    {
        $this->_calculator->setQuote($quote);

        $this->_calculator->init()->initTotals();
        return $this;
    }

}
