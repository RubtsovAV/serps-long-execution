<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

use RubtsovAV\Serps\Core\ItemPosition;

abstract class Condition
{
    /**
     * Check the item for condition matching.
     *
     * @param ItemPosition $item
     *
     * @return bool
     */
    abstract public function match(ItemPosition $item);
}
