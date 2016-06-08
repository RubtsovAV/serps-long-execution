<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

use RubtsovAV\Serps\Core\ItemPosition;

class DomainCondition extends Condition
{
    protected $wantedDomain;

    public function __construct($wantedDomain)
    {
        $this->wantedDomain = $wantedDomain;
    }

    public function match(ItemPosition $item)
    {
        $itemHost = parse_url($item->url, PHP_URL_HOST);
        return strcasecmp($this->wantedDomain, $itemHost) === 0;
    }
}
