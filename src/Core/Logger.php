<?php

namespace RubtsovAV\Serps\Core;

use RubtsovAV\Serps\Core\Exception\WrongLoggerException;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class Logger extends NullLogger
{

    /**
     * @param null|callable|LoggerInterface $logger
     * @return LoggerInterface
     */
    public static function getInstance($logger = null)
    {
        if (is_null($logger)) {
            $instance = new self();
        } elseif (is_callable($logger)) {
            $instance = $logger();
        } elseif (is_object($logger)) {
            $instance = $logger;
        }

        if (!($instance instanceof LoggerInterface)) {
            throw new WrongLoggerException('logger instance must implement Psr\Log\LoggerInterface');
        }
        return $instance;
    }
}
