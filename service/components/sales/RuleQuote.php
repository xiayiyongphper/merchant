<?php

namespace service\components\sales;

use service\components\sales\quote\Item;
use service\components\sales\quote\RuleDiscount;
use service\components\Tools;

class RuleQuote extends Quote
{



    private $_items = [];

    /**
     * Author Jason Y.Wang
     * @param $item
     * @return $this
     */
    public function addProduct($item)
    {
        $this->_items[] = $item;
        return $this;
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function collectTotals()
    {
        $discount = new RuleDiscount();
        $discount->collect($this);
        return $this;
    }

    public function formatToArray()
    {
        $cartInfo = [
            'rule_title' => $this->_rule_title,
            'rule_str' => $this->_rule_str,
            'grand_total' => $this->grandTotal,
            'sub_total' => $this->subTotal,
            'rule_type' => $this->rule_type,
            'activity_url' => $this->activity_url
        ];

        /** @var Item $item */
        foreach ($this->_items as $item) {
            $item = $item->toArray();
            $cartInfo['product'][] = $item;
        }

        return $cartInfo;
    }



}
