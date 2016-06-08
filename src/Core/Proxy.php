<?php

namespace RubtsovAV\Serps\Core;

use RubtsovAV\Serps\Core\Exception\InvalidProxyGetterException;
use RubtsovAV\Serps\Core\Exception\NotAvailableProxyException;

class Proxy
{
    use ConfigTrait;

    protected $realProxy;

    public $ip;
    public $port;

    /**
     * @param array $config
     * @return Proxy
     */
    public static function getInstance(array $config = [])
    {
        $proxy = new self($config);

        if (isset($config['getter'])) {
            $getter = $config['getter'];
            if (!is_callable($getter)) {
                throw new InvalidProxyGetterException('getter must be a callable');
            }
            $realProxy = $getter();
            if (!$realProxy) {
                throw new NotAvailableProxyException();
            }
            $proxy->bindProxy($realProxy);
        }

        return $proxy;
    }

    /**
     * @param object $proxy
     *   Must have public properties ip and port
     */
    public function bindProxy($proxy)
    {
        $this->realProxy = $proxy;
        $this->ip = $proxy->ip;
        $this->port = $proxy->port;
    }

    /**
     * Call if the proxy has successfully executed the query
     */
    public function praise()
    {
        $this->logger->debug('Proxy->praise');
        $this->logger->info('proxy is good');

        $function = $this->config['onGoodProxy'];
        if (is_callable($function)) {
            $this->logger->debug('call onGoodProxy');
            $function($this->realProxy);
        }
    }

    /**
     * Call if the proxy has failed execute the query
     */
    public function scold()
    {
        $this->logger->debug('Proxy->scold');
        $this->logger->info('proxy is bad');

        $callback = $this->config['onBadProxy'];
        if (is_callable($callback)) {
            $this->logger->debug('call onBadProxy');
            $callback($this->realProxy);
        }
    }

    /**
     * Call if the proxy was banned in the search engine
     */
    public function banned()
    {
        $this->logger->debug('Proxy->banned');
        $this->logger->info('proxy was banned');

        $callback = $this->config['onBannedProxy'];
        if (is_callable($callback)) {
            $this->logger->debug('call onBannedProxy');
            $callback($this->realProxy);
        }
    }
}
