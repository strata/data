<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Rest;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\MappingStrategyInterface;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Query\BuildQuery\BuildQuery;
use Strata\Data\Traits\PaginationPropertyTrait;

/**
 * Class to represent a data query, also has methods to return data
 *
 * This class intentionally has no constructor, so any child classes are free to implement their own constructor
 */
class Query extends QueryAbstract implements QueryInterface
{
    use PaginationPropertyTrait;

    /**
     * Data provider class required for use with this query
     * @return string
     */
    public function getRequiredDataProviderClass(): string
    {
        return Rest::class;
    }

    /**
     * Prepare a query for running
     *
     * Prepares the response object but doesn't run it - unless data is returned by the cache
     *
     * If you don't run this, it's automatically run when you access the Query::run() method
     *
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function prepare()
    {
        if (!$this->hasDataProvider()) {
            throw new QueryException('Cannot prepare query since data provider not set (do this via Query::setDataProvider or via QueryManager::addDataProvider)');
        }
        $dataProvider = $this->getDataProvider();

        // Prepare request
        $buildQuery = new BuildQuery($dataProvider);
        $this->response = $buildQuery->prepareRequest($this);
    }

    /**
     * Run a query
     *
     * Populates the response object
     *
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Strata\Data\Exception\HttpException
     * @throws \Strata\Data\Exception\HttpNotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function run()
    {
        $dataProvider = $this->getDataProvider();
        $response = $this->getResponse();

        // Prepare response if not already done
        if (!($response instanceof CacheableResponse)) {
            $this->prepare();
            $response = $this->getResponse();
        }

        if ($this->isSubRequest()) {
            $dataProvider->suppressErrors();
        }
        if ($this->isCacheEnabled()) {
            $dataProvider->enableCache($this->getCacheLifetime());
        }

        $this->response = $dataProvider->runRequest($response);

        // Reset cache & suppress errors to previous values
        $this->dataProvider->resetEnableCache();
        $this->dataProvider->resetSuppressErrors();
    }

    /**
     * Return mapping strategy to use to map a single item
     *
     * You can override this in child classes
     *
     * @return MappingStrategyInterface|array
     */
    public function getMapping()
    {
        return new WildcardMappingStrategy();
    }

    /**
     * Return data from response
     * @return mixed
     * @throws \Strata\Data\Exception\MapperException
     */
    public function get()
    {
        // Run response, if not already run
        if (!$this->hasResponseRun()) {
            $this->run();
        }

        // Simple mapping from root property path
        $data = $this->dataProvider->decode($this->getResponse());
        $mapper = new MapItem($this->getMapping());
        return $mapper->map($data, $this->getRootPropertyPath());
    }

    /**
     * Return collection of data from a query response
     * @return Collection
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Strata\Data\Exception\HttpException
     * @throws \Strata\Data\Exception\HttpNotFoundException
     * @throws \Strata\Data\Exception\MapperException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getCollection(): Collection
    {
        // Run response, if not already run
        if (!$this->hasResponseRun()) {
            $this->run();
        }

        // Simple mapping from root property path
        $response = $this->getResponse();
        $data = $this->dataProvider->decode($response);
        $mapper = new MapCollection($this->getMapping());

        // Populate pagination data if empty
        if (empty($this->getTotalResults())) {
            $this->setTotalResults(count($data));
        }
        if (empty($this->getResultsPerPage())) {
            $this->setResultsPerPage(count($data));
        }

        // Use pagination setup query
        $mapper->setTotalResults($this->getTotalResults())
            ->setResultsPerPage($this->getResultsPerPage())
            ->setCurrentPage($this->getCurrentPage());
        if ($this->isPaginationDataFromHeaders()) {
            $mapper->fromPaginationData($response->getHeaders());
        }

        return $mapper->map($data, $this->getRootPropertyPath());
    }
}
