<?php

namespace RubtsovAV\Serps\Core\Query;

use RubtsovAV\Serps\Core\ConfigTrait;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\ItemPosition;

class Result
{
    use ConfigTrait;

    protected $query;
    protected $items = [];
    protected $isComplete = false;

    public function setQuery(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function addItem(ItemPosition $item)
    {
        $this->logger->debug('QueryResult->addItem');

        if ($this->isComplete()) {
            $this->logger->notice('The result is already completed');
            return;
        }

        $maxPosition = $this->query->getMaxPosition();
        $maxNumberItems = $this->query->getMaxNumberItems();

        if (!$this->query->getConditionItems()
            || $this->query->getConditionItems()->match($item)
        ) {
            $this->logger->debug('The item is match to the conditions');
            $this->items[] = $item;
        }

        if ($maxPosition && $item->position >= $maxPosition) {
            $this->logger->debug("ItemPosition->position >= maxPosition; {$item->position} >= $maxPosition");
            $this->complete();
            return;
        }

        if ($maxNumberItems && $this->countItems() >= $maxNumberItems) {
            $this->logger->debug('number of items >= maxNumberItems; '. $this->countItems() . " >= $maxNumberItems");
            $this->complete();
            return;
        }
    }

    public function getItems()
    {
        return $this->items;
    }

    public function countItems()
    {
        return count($this->items);
    }

    public function isComplete()
    {
        return $this->isComplete;
    }

    public function complete()
    {
        $this->logger->debug('QueryResult->complete');
        $this->isComplete = true;
    }
}
