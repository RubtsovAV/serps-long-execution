<?php

namespace RubtsovAV\Serps\Test\Client;

use RubtsovAV\Serps\Client\Google;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Result;
use RubtsovAV\Serps\Core\Facade as SerpsFacade;
use RubtsovAV\Serps\Core\Exception\BadProxyException;
use RubtsovAV\Serps\Core\Exception\BannedProxyException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

/**
 * @covers RubtsovAV\Serps\Client\Google
 */
class GoogleTest extends \PHPUnit_Framework_TestCase
{
    use HttpMockTrait;

    const PATH_DUMP = 'build/dump';

    public static function setUpBeforeClass()
    {
        static::setUpHttpMockBeforeClass('8082', 'localhost');
    }

    public static function tearDownAfterClass()
    {
        static::tearDownHttpMockAfterClass();

        $httpMockStateDir = realpath('vendor/internations/http-mock/state');
        if ($httpMockStateDir) {
            $httpMockStateDir = escapeshellarg($httpMockStateDir);
            if ($httpMockStateDir) {
                exec("rm -f $httpMockStateDir/*");
            }
        }
    }

    public function setUp()
    {
        $this->setUpHttpMock();
    }

    public function tearDown()
    {
        $this->tearDownHttpMock();

        // remove the dir for dump files
        $pathDump = realpath(static::PATH_DUMP);
        if (is_dir($pathDump)) {
            $pathDump = escapeshellarg($pathDump);
            if ($pathDump) {
                exec("rm -rf $pathDump");
            }
        }
    }

    private function getResourceFile($filename)
    {
        $baseDir = 'test/resources/client/google/';
        return file_get_contents($baseDir . $filename);
    }

    /**
     * @group realSerp
     */
    public function testRealQueryWithoutRegion()
    {
        $config = [
            'httpHeaders' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            ],
            'client' => [
                'Google' => [
                    'httpClientOptions' => [
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 30,
                    ]
                ]
            ],
        ];
        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $query = new Query('news');
        $query->setMaxNumberItems(10);

        $result = $serps->executeQueryBy($client, $query);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(10, $result->countItems());
    }

    /**
     * @group realSerp
     */
    public function testRealQueryWithRegion()
    {
        $config = [
            'httpHeaders' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            ],
            'client' => [
                'Google' => [
                    'httpClientOptions' => [
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 30,
                    ]
                ]
            ],
        ];
        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $query = new Query('news');
        $query->setMaxNumberItems(10);

        // result for the 'Russia'
        $query->setSearchRegion([
            'domainZone' => 'ru',
            'countryCode' => 'RU',
        ]);

        $ruResult = $serps->executeQueryBy($client, $query);

        $this->assertInstanceOf(Result::class, $ruResult);
        $this->assertEquals(10, $ruResult->countItems());

        $query = new Query('news');
        $query->setMaxNumberItems(10);

        // result for the 'France'
        $query->setSearchRegion([
            'domainZone' => 'fr',
            'countryCode' => 'FR',
        ]);

        $frResult = $serps->executeQueryBy($client, $query);

        $this->assertInstanceOf(Result::class, $frResult);
        $this->assertEquals(10, $frResult->countItems());
        
        $this->assertNotEquals(
            $ruResult->getItems(),
            $frResult->getItems(),
            'The region is not changed'
        );
    }

    /**
     * @group realSerp
     */
    public function testRealQueryWithGeoCoord()
    {
        // Regionally dependent search term
        $searchTerm = 'новости';
        $maxNumberItems = 10;

        $config = [
            'httpHeaders' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            ],
            'client' => [
                'Google' => [
                    'httpClientOptions' => [
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 30,
                    ]
                ]
            ],
        ];
        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        
        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        // result for the 'Russia'
        $query->setSearchRegion([
            'domainZone' => 'ru',
            'countryCode' => 'RU',

            // The Moscow city
            'coordinates' => [
                'latitude' => 55.755826,
                'longitude' => 37.3193288,
            ],
        ]);

        $resultForMoscow = $serps->executeQueryBy($client, $query);

        $this->assertInstanceOf(Result::class, $resultForMoscow);
        $this->assertEquals($maxNumberItems, $resultForMoscow->countItems());


        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        // result for the 'Russia'
        $query->setSearchRegion([
            'domainZone' => 'ru',
            'countryCode' => 'RU',

            // The Saint Petersburg city
            'coordinates' => [
                'latitude' => 59.9342802,
                'longitude' => 30.3350986,
            ],
        ]);

        $resultForSaintPetersburg = $serps->executeQueryBy($client, $query);

        $this->assertInstanceOf(Result::class, $resultForSaintPetersburg);
        $this->assertEquals($maxNumberItems, $resultForSaintPetersburg->countItems());

        $this->assertNotEquals(
            $resultForMoscow->getItems(),
            $resultForSaintPetersburg->getItems(),
            'The geolocation is not changed'
        );
    }

    public function testStandartResponse()
    {
        // creation of a web server behavior
        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/search';
                })
            ->then()
                ->body($this->getResourceFile('serp_page.html'))
            ->end();

        $this->http->setUp();

        $config = [
            'httpHeaders' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            ],
            
            'client' => [
                'Google' => [
                    'httpOnly' => true,
                    'googleHost' => $this->http->server->getConnectionString(),

                    'pathDump' => static::PATH_DUMP,
                    'dumpSerp' => true,
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;

        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        // result for the 'Russia'
        $query->setSearchRegion([
            'domainZone' => 'ru',
            'countryCode' => 'RU',

            // The Saint Petersburg city
            'coordinates' => [
                'latitude' => 59.9342802,
                'longitude' => 30.3350986,
            ],
        ]);

        $result = $serps->executeQueryBy($client, $query);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals($maxNumberItems, $result->countItems());
      
        $this->assertEquals(
            1,
            count(glob(static::PATH_DUMP . '/*.dump')),
            'dump file was not created'
        );
    }

    public function testCaptchaResponse()
    {
        $captchaAnswer = '2931919';

        // creation of a web server behavior
        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/search';
                })
            ->then()
                ->statusCode(Response::HTTP_FOUND)
                ->header('Location', '/sorry/IndexRedirect')
            ->end();

        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/sorry/IndexRedirect';
                })
            ->then()
                ->statusCode(Response::HTTP_FORBIDDEN)
                ->body($this->getResourceFile('captcha_page.html'))
            ->end();

        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/sorry/image';
                })
            ->then()
                ->body($this->getResourceFile('captcha_image.png'))
            ->end();

        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) use ($captchaAnswer) {
                    return ($request->getPathInfo() == '/sorry/CaptchaRedirect'
                        && $request->query->get('captcha') == $captchaAnswer
                    );
                })
            ->then()
                ->callback(static function (Response $response) {
                    $response->headers->setCookie(new Cookie('CaptchaSolved', 'yes'));
                })
            ->end();

        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return ($request->getPathInfo() == '/search'
                        && $request->cookies->get('CaptchaSolved') == 'yes'
                    );
                })
            ->then()
                ->body($this->getResourceFile('serp_page.html'))
            ->end();

        $this->http->setUp();

        
        $config = [
            'client' => [
                'Google' => [
                    'httpOnly' => true,
                    'googleHost' => $this->http->server->getConnectionString(),
                    'captchaSolver' => function ($imageData) use ($captchaAnswer) {
                        return $captchaAnswer;
                    },
                    'httpClientOptions' => [
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 30,
                    ],

                    'pathDump' => static::PATH_DUMP,
                    'dumpCaptchaPage' => true,
                    'dumpCaptchaImage' => true,
                    'dumpCaptchaConfirmResponse' => true,
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;
        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $result = $serps->executeQueryBy($client, $query);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals($maxNumberItems, $result->countItems());

        $this->assertEquals(
            3,
            count(glob(static::PATH_DUMP . '/*.dump')),
            'dump files was not created'
        );
    }

    public function testBadProxyExceptionWhenConnectionIsBreak()
    {
        $this->http->server->stop();

        $config = [
            'client' => [
                'Google' => [
                    'httpOnly' => true,

                    // the connection will fail
                    'googleHost' => $this->http->server->getConnectionString(),
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;

        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $this->expectException(BadProxyException::class);
        $result = $serps->executeQueryBy($client, $query);
    }

    public function testBadProxyExceptionWhenResponseNotSerpPage()
    {
        // creation of a web server behavior
        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/search';
                })
            ->then()
                ->body($this->getResourceFile('not_serp_page.html'))
            ->end();

        $this->http->setUp();

        $config = [
            'client' => [
                'Google' => [
                    'httpOnly' => true,
                    'googleHost' => $this->http->server->getConnectionString(),

                    'pathDump' => static::PATH_DUMP,
                    'dumpSerpDomError' => true,
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;

        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $this->expectException(BadProxyException::class);
        $result = $serps->executeQueryBy($client, $query);

        $this->assertEquals(
            1,
            count(glob(static::PATH_DUMP . '/*.dump')),
            'dump file was not created'
        );
    }

    public function testBadProxyExceptionWhenResponseStatusIsWrong()
    {
        // creation of a web server behavior
        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/search';
                })
            ->then()
                ->statusCode(Response::HTTP_PROXY_AUTHENTICATION_REQUIRED)
            ->end();

        $this->http->setUp();

        $config = [
            'client' => [
                'Google' => [
                    'httpOnly' => true,
                    'googleHost' => $this->http->server->getConnectionString(),

                    'pathDump' => static::PATH_DUMP,
                    'dumpInvalidResponse' => true,
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;

        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $this->expectException(BadProxyException::class);
        $result = $serps->executeQueryBy($client, $query);

        $this->assertEquals(
            1,
            count(glob(static::PATH_DUMP . '/*.dump')),
            'dump file was not created'
        );
    }

    public function testBannedProxyException()
    {
        // creation of a web server behavior
        $this->http->mock
            ->once()
            ->when()
                ->callback(static function (Request $request) {
                    return $request->getPathInfo() == '/search';
                })
            ->then()
                ->statusCode(Response::HTTP_FORBIDDEN)
                ->body($this->getResourceFile('banned_page.html'))
            ->end();

        $this->http->setUp();

        $config = [
            'client' => [
                'Google' => [
                    'httpOnly' => true,
                    'googleHost' => $this->http->server->getConnectionString(),
                ],
            ]
        ];

        $serps = new SerpsFacade($config);
        $client = $serps->createClientByName('Google');

        $searchTerm = 'test';
        $maxNumberItems = 10;

        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $this->expectException(BannedProxyException::class);
        $result = $serps->executeQueryBy($client, $query);
    }
}
