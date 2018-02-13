<?php
namespace service\components\sales;

use framework\db\readonly\models\core\Rule;
use service\models\VarienObject;

class Validator extends VarienObject
{

    /** @var RuleQuote $_quote */
    protected $_quote = null;

    /**
     * 规则检测，检测该规则是否可以应用，返回应该规则的
     *
     * @param   Rule $rule
     * @param   Quote $quote
     * @return  bool
     */
    protected function _canProcessRule($rule, $quote)
    {
        $validResult = $rule->validateConditions($quote);
        return $validResult;
    }

    public function setQuote($quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    public function init(){
        return $this;
    }

}
