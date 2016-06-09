The Google client
=====
Features:		
 - Set the location by geo coordinates		
 - Notice if proxy was banned		
 - Catch and confirm captcha

Quick Start
-----

```php

use RubtsovAV\Serps\Core\Facade as Serps;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Condition\DomainCondition;

$searchTerm = 'news';
$requiredDomain = 'news.google.com';

$query = new Query($searchTerm);

// We need only items with the required domain.
// It optional.
$query->setConditionItems(
    new DomainCondition($requiredDomain)
);

// We need only the first item (which match the condition).
// It optional.
$query->setMaxNumberItems(1); 

// Limit the search to 100 items.
// It optional.
$query->setPositionLimit(100);

// result for the 'Russia'
$query->setSearchRegion([

    // Set the Google domain zone for that query
    // You can see the full list here https://en.wikipedia.org/wiki/List_of_Google_domains
    'domainZone' => 'ru',
    
    // Set the country code for that query
    // You can find the code for your country here https://developers.google.com/public-data/docs/canonical/countries_csv
    'countryCode' => 'RU',

    // The Moscow city coordinates
    'coordinates' => [
        'latitude' => 55.755826,
        'longitude' => 37.3193288,
    ],
]);

$serpsConfig = [
    // Headers that will be used in all requests
    'httpHeaders' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
    ],
    'client' => [
        'Google' => [
        
            // Use only http protocol (not https)
            'httpOnly' => true,
        ]
    ],
];
$serps = new Serps($serpsConfig);
$client = $serps->getClientByName('Google');
$result = $serps->executeQueryBy($client, $query);

echo "Found: " . $result->countItems() . " items\n";
foreach ($result->getItems() as $item) {
    echo "#{$item->position}\n";
    echo "{$item->title}\n";
    echo "{$item->url}\n";
    echo "\n";
}
    
```

Proxy Manager
-------------

```php

// Your proxy class for example
class Proxy
{
    public $ip;
    public $port;
    
    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }
}

$proxyQueue = [
    new Proxy('123.123.123.123', '8080'),
    new Proxy('123.123.123.124', '8080'),
];

$serpsConfig = [
    'proxy' => [
    
        /**
         * callable  
         *   Required option for proxy manager.
         *   Сalled when the need a new proxy.
         *   Must return object with properties ip and port or null if the proxy is not available.
         */
        'getter' => function () use (&$proxyQueue) {
            return array_shift($proxyQueue);
        },
        
        /**
         * callable  
         *   Optional.
         *   It called when the proxy worked successfully.
         *   In first argument the proxy object which was returned the getter function.
         */
        'onGoodProxy' => function (Proxy $proxy) {
            // Here you can mark that proxy is a good
            // You do not need to add it to the proxy queue
        },
        
        /**
         * callable  
         *   Optional.
         *   It called when the proxy not worked.
         *   In first argument the proxy object which was returned the getter function.
         */
        'onBadProxy' => function (Proxy $proxy) {
            // Here you can mark that proxy is a bad
            // You can add it to the proxy queue again
        },
        
        /**
         * callable  
         *   Optional.
         *   It called when proxy was banned in the search engine.
         *   In first argument the proxy object which was returned the getter function.
         */
        'onBannedProxy' => function (Proxy $proxy) {
            // Here you can mark that proxy is a bad
            // You can add it to the proxy queue again
        },
    ]
];

$serps = new Serps($serpsConfig);
$client = $serps->getClientByName('Google');

// The result can be returned after a long time, if your proxy server is not good.
// Be sure that your the proxy queue is not empty, 
// because it will be waiting while the proxy getter returns the proxy object.
$result = $serps->longExecuteQueryBy($client, $query);

echo "Found: " . $result->countItems() . " items\n";
foreach ($result->getItems() as $item) {
    echo "#{$item->position}\n";
    echo "{$item->title}\n";
    echo "{$item->url}\n";
    echo "\n";
}
    
```

Captcha Solver
--------------

```php

$serpsConfig = [
    'client' => [
        'Google' => [
            
            /**
             * callable  
             *   Required option for captcha solver.
             *   Called when the search engine need solve the CAPTCHA.
             *   In first argument the image data string.
             *   Must return the answer string.
             */
            'captchaSolver' => function ($imageData) {
                // Here you must to solve the captcha image
                // ...
                return (string) $captchaAnswer;
            }
        ]
    ]
];

$serps = new Serps($serpsConfig);
$client = $serps->getClientByName('Google');

// The result can be returned after a long time, if your proxy server is not good.
// Be sure that your the proxy queue is not empty, 
// because it will be waiting while the proxy getter returns the proxy object.
$result = $serps->longExecuteQueryBy($client, $query);

echo "Found: " . $result->countItems() . " items\n";
foreach ($result->getItems() as $item) {
    echo "#{$item->position}\n";
    echo "{$item->title}\n";
    echo "{$item->url}\n";
    echo "\n";
}

```

PSR Logger
---------

I recommend using the [Monolog](https://github.com/Seldaek/monolog)

```php

$logger = new \Monolog\Logger('default');
$logger->pushHandler(
    new \Monolog\Handler\StreamHandler('php://output', \Monolog\Logger::INFO)
);

$serpsConfig = [
    'logger' => $logger
];

```
