<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\QueryManagerException;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\WildcardMappingStrategy;

/**
 * Class to help manage running queries against APIs
 */
class QueryManager
{
    /** @var Query[]  */
    private array $queries = [];

    /** @var DataProviderInterface[] */
    private array $dataProviders = [];

    /** @var string[] */
    private array $dataProvidersForQueries = [];

    /**
     * Add a data provider to use with queries
     * @param string $name
     * @param DataProviderInterface $dataProvider
     */
    public function addDataProvider(string $name, DataProviderInterface $dataProvider)
    {
        $this->dataProviders[$name] = $dataProvider;
    }

    /**
     * Does the named data provider exist?
     * @param string $name
     * @return bool
     */
    public function hasDataProvider(string $name): bool
    {
        return (isset($this->dataProviders[$name]));
    }

    /**
     * Return named data provider
     * @param string $name
     * @return DataProviderInterface|null
     */
    public function getDataProvider(string $name): ?DataProviderInterface
    {
        if ($this->hasDataProvider($name)) {
            return $this->dataProviders[$name];
        }
        return null;
    }

    /**
     * Return data provider to use with a named query
     * @param string $queryName
     * @return DataProviderInterface
     */
    public function getDataProviderForQuery(string $queryName): DataProviderInterface
    {
        $dataProvider = null;
        if (isset($this->dataProvidersForQueries[$queryName])) {
            $dataProvider = $this->getDataProvider($this->dataProvidersForQueries[$queryName]);
        }

        if (!($dataProvider instanceof DataProviderInterface)) {
            throw new QueryManagerException(sprintf('Cannot find data provider for query name %s', $queryName));
        }

        return $dataProvider;
    }

    /**
     * Add a query (does not run the query, this happens on data access)
     *
     * @param string $dataProviderName
     * @param Query $query
     * @param string|null $queryName
     * @throws QueryManagerException
     */
    public function add(string $dataProviderName, Query $query, ?string $queryName = null)
    {
        // Get query name from parameters or query object
        if ($queryName === null) {
            $queryName = $query->getName();
        }
        if (array_key_exists($queryName, $this->queries)) {
            throw new QueryManagerException(sprintf('Cannot add query since query with same name "%s" already exists', $query->getName()));
        }

        // Check data provider
        if (!$this->hasDataProvider($dataProviderName)) {
            throw new QueryManagerException(sprintf('Data provider %s not found, please add this first via QueryManagerOld::addDataProvider()', $dataProviderName));
        }
        $dataProvider = $this->getDataProvider($dataProviderName);
        if (!$query->checkDataProvider($dataProvider)) {
            throw new QueryManagerException(sprintf('Data provider %s is not compatible with passed query %s', $dataProviderName, $queryName));
        }

        // Prepare request
        $query->prepareRequest($this->getDataProvider($dataProviderName));

        $this->queries[$queryName] = $query;
        $this->dataProvidersForQueries[$queryName] = $dataProviderName;
    }

    /**
     * Run queries on data access
     *
     * Only runs a query once, you can force a query to be re-run via $query->clearResponse()
     */
    protected function runQueries()
    {
        // Build array of concurrent queries for all responses that have not been executed
        $concurrent = [];
        foreach ($this->getQueries() as $queryName => $query) {
            if (!$query->hasResponse() || $query->hasResponseRun()) {
                continue;
            }
            $concurrent[$queryName] = $query;
        }

        // Run multiple queries concurrently for performance
        foreach ($concurrent as $queryName => $query) {
            $dataProvider = $this->getDataProviderForQuery($queryName);
            if ($query->isSubRequest()) {
                $dataProvider->suppressErrors();
            } else {
                $dataProvider->suppressErrors(false);
            }
            $dataProvider->runRequest($query->getResponse());
        }
    }

    /**
     * Return all queries
     * @return Query[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Does a named query exist?
     * @param string $name
     * @return bool
     */
    public function hasQuery(string $name): bool
    {
        return (isset($this->queries[$name]));
    }

    /**
     * Return a query by name
     * @param string $name
     * @return Query|null
     */
    public function getQuery(string $name): ?Query
    {
        if ($this->hasQuery($name)) {
            return $this->queries[$name];
        }
        return null;
    }

    /**
     * Get response by query name
     * @param string $name
     * @return CacheableResponse
     * @throws QueryManagerException
     */
    public function getResponse(string $queryName): CacheableResponse
    {
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }
        $query = $this->getQuery($queryName);
        $this->runQueries();
        if (!$query->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        return $query->getResponse();
    }

    /**
     * Return a data item
     *
     * Default functionality is to return decoded data as an array
     *
     * @param string $queryName
     * @throws QueryManagerException
     *@todo Create an Item object to return (which is cache aware)
     */
    public function getItem(string $queryName): array
    {
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }

        // Run queries
        $dataProvider = $this->getDataProviderForQuery($queryName);
        $this->runQueries();
        $query = $this->getQuery($queryName);
        if (!$query->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        // Return decoded data
        return $dataProvider->decode($query->getResponse());
    }

    /**
     * Return a collection of data items with pagination
     *
     * Default functionality is to return decoded data as an array with pagination
     *
     * @param string $queryName
     * @throws QueryManagerException
     */
    public function getCollection(string $queryName): Collection
    {
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }
        $query = $this->getQuery($queryName);
        $dataProvider = $this->getDataProviderForQuery($queryName);

        // Run queries
        $this->runQueries();
        if (!$query->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        // Return collections object with decoded data & pagination
        $response = $query->getResponse();
        $data = $dataProvider->decode($response);

        $mapper = new MapCollection(new WildcardMappingStrategy());
        $mapper->totalResults($query->totalResultsPropertyPath)
                ->resultsPerPage($query->resultsPerPagePropertyPath)
                ->currentPage($query->currentPagePropertyPath);
        if ($query->paginationDataFromHeaders) {
            $mapper->fromPaginationData($response->getHeaders());
        }

        return $mapper->map($data);
    }

}