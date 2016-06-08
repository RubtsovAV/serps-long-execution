<?php

namespace RubtsovAV\Serps\Core\Query\Condition;

abstract class Conditions extends Condition
{
    protected $conditions = [];

    /**
     * @param array $conditions Array with conditions
     */
    public function __construct(array $conditions = [])
    {
    	$this->conditions = $conditions;
    }

    public function addCondition(Condition $condition)
    {
        $this->conditions[] = $condition;
    }

    public function getConditions()
    {
        return $this->conditions;
    }
}
