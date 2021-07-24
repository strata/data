<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\MissingDataProviderException;
use Strata\Data\Exception\QueryManagerException;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\CacheableResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class to help manage running queries against data providers
 */
class QueryManager
{
    /** @var DataProviderInterface[] */
    private array $dataProviders = [];

    /** @var QueryInterface[] */
    private array $queries = [];

    private array $dataProviderClassMap = [];
    private bool $cacheEnabled = false;
    private ?CacheInterface $cache = null;
    private ?int $cacheLifetime = null;
    private array $cacheTags = [];

    /**
     * Add a data provider to use with queries
     * @param string $name
     * @param DataProviderInterface $dataProvider
     */
    public function addDataProvider(string $name, DataProviderInterface $dataProvider)
    {
        if ($this->isCacheEnabled()) {
            // Enable cache on data provider
            $dataProvider->setCache($this->cache, $this->cacheLifetime);
        } elseif ($this->hasCache()) {
            // If cache exists but disabled, add to data provider but disable it
            $dataProvider->setCache($this->cache, $this->cacheLifetime);
            $dataProvider->disableCache();
        }
        if (!empty($this->cacheTags)) {
            // If cache tags exist and cache adapter is compatible, set them
            if ($dataProvider->getCache()->isTaggable()) {
                $dataProvider->setCacheTags($this->cacheTags);
            }
        }

        $this->dataProviders[$name] = $dataProvider;
        $this->dataProviderClassMap[] = [
            'name' => $name,
            'class' => get_class($dataProvider),
        ];
    }

    /**
     * Set and enable the cache
     *
     * @param CacheInterface $cache
     * @param int $defaultLifetime Default cache lifetime
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null)
    {
        $this->cache = $cache;
        $this->cacheEnabled = true;

        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->setCache($this->cache, $defaultLifetime);
        }
    }

    /**
     * Whether a cache object is set
     * @return bool
     */
    public function hasCache(): bool
    {
        return $this->cache instanceof CacheInterface;
    }

    /**
     * Is the cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
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
     * @throws QueryManagerException
     */
    public function setCacheTags(array $tags = [])
    {
        $taggable = 0;
        $this->cacheTags = $tags;
        foreach ($this->dataProviders as $dataProvider) {
            // If cache adapter is compatible, set them
            if ($dataProvider->isCacheEnabled() && $dataProvider->getCache()->isTaggable()) {
                $dataProvider->setCacheTags($tags);
                $taggable++;
            }
        }

        if ($taggable === 0) {
            throw new QueryManagerException('No data providers contain a cache adapter that is compatible with tagging (must implement Symfony\Component\Cache\Adapter\TagAwareAdapter)');
        }
    }

    /**
     * Return cache tags currently set to the query manager
     * @return array
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
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
     * Return data provider by name
     * @param string $name
     * @return DataProviderInterface
     * @throws MissingDataProviderException
     */
    public function getDataProvider(string $name): DataProviderInterface
    {
        if (!$this->hasDataProvider($name)) {
            throw new MissingDataProviderException(sprintf('Cannot find data provider %s', $name));
        }
        return $this->dataProviders[$name];
    }

    /**
     * Return first compatible data provider we can find for passed query
     * @param QueryInterface $query
     * @return DataProviderInterface
     * @throws MissingDataProviderException
     */
    public function getDataProviderForQuery(QueryInterface $query): DataProviderInterface
    {
        $requiredClass = $query->getRequiredDataProviderClass();
        foreach ($this->dataProviderClassMap as $item) {
            $class = $item['class'];
            $name = $item['name'];
            if ($class === $requiredClass) {
                return $this->getDataProvider($name);
            }
        }
        throw new MissingDataProviderException(sprintf('Cannot find a compatible data provider (%s) for query', $class));
    }

    /**
     * Add a query (does not run the query, this happens on data access)
     *
     * @param string $queryName
     * @param QueryInterface $query Query
     * @param string|null $dataProviderName Data provider to use with query, if not set use last added data provider
     * @throws QueryManagerException
     */
    public function add(string $queryName, QueryInterface $query, ?string $dataProviderName = null)
    {
        if ($this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Query name %s already exists in the Query Manager, please give this query a unique name', $queryName));
        }

        if (!$query->hasDataProvider()) {
            // Get data provider (pass as 2nd argument or use a compatible data provider)
            if ($dataProviderName === null) {
                $dataProvider = $this->getDataProviderForQuery($query);
            } else {
                $dataProvider = $this->getDataProvider($dataProviderName);
            }
            $query->setDataProvider($dataProvider);
        }

        // Prepare request
        $query->prepare();

        // Add query to query stack
        $this->queries[$queryName] = $query;
    }

    /**
     * Run queries on data access
     *
     * Only runs a query once, you can force a query to be re-run via $query->clearResponse()
     *
     * This should run multiple queries concurrently
     */
    protected function runQueries()
    {
        foreach ($this->queries as $query) {
            // Skip if already run, you can still manually re-run a query via $query->run()
            if ($query->hasResponseRun()) {
                continue;
            }

            // Run a query
            $query->run();
        }
    }

    /**
     * Does a named query exist?
     * @param string $name
     * @return bool
     */
    public function hasQuery(string $name): bool
    {
        return array_key_exists($name, $this->queries);
    }

    /**
     * Return a query by name
     * @param string $name
     * @return QueryInterface|null
     */
    public function getQuery(string $name): ?QueryInterface
    {
        if ($this->hasQuery($name)) {
            return $this->queries[$name];
        }
        return null;
    }

    /**
     * Get response by query name
     * @param string $queryName
     * @return CacheableResponse
     * @throws QueryManagerException
     */
    public function getResponse(string $queryName): CacheableResponse
    {
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }
        $query = $this->getQuery($queryName);

        if (!$query->hasResponseRun()) {
            $this->runQueries();
        }
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
     * @param string|null $rootPropertyPath Property path to root element to select data from, null if no data returned
     * @return mixed
     * @throws QueryManagerException
     */
    public function get(string $queryName, ?string $rootPropertyPath = null)
    {
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }
        $query = $this->getQuery($queryName);

        if (!$query->hasResponseRun()) {
            $this->runQueries();
        }
        if (!$query->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        return $query->get();
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
        if (!$this->hasQuery($queryName)) {
            throw new QueryManagerException(sprintf('Cannot find query with query name "%s"', $queryName));
        }
        $query = $this->getQuery($queryName);

        if (!$query->hasResponseRun()) {
            $this->runQueries();
        }
        if (!$query->hasResponseRun()) {
            throw new QueryManagerException(sprintf('Response has not run for query name "%s"', $queryName));
        }

        return $query->getCollection();
    }
}
