<?php

namespace RubtsovAV\Serps\Core\Query;

use RubtsovAV\Serps\Core\Query\Condition\Condition;
use RubtsovAV\Serps\Core\Exception\InvalidArgumentException;

class Query
{
    protected $searchTerm;
    protected $searchRegion;
    protected $positionLimit;
    protected $maxNumberItems;
    protected $conditionItems;

    /**
     * @param string $searchTerm
     *   Search term which to send to search engine.
     *
     * @throws InvalidArgumentException
     *   If the $searchTerm is not of type 'string' or is empty.
     */
    public function __construct($searchTerm)
    {
        $this->setSearchTerm($searchTerm);
    }

    /**
     * @param string $searchTerm
     *   Search term which to send to search engine
     *
     * @throws InvalidArgumentException
     *   If the $searchTerm is not of type 'string' or is empty.
     */
    public function setSearchTerm($searchTerm)
    {
        if (!is_string($searchTerm)) {
            throw new InvalidArgumentException(
                'searchTerm must be a string, but passed the '. gettype($searchTerm)
            );
        }
        if (empty($searchTerm)) {
            throw new InvalidArgumentException("searchTerm can't be empty");
        }

        $this->searchTerm = $searchTerm;
    }

    /**
     * @return string
     */
    public function getSearchTerm()
    {
        return $this->searchTerm;
    }

    /**
     * @param array $searchRegion
     *   Information about the search region.
     */
    public function setSearchRegion(array $searchRegion)
    {
        $this->searchRegion = $searchRegion;
    }

    /**
     * @return array
     */
    public function getSearchRegion()
    {
        return $this->searchRegion;
    }

    /**
     * Limits the search area.
     * Executing the query will be interrupted when it reaches that position.
     *
     * @param int $positionLimit
     *
     * @throws InvalidArgumentException
     *   If the $positionLimit is not numeric or is less than one.
     */
    public function setPositionLimit($positionLimit)
    {
        if (!is_numeric($positionLimit)) {
            throw new InvalidArgumentException('positionLimit must be a numeric');
        }

        $positionLimit = (int) $positionLimit;

        if ($positionLimit < 1) {
            throw new InvalidArgumentException("positionLimit can't less than one");
        }

        $this->positionLimit = $positionLimit;
    }

    /**
     * @return null|int
     *   Returns null if the positionLimit is not set.
     */
    public function getPositionLimit()
    {
        return $this->positionLimit;
    }

    /**
     * Sets the maximum number of required items.
     * Executing the query will be interrupted when it is found necessary number of items.
     *
     * @param int $maxNumberItems
     *
     * @throws InvalidArgumentException
     *   If the $maxNumberItems is not numeric or is less than one.
     */
    public function setMaxNumberItems($maxNumberItems)
    {
        if (!is_numeric($maxNumberItems)) {
            throw new InvalidArgumentException('maxNumberItems must be a numeric');
        }

        $maxNumberItems = (int) $maxNumberItems;

        if ($maxNumberItems < 1) {
            throw new InvalidArgumentException("maxNumberItems can't less than one");
        }

        $this->maxNumberItems = $maxNumberItems;
    }

    /**
     * @return null|int
     *   Returns null if the maxNumberItems is not set.
     */
    public function getMaxNumberItems()
    {
        return $this->maxNumberItems;
    }

    /**
     * @param Condition $conditionItems
     */
    public function setConditionItems(Condition $conditionItems)
    {
        $this->conditionItems = $conditionItems;
    }

    /**
     * @return null|Condition
     */
    public function getConditionItems()
    {
        return $this->conditionItems;
    }
}
