<?php

namespace RubtsovAV\Serps\Test\Core\Query;

use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Executor;
use RubtsovAV\Serps\Core\Query\Result;
use RubtsovAV\Serps\Core\Guise\Guise;
use RubtsovAV\Serps\Core\Guise\Factory as GuiseFactory;
use RubtsovAV\Serps\Core\Guise\Queue as GuiseQueue;
use RubtsovAV\Serps\Core\Client\Client;
use RubtsovAV\Serps\Core\Exception\BadProxyException;
use RubtsovAV\Serps\Core\Exception\BannedProxyException;

/**
 * @covers RubtsovAV\Serps\Core\Query\Executor
 */
class ExecutorTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods([
                'executeQuery',
            ])
            ->getMockForAbstractClass();

        $executor = new Executor();
        $executor->prepare($clientMock, $query);

        $this->assertEquals($clientMock, $executor->getClient());
        $this->assertEquals($query, $executor->getQuery());

        return $executor;
    }

    /**
     * @depends testPrepare
     */
    public function testExecute(Executor $executor)
    {
        $result = new Result();

        $clientMock = $executor->getClient();
        $clientMock
            ->method('executeQuery')
            ->will($this->returnValue($result));

        $this->assertEquals($result, $executor->execute());
        $this->assertInstanceOf(Guise::class, $clientMock->getGuise());

        return $executor;
    }

    /**
     * @depends testExecute
     */
    public function testExecuteWithGuiseQueue(Executor $executor)
    {
        $guiseFactory = new GuiseFactory();
        $guiseQueue = GuiseQueue::getInstance();

        $guise = $guiseFactory->createGuise();
        $guiseQueue->enqueue($guise);

        $executor->execute();
        $this->assertEquals($guise, $guiseQueue->dequeue());
    }

    /**
     * @depends testExecute
     */
    public function testExecuteWithThrowBadProxyException(Executor $executor)
    {
        $clientMock = $executor->getClient();
        $clientMock
            ->method('executeQuery')
            ->will($this->throwException(new BadProxyException));

        $this->expectException(BadProxyException::class);
        $executor->execute();
    }

    /**
     * @depends testExecute
     */
    public function testExecuteWithThrowBannedProxyException(Executor $executor)
    {
        $clientMock = $executor->getClient();
        $clientMock
            ->method('executeQuery')
            ->will($this->throwException(new BannedProxyException));

        $this->expectException(BannedProxyException::class);
        $executor->execute();
    }
}
