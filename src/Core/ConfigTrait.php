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

        $logger = isset($config['logger']) ? $config['logger'] : null;
        $this->logger = Logger::getInstance($logger);

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
