<?php

namespace RubtsovAV\Serps\Core\Guise;

use RubtsovAV\Serps\Core\ConfigTrait;
use RubtsovAV\Serps\Core\Proxy;
use RubtsovAV\Serps\Core\Exception\NotAvailableProxyException;

use Serps\Core\Cookie\ArrayCookieJar;

class Factory
{
    use ConfigTrait;

    /**
     * Create new guise.
     *
     * @throws NotAvailableProxyException
     *   When the getter of proxy was returned null.
     *
     * @return Guise
     */
    public function createGuise()
    {
        $cookieStorage = new ArrayCookieJar();
        $proxy = null;
        $httpHeaders = [];

        $proxyConfig = $this->config['proxy'];
        if (!$proxyConfig['logger']) {
            $proxyConfig['logger'] = $this->logger;
        }
        try {
            $proxy = Proxy::getInstance($proxyConfig);
        } catch (NotAvailableProxyException $ex) {
            $this->logger->debug('Proxy::getInstance() throw NotAvailableProxyException');
            $this->logger->info('not available proxy');
            throw $ex;
        }
        
        if (is_array($this->config['httpHeaders'])) {
            $httpHeaders = $this->config['httpHeaders'];
        }
        return new Guise($cookieStorage, $proxy, $httpHeaders);
    }
}
