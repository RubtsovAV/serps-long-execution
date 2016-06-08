<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

use RubtsovAV\Serps\Core\ItemPosition;

class AnyConditions extends Conditions
{
    public function match(ItemPosition $item)
    {
        if (empty($this->conditions)) {
            return true;
        }
        
        foreach ($this->conditions as $condition) {
            if ($condition->match($item)) {
                return true;
            }
        }
        return false;
    }
}
