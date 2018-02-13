<?php
namespace service\components\sales\quote;

use service\components\sales\WholesalerValidator;

/**
 * Author Jason Y.Wang
 * Class Discount
 * @package framework\components\sales\sales\quote
 * 优惠计算模块
 */
class WholesalerDiscount extends Discount
{

    protected $_calculator;

    public function __construct()
    {
        $this->setCode('discount');
        $this->_calculator = new WholesalerValidator();
    }

    public function collect($quote)
    {
        $this->_calculator->setQuote($quote);
        $this->_calculator->init()->initTotals();
        return $this;
    }

}
