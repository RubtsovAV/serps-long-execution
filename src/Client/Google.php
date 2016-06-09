<?php

namespace RubtsovAV\Serps\Client;

use RubtsovAV\Serps\Core\Client\Client;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Guise\Guise;
use RubtsovAV\Serps\Core\ItemPosition;
use RubtsovAV\Serps\Core\Exception\RegionException;
use RubtsovAV\Serps\Core\Exception\BadProxyException;
use RubtsovAV\Serps\Core\Exception\BannedProxyException;

use Serps\SearchEngine\Google\GoogleClient;
use Serps\SearchEngine\Google\NaturalResultType;
use Serps\SearchEngine\Google\GoogleUrl;
use Serps\SearchEngine\Google\Page\GoogleSerp;
use Serps\SearchEngine\Google\Page\GoogleCaptcha;
use Serps\SearchEngine\Google\Exception\GoogleCaptchaException;
use Serps\SearchEngine\Google\Exception\InvalidDOMException;

use Serps\HttpClient\CurlClient;
use Serps\HttpClient\CurlClient\CurlException;

use Serps\Core\Http\SearchEngineResponse;
use Serps\Core\Http\Proxy;
use Serps\Core\Cookie\Cookie;
use Serps\Exception as SerpsException;
use Serps\Exception\RequestError\InvalidResponseException;

use Zend\Diactoros\Uri;

class Google extends Client
{
    protected $httpClient;
    protected $googleClient;
    protected $googleUrl;
    protected $googlePageNumber;

    protected function init()
    {
        $this->httpClient = new CurlClient();
        $this->googleClient = new GoogleClient($this->httpClient);

        if (isset($this->config['httpClientOptions'])) {
            foreach ($this->config['httpClientOptions'] as $option => $value) {
                $this->httpClient->getCurl()->setOption($option, $value);
            }
        }
    }

    public function prepareQuery(Query $query)
    {
        parent::prepareQuery($query);
        $this->googleUrl = $this->createGoogleUrl($query);
        $this->googlePageNumber = 1;
    }

    public function executeQuery()
    {
        $this->logger->debug('Client\Google->executeQuery');

        $this->setGeoLocationCookie();
        $queryResult = $this->queryResult;
        do {
            try {
                $this->logger->debug('try Client\Google->sendQuery()');
                $serp = $this->sendQuery();
            } catch (CurlException $ex) {
                $this->logger->debug(
                    'Client\Google->sendQuery() throw CurlException #' . $ex->getCurlErrCode()
                );
                throw new BadProxyException('', null, $ex);
            } catch (SerpsException $ex) {
                $this->logger->debug('Client\Google->sendQuery() throw SerpsException');
                $this->logger->debug("$ex");
                throw new BadProxyException('', null, $ex);
            }
            $items = $this->getItemsFromSerp($serp);
            foreach ($items as $item) {
                $queryResult->addItem($item);
                if ($queryResult->isComplete()) {
                    break;
                }
            }
            $this->setNextGooglePage();
            if (empty($items)) {
                $this->logger->info('items list is empty');
                $queryResult->complete();
            }
        } while (!$queryResult->isComplete());

        $this->logger->info('query executed');
        return $queryResult;
    }

    protected function createGoogleUrl(Query $query)
    {
        $searchTerm = $query->getSearchTerm();
        $region = $query->getSearchRegion();

        if (!empty($region['domainZone'])) {
            $domainZone = $region['domainZone'];
        } elseif (!empty($this->config['domainZone'])) {
            $domainZone = $this->config['domainZone'];
        } else {
            $domainZone = 'com';
        }

        if (isset($region['countryCode'])) {
            $countryCode = $region['countryCode'];
        } elseif (isset($this->config['countryCode'])) {
            $countryCode = $this->config['countryCode'];
        } 

        if (isset($this->config['googleHost'])) {
            $googleHost = $this->config['googleHost'];
        } else {
            $googleHost = 'google.%domainZone%';
        }

        $googleHost = str_replace('%domainZone%', $domainZone, $googleHost);

        $url = new GoogleUrl();
        $url->setHost($googleHost);
        if ($this->config['httpOnly']) {
            $url->setScheme('http');
            $url->setParam('gws_rd', 'ssl');
        }
        if (isset($countryCode)) {
            $url->setParam('cr', 'country' . $countryCode);
        }
        $url->setResultsPerPage(100);
        $url->setSearchTerm($searchTerm);
        return $url;
    }

    protected function sendQuery()
    {
        $this->logger->debug('Client\Google->sendQuery');

        $proxy = $this->getProxy();
        $cookieStorage = $this->getCookieStorage();
        $httpHeaders = $this->getHttpHeaders();

        if ($proxy) {
            $proxyString = $proxy->getIP() . ':' . $proxy->getPort();
            $this->logger->info("use proxy $proxyString");
        }

        if (isset($httpHeaders['User-Agent'])) {
            $this->logger->info("set User-Agent: {$httpHeaders['User-Agent']}");
            $this->googleClient->request->setUserAgent($httpHeaders['User-Agent']);
        }

        if (isset($httpHeaders['Accept-Language'])) {
            $this->logger->info("set Accept-Language: {$httpHeaders['Accept-Language']}");
            $this->googleClient->request->setDefaultAcceptLanguage(
                $httpHeaders['Accept-Language']
            );
        }

        while (true) {
            try {
                $this->logger->debug('try googleClient->query()');
                $this->logger->info('send query');
                $serp = $this->googleClient->query(
                    $this->googleUrl,
                    $proxy,
                    $cookieStorage
                );

                if ($this->config['dumpSerp']) {
                    $this->createDumpSerp($serp);
                }
                break;
            } catch (GoogleCaptchaException $ex) {
                $this->logger->debug('googleClient->query() throw GoogleCaptchaException');
                $this->logger->info('CAPTCHA received');

                if (!$this->canSolveCaptcha()) {
                    throw $ex;
                }

                $this->solveCaptcha($ex->getCaptcha());
                $this->logger->info('CAPTCHA is solved');
            } catch (InvalidResponseException $ex) {
                $this->logger->debug('googleClient->query() throw InvalidResponseException');

                $response = $ex->getResponse();
                if ($this->config['dumpInvalidResponse']) {
                    $this->createDumpInvalidResponse($response);
                }
                if ($this->isBannedResponse($response)) {
                    $pageContent = $response->getPageContent();
                    $this->logger->info('proxy was banned in the Google');
                    throw new BannedProxyException('', null, $ex);
                } else {
                    $statusCode = $response->getHttpResponseStatus();
                    $this->logger->notice("received undefined response with the status code $statusCode");
                    throw new BadProxyException('', null, $ex);
                }
            }
        }
        $this->logger->info('response received');
        return $serp;
    }

    protected function isBannedResponse(SearchEngineResponse $response)
    {
        $pageContent = $response->getPageContent() . '';
        return strpos($pageContent, '<title>Sorry...</title>') !== false;
    }

    protected function getProxy()
    {
        $guiseProxy = $this->guise->getProxy();
        if (!$guiseProxy) {
            return null;
        }
        return new Proxy($guiseProxy->ip, $guiseProxy->port);
    }

    protected function getCookieStorage()
    {
        return $this->guise->getCookieStorage();
    }

    protected function getHttpHeaders()
    {
        return $this->guise->getHttpHeaders();
    }

    protected function setGeoLocationCookie()
    {
        $this->logger->debug('Client\Google->setGeoLocationCookie');

        $region = $this->query->getSearchRegion();
        $coordinates = $region['coordinates'];
        if (!$coordinates) {
            $this->logger->info('coordinates is not set for the region');
            return;
        }

        $latitude = $coordinates['latitude'];
        $longitude = $coordinates['longitude'];

        $latitude_e7 = round($latitude * pow(10, 7));
        $longitude_e7 = round($longitude * pow(10, 7));

        if (!$latitude_e7) {
            $this->logger->error("wrong latitude $latitude");
            throw new RegionException("wrong latitude $latitude");
        }

        if (!$longitude_e7) {
            $this->logger->error("wrong longitude $longitude");
            throw new RegionException("wrong longitude $longitude");
        }

        $googleHost = $this->googleUrl->getHost();

        $value = "role:1\n";
        $value .= "producer:12\n";
        $value .= "provenance:6\n";
        $value .= 'timestamp:'. (time() * 1000). "\n";
        $value .= "latlng{\n";
        $value .= "latitude_e7:{$latitude_e7}\n";
        $value .= "longitude_e7:{$longitude_e7}\n";
        $value .= "}\n";
        $value .= 'radius:7150000';
        $value = 'a+' . base64_encode($value);

        $cookie = new Cookie('UULE', $value, ['domain' => ".$googleHost"]);
        $cookieStorage = $this->guise->getCookieStorage();
        $cookieStorage->set($cookie);
        $this->logger->info("set geolocation coordinates in latitude $latitude and longitude $longitude");
    }

    protected function getItemsFromSerp(GoogleSerp $serp)
    {
        try {
            $serpItems = $serp->getNaturalResults();
        } catch (InvalidDOMException $ex) {
            if ($this->config['dumpSerpDomError']) {
                $this->createDumpSerpDomError($serp, $ex);
            }
            throw new BadProxyException('invalid dom of the serp', null, $ex);
        }

        $items = [];
        foreach ($serpItems as $result) {
            if ($result->is(NaturalResultType::CLASSICAL)) {
                $items[] = new ItemPosition([
                    'position' => $result->getRealPosition() + 1,
                    'title' => $result->title,
                    'url' => $result->url,
                ]);
            } elseif ($result->is(NaturalResultType::IN_THE_NEWS)) {
                foreach ($result->news as $newsResult) {
                    $items[] = new ItemPosition([
                        'position' => $result->getRealPosition() + 1,
                        'title' => $newsResult->title,
                        'url' => $newsResult->url,
                    ]);
                }
            }
        }
        return $items;
    }

    protected function setNextGooglePage()
    {
        $this->logger->debug('Client\Google->setNextGooglePage');

        $this->googleUrl->setPage(++$this->googlePageNumber);
        $this->logger->info("page number changed to {$this->googlePageNumber}");
    }

    protected function solveCaptcha(GoogleCaptcha $captcha)
    {
        $this->logger->debug('Client\Google->solveCaptcha');

        if ($this->config['dumpCaptchaPage']) {
            $this->createDumpCaptchaPage($captcha);
        }

        $imageUrl = $captcha->getImageUrl();
        $this->logger->info("download captcha image from $imageUrl");
        $imageData = $this->downloadImageData($imageUrl);

        if ($this->config['dumpCaptchaImage']) {
            $this->createDumpCaptchaImage($imageData);
        }

        $this->logger->info('solve captcha');
        $captchaSolver = $this->config['captchaSolver'];
        $captchaAnswer = $captchaSolver($imageData);

        $this->logger->info("confirm captcha by answer $captchaAnswer");
        $response = $this->confirmCaptcha($captcha, $captchaAnswer);
        if ($this->config['dumpCaptchaConfirmResponse']) {
            $this->createDumpCaptchaConfirmResponse($response);
        }
    }

    protected function downloadImageData($imageUrl)
    {
        $this->logger->debug('Client\Google->downloadImageData');

        $request = $this->googleClient->request->buildRequest($this->googleUrl);
        $imageUri = new Uri($imageUrl);
        
        // fix port
        $imageUri = $imageUri->withPort($request->getUri()->getPort());
        $request = $request->withUri($imageUri);

        $proxy = $this->getProxy();
        $cookieStorage = $this->getCookieStorage();

        $response = $this->httpClient->sendRequest(
            $request,
            $proxy,
            $cookieStorage
        );

        $imageData = $response->getPageContent();
        if (!$imageData || !@getimagesizefromstring($imageData)) {
            throw new BadProxyException();
        }
        $this->logger->debug('imageData downloaded');
        return $imageData;
    }

    protected function confirmCaptcha(GoogleCaptcha $captcha, $captchaAnswer)
    {
        $this->logger->debug('Client\Google->confirmCaptcha');

        $params = $this->getCaptchaConfirmParams($captcha, $captchaAnswer);

        $confirmUri = new Uri($params['url']);
        $request = $this->googleClient->request->buildRequest($this->googleUrl);

        // fix port
        $confirmUri = $confirmUri->withPort($request->getUri()->getPort());
        $request = $request->withUri($confirmUri);

        if (strcasecmp($params['method'], 'get') === 0) {
            $uri = $request->getUri();
            $query = $uri->getQuery();
            if (!empty($query)) {
                $query .= '&';
            }
            $query .= http_build_query($params['fields']);
            $uri = $uri->withQuery($query);
            $request = $request->withUri($uri);
        }

        $this->logger->info('confirm request uri is ' . $request->getUri());

        $proxy = $this->getProxy();
        $cookieStorage = $this->getCookieStorage();

        $response = $this->httpClient->sendRequest(
            $request,
            $proxy,
            $cookieStorage
        );
        return $response;
    }

    protected function getCaptchaConfirmParams(GoogleCaptcha $captcha, $captchaAnswer)
    {
        $xpath = $captcha->getErrorPage()->getXpath();

        $form = $xpath->query('//body/div/form')->item(0);
        $action = $form->getAttribute('action');
        $method = $form->getAttribute('method');

        if (empty($method)) {
            $method = 'get';
        }

        $fields = [];
        $inputs = $xpath->query('//input[@name]');
        foreach ($inputs as $input) {
            $name = $input->getAttribute('name');
            if (empty($name)) {
                continue;
            }
            $value = $input->getAttribute('value');
            $fields[$name] = $value;
        }
        $fields['captcha'] = $captchaAnswer;

        $url = $captcha->getErrorPage()->getUrl();
        if (!empty($action)) {
            if ($action{0} == '/') {
                $url = $url->resolve($action);
            } else {
                $path = $url->getPath();
                $path = substr($path, 0, strrpos($path, '/'));
                $path .= '/' . $action;
                $url = $url->resolve($path);
            }
        }

        return [
            'url' => $url->buildUrl(),
            'method' => $method,
            'fields' => $fields,
        ];
    }

    protected function createDumpSerp(GoogleSerp $serp)
    {
        $this->logger->debug('Client\Google->createDumpSerp');

        $dumpString .= 'URL: ' . $serp->getUrl() . "\n";
        $dumpString .= "Page content:\n". $serp->getDom()->C14N();

        $this->logger->info('create dump of the SERP');
        $this->createDump('Serp', $dumpString);
    }

    protected function createDumpSerpDomError(GoogleSerp $serp, SerpsException $ex)
    {
        $this->logger->debug('Client\Google->createDumpSerpDomError');

        $dumpString .= 'Error: ' . $ex . "\n";
        $dumpString .= 'URL: ' . $serp->getUrl() . "\n";
        $dumpString .= "Page content:\n". $serp->getDom()->C14N();

        $this->logger->info('create dump of the SERP');
        $this->createDump('SerpDomError', $dumpString);
    }

    protected function createDumpInvalidResponse(SearchEngineResponse $response)
    {
        $this->logger->debug('Client\Google->createDumpInvalidResponse');

        $dumpString .= 'Initial URL: ' . $response->getInitialUrl() . "\n";
        $dumpString .= 'Effective URL: ' . $response->getEffectiveUrl() . "\n";
        $dumpString .= 'HTTP Status: ' . $response->getHttpResponseStatus() . "\n";
        $dumpString .= 'HTTP Headers: '. var_export($response->getHeaders(), true) . "\n";
        $dumpString .= "Page content:\n". $response->getPageContent();

        $this->logger->notice('create dump of the invalid response');
        $this->createDump('InvalidResponse', $dumpString);
    }

    protected function createDumpCaptchaPage(GoogleCaptcha $captcha)
    {
        $this->logger->debug('Client\Google->createDumpCaptchaPage');

        $dumpString .= 'Url: ' . $captcha->getErrorPage()->getUrl() . "\n";
        $dumpString .= "Page content:\n" . $captcha->getErrorPage()->getDom()->C14N();

        $this->logger->info('create dump of the captcha page');
        $this->createDump('CaptchaPage', $dumpString);
    }

    protected function createDumpCaptchaImage($imageData)
    {
        $this->logger->debug('Client\Google->createDumpCaptchaImage');

        $this->logger->info('create dump of the captcha image');
        $this->createDump('CaptchaImage', $imageData);
    }

    protected function createDumpCaptchaConfirmResponse(SearchEngineResponse $response)
    {
        $this->logger->debug('Client\Google->createDumpCaptchaConfirmResponse');

        $dumpString .= 'Initial URL: ' . $response->getInitialUrl() . "\n";
        $dumpString .= 'Effective URL: ' . $response->getEffectiveUrl() . "\n";
        $dumpString .= 'HTTP Status: ' . $response->getHttpResponseStatus() . "\n";
        $dumpString .= 'HTTP Headers: '. var_export($response->getHeaders(), true) . "\n";
        $dumpString .= "Page content:\n". $response->getPageContent();

        $this->logger->info('create dump of the captcha confirm response');
        $this->createDump('CaptchaConfirmResponse', $dumpString);
    }
}
