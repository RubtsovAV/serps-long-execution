<?php

namespace RubtsovAV\Serps\Core;

class ItemPosition
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($fieldName)
    {
        return $this->data[$fieldName];
    }

    public function __set($fieldName, $fieldValue)
    {
        throw new Exception('all fields for read only');
    }

    public function toArray()
    {
        return $this->data;
    }
}
