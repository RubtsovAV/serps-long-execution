<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

use RubtsovAV\Serps\Core\ItemPosition;

class AllConditions extends Conditions
{
    public function match(ItemPosition $item)
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->match($item)) {
                return false;
            }
        }
        return true;
    }
}
