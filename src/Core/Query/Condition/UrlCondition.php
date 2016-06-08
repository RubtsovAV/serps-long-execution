<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

use RubtsovAV\Serps\Core\ItemPosition;

class UrlCondition extends Condition
{
    protected $needleUrl;

    public function __construct($needleUrl)
    {
        $this->needleUrl = $needleUrl;
    }

    public function match(ItemPosition $item)
    {
        return strcasecmp($this->needleUrl, $item->url) === 0;
    }
}
