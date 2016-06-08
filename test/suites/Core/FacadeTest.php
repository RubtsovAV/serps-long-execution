<?php

namespace RubtsovAV\Serps\Test\Core;

use RubtsovAV\Serps\Core\Facade as SerpsFacade;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Result;
use RubtsovAV\Serps\Core\Client\Client;
use RubtsovAV\Serps\Core\Client\Factory as ClientFactory;
use RubtsovAV\Serps\Core\Logger;
use RubtsovAV\Serps\Core\Exception\BadProxyException;
use RubtsovAV\Serps\Core\Exception\NotAvailableProxyException;

/**
 * @covers RubtsovAV\Serps\Core\Facade
 */
class FacadeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClientConfigByName()
    {
        $config = [
            'client' => [
                'MockClient' => [
                    'logger' => Logger::getInstance(),
                    'param1' => 'value1',
                    'param2' => 'value2',
                ]
            ]
        ];

        $serpsFacade = new SerpsFacade($config);
        $clientConfig = $serpsFacade->getClientConfigByName('MockClient');
        $this->assertEquals($config['client']['MockClient'], $clientConfig);
    }

    public function testGetClientConfigByNameWithGlobalLogger()
    {
        $config = [
            'client' => [
                'MockClient' => [
                    'param1' => 'value1',
                    'param2' => 'value2',
                ]
            ]
        ];

        $serpsFacade = new SerpsFacade($config);
        $clientConfig = $serpsFacade->getClientConfigByName('MockClient');
        $this->assertEquals($serpsFacade->getLogger(), $clientConfig['logger']);
    }

    public function testCreateClientByName()
    {
        $clientMock = $this->createMockClientClass('MockClient');

        $serpsFacade = new SerpsFacade();
        $client = $serpsFacade->createClientByName('MockClient');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(get_class($clientMock), $client);

        return $client;
    }

    /**
     * @depends testCreateClientByName
     */
    public function testCreatePreparedQueryExecutor(Client $client)
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $serpsFacade = new SerpsFacade();
        $queryExecutor = $serpsFacade->createPreparedQueryExecutor($client, $query);

        $this->assertEquals($client, $queryExecutor->getClient());
        $this->assertEquals($query, $queryExecutor->getQuery());
    }
    
    public function testExecuteQueryBy()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $resultMock = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $clientMock = $this->createMockClientClass('MockClient');
        $clientMock
            ->method('executeQuery')
            ->will($this->returnValue($resultMock));

        $serpsFacade = new SerpsFacade();
        $this->assertEquals($resultMock, $serpsFacade->executeQueryBy($clientMock, $query));
    }

    public function testLongExecuteQueryBy()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $resultMock = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $clientMock = $this->createMockClientClass('MockClient');
        $clientMock
            ->method('executeQuery')
            ->will($this->returnValue($resultMock));

        $serpsFacade = new SerpsFacade();
        $this->assertEquals($resultMock, $serpsFacade->longExecuteQueryBy($clientMock, $query));
    }

    public function testLongExecuteQueryByWithExceptions()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);
        $clientMock = $this->createMockClientClass('MockClient');
        $serpsFacade = new SerpsFacade();

        $resultMock = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();


        $exception =
            $this->getMockBuilder(BadProxyException::class)
            ->getMock();
        
        $clientMock->expects($this->at(0))
            ->method('executeQuery')
            ->will($this->throwException($exception));

        $clientMock->expects($this->at(1))
            ->method('executeQuery')
            ->will($this->returnValue($resultMock));

        $this->assertEquals($resultMock, $serpsFacade->longExecuteQueryBy($clientMock, $query));


        $exception =
            $this->getMockBuilder(NotAvailableProxyException::class)
            ->getMock();
        
        $clientMock->expects($this->at(0))
            ->method('executeQuery')
            ->will($this->throwException($exception));

        $clientMock->expects($this->at(1))
            ->method('executeQuery')
            ->will($this->returnValue($resultMock));
        
        $this->assertEquals($resultMock, $serpsFacade->longExecuteQueryBy($clientMock, $query));
    }

    private function createMockClientClass($clientName)
    {
        static $classes = [];
        if (isset($classes[$clientName])) {
            return $classes[$clientName];
        }
        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods([
                'executeQuery',
            ])
            ->getMockForAbstractClass();

        class_alias(
            get_class($clientMock),
            ClientFactory::CLIENT_NAMESPACE . $clientName
        );

        $classes[$clientName] = $clientMock;

        return $classes[$clientName];
    }
}
