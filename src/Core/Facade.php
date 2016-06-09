<?php

namespace RubtsovAV\Serps\Core;

use RubtsovAV\Serps\Core\Client\Client;
use RubtsovAV\Serps\Core\Client\Factory as ClientFactory;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Executor as QueryExecutor;
use RubtsovAV\Serps\Core\Exception\CanContinueExecutionException;
use RubtsovAV\Serps\Core\Exception\NotAvailableProxyException;

class Facade
{
    use ConfigTrait;

    /**
     * Execute the query by the client.
     *
     * @param Client $client
     * @param Query $query
     *
     * @return Query\Result
     */
    public function executeQueryBy(Client $client, Query $query)
    {
        $queryExecutor = $this->createPreparedQueryExecutor($client, $query);
        return $queryExecutor->execute();
    }

    /**
     * Execute the query by the client until it receives the result.
     *
     * @param Client $client
     * @param Query $query
     *
     * @return Query\Result
     */
    public function longExecuteQueryBy(Client $client, Query $query)
    {
        $queryExecutor = $this->createPreparedQueryExecutor($client, $query);
        while (true) {
            try {
                $result = $queryExecutor->execute();
                break;
            } catch (CanContinueExecutionException $ex) {
                continue;
            } catch (NotAvailableProxyException $ex) {
                sleep(10);
                continue;
            }
        }
        return $result;
    }

    /**
     * Create prepared to execute the query executor.
     *
     * @param Client $client
     * @param Query $query
     *
     * @return QueryExecutor
     */
    public function createPreparedQueryExecutor(Client $client, Query $query)
    {
        $queryExecutor = new QueryExecutor($this->config);
        $queryExecutor->prepare($client, $query);
        return $queryExecutor;
    }

    /**
     * @param string $clientName
     *
     * @return Client
     *
     * @throws ClientNotFoundException
     *   When the client with that name is not found.
     */
    public function createClientByName($clientName)
    {
        $clientConfig = $this->getClientConfigByName($clientName);
        return ClientFactory::getClientInstanceByName($clientName, $clientConfig);
    }

    /**
     * @param string $clientName
     *
     * @return array
     */
    public function getClientConfigByName($clientName)
    {
        $clientConfig = [];
        if (isset($this->config['client'][$clientName])) {
            $clientConfig = $this->config['client'][$clientName];
        }
        
        if (!isset($clientConfig['logger'])) {
            $clientConfig['logger'] = $this->getLogger();
        }
        return $clientConfig;
    }
    
}
