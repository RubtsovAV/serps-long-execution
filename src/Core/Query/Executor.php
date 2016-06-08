<?php

namespace RubtsovAV\Serps\Core\Query;

use RubtsovAV\Serps\Core\ConfigTrait;
use RubtsovAV\Serps\Core\Client\Client;
use RubtsovAV\Serps\Core\Guise\Guise;
use RubtsovAV\Serps\Core\Guise\Queue as GuiseQueue;
use RubtsovAV\Serps\Core\Guise\Factory as GuiseFactory;
use RubtsovAV\Serps\Core\Exception\BadProxyException;
use RubtsovAV\Serps\Core\Exception\BannedProxyException;

class Executor
{
    use ConfigTrait;

    protected $client;
    protected $query;

    /**
     * @return Client
     *   The prepared client.
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Query
     *   The prepared query.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Preparing to execute the query.
     *
     * @param Client $client
     *   The client for the query execution.
     *
     * @param Query $query
     *   The query, which be executed.
     */
    public function prepare(Client $client, Query $query)
    {
        $this->client = $client;
        $this->query = $query;

        $this->client->prepareQuery($query);
    }

    /**
     * Execution of the prepared query.
     *
     * @return Result
     *
     * @throws CanContinueExecutionException
     *   When you can repeat of the execute call.
     *
     * @throws NotAvailableProxyException
     *   When the getter of proxy was returned null.
     */
    public function execute()
    {
        $this->logger->debug('QueryExecutor->execute()');

        $guise = $this->getGuise();
        $this->client->setGuise($guise);

        try {
            $this->logger->debug('try client->executeQuery()');

            $result = $this->client->executeQuery();
        } catch (BadProxyException $ex) {
            $this->logger->debug('client->executeQuery() throw BadProxyException');

            $guise->scold();
            throw $ex;
        } catch (BannedProxyException $ex) {
            $this->logger->debug('client->executeQuery() throw BannedProxyException');

            $guise->banned();
            throw $ex;
        }
        $this->logger->info('The query was successful executed');

        $guise->praise();
        $this->enqueueGuise($guise);
        return $result;
    }

    /**
     * Get the guise from GuiseQueue or create it by GuiseFactory.
     *
     * @return Guise
     *
     * @throws NotAvailableProxyException
     *   When the getter of proxy was returned null.
     */
    protected function getGuise()
    {
        $this->logger->debug('QueryExecutor->getGuise()');

        $guiseQueue = GuiseQueue::getInstance();
        if (!$guiseQueue->isEmpty()) {
            $this->logger->debug('get Guise from GuiseQueue');

            $guise = $guiseQueue->dequeue();
        } else {
            $this->logger->debug('create Guise by GuiseFactory');

            $guiseFactory = new GuiseFactory($this->config);
            $guise = $guiseFactory->createGuise();
        }
        return $guise;
    }

    /**
     * The guise enqueue for reuse it in next query.
     *
     * @param Guise $guise
     */
    protected function enqueueGuise(Guise $guise)
    {
        $this->logger->debug('QueryExecutor->enqueueGuise()');
        GuiseQueue::getInstance()->enqueue($guise);
    }
}
