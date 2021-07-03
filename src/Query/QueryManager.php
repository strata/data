<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Cache\DataCache;
use Strata\Data\Collection;
use Strata\Data\DataProviderCommonTrait;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\QueryManagerException;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Query\BuildQuery\BuildGraphQLQuery;
use Strata\Data\Query\BuildQuery\BuildQuery;
use Strata\Data\Query\QueryManager\QueryStack;
use Strata\Data\Query\QueryManager\StackItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class to help manage running queries against APIs
 */
class QueryManager
{
    /** @var DataProviderInterface[] */
    private array $dataProviders = [];

    /** @var string */
    public ?string $lastDataProviderName = null;

    /** @var QueryStack  */
    private QueryStack $queryStack;

    private bool $cacheEnabled = false;
    private ?CacheInterface $cache = null;
    private ?int $cacheLifetime = null;
    private array $cacheTags = [];

    /**
     * QueryManager constructor.
     */
    public function __construct()
    {
        $this->queryStack = new QueryStack();
    }

    /**
     * Add a data provider to use with queries
     * @param string $name
     * @param DataProviderInterface $dataProvider
     */
    public function addDataProvider(string $name, DataProviderInterface $dataProvider)
    {
        if ($this->cacheEnabled) {
            // Enable cache on data provider
            $dataProvider->setCache($this->cache, $this->cacheLifetime);

        } elseif ($this->cache instanceof CacheInterface) {
            // If cache exists but disabled, add to data provider but disable it
            $dataProvider->setCache($this->cache, $this->cacheLifetime);
            $dataProvider->disableCache();
        }
        if (!empty($this->cacheTags)) {
            // If cache tags exist, add them to data provider
            $dataProvider->setCacheTags($this->cacheTags);
        }
        
        $this->dataProviders[$name] = $dataProvider;
        $this->lastDataProviderName = $name;
    }

    /**
     * Set and enable the cache
     *
     * @param CacheInterface $cache
     * @param int $defaultLifetime Default cache lifetime
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null)
    {
        $this->cache = new DataCache($cache, $defaultLifetime);
        $this->cacheEnabled = true;

        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->setCache($this->cache, $defaultLifetime);
        }
    }

    /**
     * Enable cache for subsequent data requests
     *
     * @param ?int $lifetime
     * @throws CacheException If cache not set
     */
    public function enableCache(?int $lifetime = null)
    {
        $this->cacheEnabled = true;
        $this->cacheLifetime = $lifetime;
        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->enableCache($lifetime);
        }
    }

    /**
     * Disable cache for subsequent data requests
     *
     */
    public function disableCache()
    {
        $this->cacheEnabled = false;
        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->disableCache();
        }
    }

    /**
     * Set cache tags to apply to all future saved cache items
     *
     * To remove tags do not pass any arguments and tags will be reset to an empty array
     *
     * @param array $tags
     * @throws CacheException
     */
    public function setCacheTags(array $tags = [])
    {
        $this->cacheTags = $tags;
        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->getCache()->setTags($tags);
        }
    }

    /**
     * Set HTTP client for all data providers
     *
     * Primarily used for mocking the HTTPClient for testing
     *
     * @param HttpClientInterface $client
     */
    public function setHttpClient(HttpClientInterface $client)
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider instanceof Http) {
                $dataProvider->setHttpClient($client);
            }
        }
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
     * @return DataProviderInterface
     * @throws QueryManagerException
     */
    public function getDataProvider(string $name): DataProviderInterface
    {
        if (!$this->hasDataProvider($name)) {
            throw new QueryManagerException(sprintf('Cannot find data provider %s', $name));
        }
        return $this->dataProviders[$name];
    }

    /**
     * Get last data provider name
     * @return string
     * @throws QueryManagerException
     */
    public function getLastDataProviderName(): string
    {
        if ($this->lastDataProviderName === null) {
            throw new QueryManagerException('You must set at least one data provider to the query manager');
        }
        return $this->lastDataProviderName;
    }

    /**
     * Add a query (does not run the query, this happens on data access)
     *
     * @param Query $query Query
     * @param string $dataProviderName Data provider to use with query, if not set use last added data provider
     * @throws QueryManagerException
     */
    public function add(Query $query, ?string $dataProviderName = null)
    {
        if (!$query->hasName()) {
            throw new QueryManagerException('Query must have a name before it is added to the Query Manager');
        }
        if ($this->queryStack->offsetExists($query->getName())) {
            throw new QueryManagerException(sprintf('Query name %s already exists in the Query Manager, please give this query a unique name', $query->getName()));
        }

        // Get data provider (pass as 2nd argument or use current data provider)
        if ($dataProviderName === null) {
            $dataProviderName = $this->getLastDataProviderName();
        }
        $dataProvider = $this->getDataProvider($dataProviderName);

        // Prepare request
        switch (get_class($query)) {
            case GraphQLQuery::class:
                $buildQuery = new BuildGraphQLQuery($dataProvider);
                break;
            case Query::class:
            default:
                $buildQuery = new BuildQuery($dataProvider);
                break;
        }

        $response = $buildQuery->prepareRequest($query);

        // Add query, data provider name & response to query stack
        $this->queryStack->add($query->getName(), new StackItem($query, $dataProviderName, $response));
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
        foreach ($this->getQueryStack() as $name => $item) {
            if ($item->hasResponseRun()) {
                continue;
            }

            $query = $item->getQuery();
            $dataProvider = $this->getDataProvider($item->getDataProviderName());
            $response = $item->getResponse();

            if ($query->isSubRequest()) {
                $dataProvider->suppressErrors();
            } else {
                $dataProvider->suppressErrors(false);
            }
            $dataProvider->runRequest($response);
        }
    }

    /**
     * Return all queries
     * @return QueryStack Collection of queries
     */
    public function getQueryStack(): QueryStack
    {
        return $this->queryStack;
    }

    /**
     * Does a named query exist?
     * @param string $name
     * @return bool
     */
    public function hasQuery(string $name): bool
    {
        return (isset($this->queryStack[$name]));
    }

    /**
     * Return a query by name
     * @param string $name
     * @return Query|null
     */
    public function getQuery(string $name): ?StackItem
    {
        if ($this->hasQuery($name)) {
            return $this->queryStack[$name];
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
        if (!$this->queryStack->exists($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }

        $this->runQueries();

        $item = $this->queryStack->get($queryName);
        if (!$item->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        return $item->getResponse();
    }

    /**
     * Return a data item
     *
     * Default functionality is to return decoded data as an array
     *
     * @param string $queryName
     * @param string|null $rootPropertyPath Property path to root element to select data from
     * @throws QueryManagerException
     *@todo Create an Item object to return (which is cache aware)
     */
    public function getItem(string $queryName, ?string $rootPropertyPath = null): array
    {
        if (!$this->queryStack->exists($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }

        // Run queries
        $this->runQueries();

        $item = $this->queryStack->get($queryName);
        if (!$item->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        // Return decoded data
        $dataProvider = $this->getDataProvider($item->getDataProviderName());
        $query = $item->getQuery();
        $data = $dataProvider->decode($item->getResponse());

        $mapper = new MapItem(new WildcardMappingStrategy());
        return $mapper->map($data, $rootPropertyPath);
    }

    /**
     * Return a collection of data items with pagination
     *
     * Default functionality is to return decoded data as an array with pagination
     *
     * @param string $queryName
     * @param string|null $rootPropertyPath Property path to root element to select data from
     * @throws QueryManagerException
     */
    public function getCollection(string $queryName, ?string $rootPropertyPath = null): Collection
    {
        if (!$this->queryStack->exists($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }

        // Run queries
        $this->runQueries();

        $item = $this->queryStack->get($queryName);
        if (!$item->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        // Return collections object with decoded data & pagination
        $dataProvider = $this->getDataProvider($item->getDataProviderName());
        $query = $item->getQuery();
        $response = $item->getResponse();
        $data = $dataProvider->decode($response);

        $mapper = new MapCollection(new WildcardMappingStrategy());
        $mapper->totalResults($query->totalResultsPropertyPath)
                ->resultsPerPage($query->resultsPerPagePropertyPath)
                ->currentPage($query->currentPagePropertyPath);
        if ($query->paginationDataFromHeaders) {
            $mapper->fromPaginationData($response->getHeaders());
        }

        return $mapper->map($data, $rootPropertyPath);
    }

}