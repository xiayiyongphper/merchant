<?php
namespace service\components\sales\quote;
use service\components\sales\Validator;


/**
 * Author Jason Y.Wang
 * Class Discount
 * @package framework\components\sales\sales\quote
 * 优惠计算模块
 */
class Discount extends TotalAbstract
{

    /** @var Validator $_calculator */
    protected $_calculator;

    public function __construct()
    {
        $this->setCode('discount');
        $this->_calculator = new Validator();
    }

    public function collect($quote)
    {
        $this->_calculator->setQuote($quote);
        $this->_calculator->init()->initTotals();
        return $this;
    }

    public function init()
    {
        return $this;
    }

}
