<?php

namespace RubtsovAV\Serps\Core\Guise;

class Queue extends \SplQueue
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}
