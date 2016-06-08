<?php

namespace RubtsovAV\Serps\Core;

trait ConfigTrait
{
    protected $config;
    protected $logger;

    public function __construct(array $config = [])
    {
        $this->initConfig($config);
    }

    public function initConfig(array $config = [])
    {
        $this->config = $config;
        $this->logger = Logger::getInstance($config['logger']);
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}
