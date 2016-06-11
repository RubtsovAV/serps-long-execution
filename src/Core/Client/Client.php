<?php

namespace RubtsovAV\Serps\Core\Client;

use RubtsovAV\Serps\Core\ConfigTrait;
use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Result;
use RubtsovAV\Serps\Core\Guise\Guise;

abstract class Client
{
    use ConfigTrait;

    protected $query;
    protected $queryResult;
    protected $guise;

    public function getName()
    {
        $className = get_class($this);
        $path = explode('\\', $className);
        return end($path);
    }

    public function setGuise(Guise $guise)
    {
        $this->guise = $guise;
    }

    public function getGuise()
    {
        return $this->guise;
    }

    public function prepareQuery(Query $query)
    {
        $this->query = $query;
        $this->queryResult = new Result($this->config);
        $this->queryResult->setQuery($query);
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Execution of the prepared query.
     *
     * @return QueryResult
     *
     * @throws CanContinueExecutionException
     *   When you can repeat of the executeQuery call.
     *
     * @throws BadProxyException
     *   When the guise proxy is not working.
     *
     * @throws BannedProxyException
     *   When the guise proxy was banned in search engine.
     *
     * @throws BannedProxyException
     *   When the guise proxy was banned in search engine.
     */
    abstract public function executeQuery();

    protected function canSolveCaptcha()
    {
        $this->logger->debug('Client->canSolveCaptcha');

        if (!isset($this->config['captchaSolver'])) {
            $this->logger->notice('you need to set the captchaSolver in config for that client');
            return false;
        }

        if (!is_callable($this->config['captchaSolver'])) {
            throw new CaptchaSolverException('captchaSolver must be a callable');
            return false;
        }

        return true;
    }

    protected function createDump($prefixName, $data)
    {
        $this->logger->debug('Client->createDump');

        if (!isset($this->config['pathDump'])) {
            $this->logger->notice('pathDump config is not set');
            return;
        }

        if (empty($this->config['pathDump'])) {
            $this->logger->notice('pathDump can\'t be empty');
            return;
        }

        $pathDump = $this->config['pathDump'];
        if (substr($pathDump, -1) != '/') {
            $pathDump .= '/';
        }

        if (!is_dir($pathDump)) {
            $this->logger->debug("create dir '$pathDump'");
            mkdir($pathDump, 0777, true);
        }

        do {
            $filename = $pathDump . uniqid($prefixName, true) . '.dump';
        } while (file_exists($filename));

        file_put_contents($filename, $data);
        $this->logger->info("the dump created in '$filename'");
    }
}
