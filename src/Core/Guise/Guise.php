<?php

namespace RubtsovAV\Serps\Core\Guise;

use RubtsovAV\Serps\Core\Proxy;
use Serps\Core\Cookie\CookieJarInterface;

class Guise
{
    protected $proxy;
    protected $cookieStorage;
    protected $httpHeaders;

    public function __construct(
        CookieJarInterface $cookieStorage,
        Proxy $proxy = null,
        array $httpHeaders = []
    ) {
        $this->proxy = $proxy;
        $this->cookieStorage = $cookieStorage;
        $this->httpHeaders = $httpHeaders;
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    public function getCookieStorage()
    {
        return $this->cookieStorage;
    }

    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    public function praise()
    {
        if ($this->proxy) {
            $this->proxy->praise();
        }
    }

    public function scold()
    {
        if ($this->proxy) {
            $this->proxy->scold();
        }
    }

    public function banned()
    {
        if ($this->proxy) {
            $this->proxy->banned();
        }
    }
}
