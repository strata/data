<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Rest;
use Strata\Data\Traits\PaginationPropertyTrait;

/**
 * Common functionality for queries
 */
abstract class QueryAbstract implements QueryInterface
{
    use PaginationPropertyTrait;

    const TAG_GLOBAL = 'global';
    const TAG_NEW = 'new-';

    protected ?DataProviderInterface $dataProvider = null;
    protected array $options = [];
    protected ?CacheableResponse $response = null;

    protected ?bool $cacheableRequest = null;
    protected ?int $cacheLifetime = null;
    protected array $cacheTags = [];
    protected bool $subRequest = false;
    protected string $uri;
    protected array $params = [];
    protected array $fields = [];
    protected ?string $rootPropertyPath = null;
    protected string $multipleValuesSeparator = ',';
    protected string $fieldParameter = 'fields';
    protected string $resultsPerPageParam = 'limit';
    protected string $pageParam = 'page';
    protected bool $paginationDataFromHeaders = false;
    protected bool $concurrent = true;

    /**
     * Data provider class required for use with this query
     * @return string
     */
    public function getRequiredDataProviderClass(): string
    {
        return Rest::class;
    }

    /**
     * Set data provider to use with this query
     *
     * @param DataProviderInterface $dataProvider
     * @return $this
     */
    public function setDataProvider(DataProviderInterface $dataProvider): self
    {
        $class = $this->getRequiredDataProviderClass();
        if (!($dataProvider instanceof $class)) {
            throw new QueryException(sprintf('Cannot set data provider of type %s to this query, type %s required', get_class($dataProvider), $class));
        }
        $this->dataProvider = $dataProvider;
        return $this;
    }

    /**
     * Does this query have a valid data provider set?
     * @return bool
     */
    public function hasDataProvider(): bool
    {
        return $this->dataProvider instanceof DataProviderInterface;
    }

    /**
     * Return data provider
     * @return DataProviderInterface
     * @throws QueryException
     */
    public function getDataProvider(): DataProviderInterface
    {
        if (!$this->hasDataProvider()) {
            throw new QueryException('No data provider set');
        }
        return $this->dataProvider;
    }

    /**
     * Set options for this query
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Return options for this query
     * @return $this
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Does this query have a valid response set?
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->response instanceof CacheableResponse;
    }

    /**
     * Return response
     * @return CacheableResponse|null
     */
    public function getResponse(): ?CacheableResponse
    {
        return $this->response;
    }

    /**
     * Whether the response was returned from the cache?
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->getResponse()->isHit();
    }

    /**
     * Has the response run and retrieved data?
     * @return bool
     */
    public function hasResponseRun(): bool
    {
        if ($this->hasResponse()) {
            return (!empty($this->response->getInfo('http_code')));
        }
        return false;
    }

    /**
     * Clear response and mark to re-run query next time data is accessed
     * @return $this
     */
    public function clearResponse(): self
    {
        $this->response = null;
        return $this;
    }

    /**
     * Set root property path to retrieve data for this query
     * @param string $path
     * @return $this
     */
    public function setRootPropertyPath(string $path): self
    {
        $this->rootPropertyPath = $path;
        return $this;
    }

    /**
     * Whether the query has a root property path set
     * @return bool
     */
    public function hasRootPropertyPath(): bool
    {
        return !empty($this->rootPropertyPath);
    }

    /**
     * Return root property path for this query, e.g. [data]
     * @return ?string
     */
    public function getRootPropertyPath(): ?string
    {
        return $this->rootPropertyPath;
    }

    /**
     * Set whether it's safe to run this query concurrently with other queries
     * @param bool $concurrent
     * @return $this
     */
    public function concurrent(bool $concurrent = true): self
    {
        $this->concurrent = $concurrent;
        return $this;
    }

    /**
     * Return whether its safe to run this query concurrently
     * @return bool
     */
    public function isConcurrent(): bool
    {
        return $this->concurrent;
    }

    /**
     * Set string to separate array values in parameters
     * @param string $multipleValuesSeparator
     * @return $this
     */
    public function setMultipleValuesSeparator(string $multipleValuesSeparator): self
    {
        $this->multipleValuesSeparator = $multipleValuesSeparator;
        return $this;
    }

    /**
     * Return string to separate array values in parameters
     * @return string
     */
    public function getMultipleValuesSeparator(): string
    {
        return $this->multipleValuesSeparator;
    }

    /**
     * Set whether pagination data is set from headers data
     * @param bool $paginationDataFromHeaders
     */
    public function setPaginationDataFromHeaders(bool $paginationDataFromHeaders = true): self
    {
        $this->paginationDataFromHeaders = $paginationDataFromHeaders;
        return $this;
    }

    /**
     * Is pagination data set from headers?
     * @return bool
     */
    public function isPaginationDataFromHeaders(): bool
    {
        return $this->paginationDataFromHeaders;
    }

    /**
     * Is this a cacheable request?
     * @return ?bool
     */
    public function isCacheableRequest(): ?bool
    {
        return $this->cacheableRequest;
    }

    /**
     * Cache this query
     * @param int|null $lifetime
     * @return $this
     */
    public function cache(?int $lifetime = null): self
    {
        $this->cacheableRequest = true;
        if ($lifetime !== null) {
            $this->cacheLifetime = $lifetime;
        }
        return $this;
    }

    /**
     * Do not cache this query
     * @return $this
     */
    public function doNotCache(): self
    {
        $this->cacheableRequest = false;
        return $this;
    }

    /**
     * Whether a cache lifetime is setup
     * @return bool
     */
    public function hasCacheLifetime(): bool
    {
        return null !== $this->cacheLifetime;
    }

    /**
     * Return cache lifetime for this query
     * @return int|null
     */
    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
    }

    /**
     * Add a single cache tag
     * @param string $tag
     * @return $this
     */
    public function cacheTag(string $tag): self
    {
        $this->cacheTags[] = $tag;
        return $this;
    }

    /**
     * Add multiple cache tags to this query
     *
     * To remove tags do not pass any arguments and tags will be reset to an empty array
     *
     * @param array $tags
     * @throws CacheException
     * @throws QueryException
     */
    public function cacheTags(array $tags = []): self
    {
        foreach ($tags as $tag) {
            $this->cacheTag($tag);
        }
        return $this;
    }

    /**
     * Add a single cache tag with the 'new' prefix, this denotes the cached content is only new content
     * @param string $tag
     * @return $this
     */
    public function cacheTagNew(string $tag): self
    {
        return $this->cacheTag(self::TAG_NEW . $tag);
    }

    /**
     * Add multiple cache tags with the 'new' prefix, denoting this is new content
     * @param array $tags
     * @return $this
     */
    public function cacheTagsNew(array $tags): self
    {
        foreach ($tags as $tag) {
            $this->cacheTag(self::TAG_NEW . $tag);
        }
        return $this;
    }

    /**
     * Add the global cache tag, this denotes cached content is used across the whole website/app
     * @return $this
     */
    public function cacheTagGlobal(): self
    {
        return $this->cacheTag(self::TAG_GLOBAL);
    }

    /**
     * Whether any cache tags are set for this query
     * @return bool
     */
    public function hasCacheTags(): bool
    {
        return !empty($this->cacheTags);
    }

    /**
     * Return cache tags currently set on the query
     * @return array
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * Set whether this request is a sub-request
     *
     * This suppresses HTTP exceptions for sub-requests
     *
     * @param bool $subRequest
     * @return $this Fluent interface
     */
    public function setSubRequest(bool $subRequest = true): self
    {
        $this->subRequest = $subRequest;
        return $this;
    }

    /**
     * Whether this request is a sub-request
     * @return bool
     */
    public function isSubRequest(): bool
    {
        return $this->subRequest;
    }

    /**
     * Set the URI for this query
     * @param string $uri
     * @return $this Fluent interface
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Return the URI for this query
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set array of parameters to apply to this query
     * @param array $params
     * @return $this Fluent interface
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Add one parameter to apply to this query
     * @param string $key
     * @param mixed $value
     * @return $this Fluent interface
     */
    public function addParam(string $key, $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Return array of parameters to apply to this query
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Whether the query has any parameters set
     * @return bool
     */
    public function hasParams(): bool
    {
        return (!empty($this->params));
    }

    /**
     * Set array of fields to return for this query
     * @param array $fields
     * @return $this Fluent interface
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Whether any fields are set for this query
     * @return bool
     */
    public function hasFields(): bool
    {
        return (!empty($this->fields));
    }

    /**
     * Return array of fields to return for this query
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Return name of the query parameter to set data fields to return
     * @return string
     */
    public function getFieldParameter(): string
    {
        return $this->fieldParameter;
    }

    /**
     * Set name of the query parameter to set data fields to return
     * @param string $fieldParameter
     * @return $this
     */
    public function setFieldParameter(string $fieldParameter): self
    {
        $this->fieldParameter = $fieldParameter;
        return $this;
    }

    /**
     * Return name of the query parameter to set number of results to return per page
     * @return string
     */
    public function getResultsPerPageParam(): string
    {
        return $this->resultsPerPageParam;
    }

    /**
     * Set name of the query parameter to set number of results to return per page
     * @param string $resultsPerPageParam
     * @return $this
     */
    public function setResultsPerPageParam(string $resultsPerPageParam): self
    {
        $this->resultsPerPageParam = $resultsPerPageParam;
        return $this;
    }

    /**
     * Return name of the query parameter to set page of results to return
     * @return string
     */
    public function getPageParam(): string
    {
        return $this->pageParam;
    }

    /**
     * Set name of the query parameter to set page of results to return
     * @param string $pageParam
     * @return $this
     */
    public function setPageParam(string $pageParam): self
    {
        $this->pageParam = $pageParam;
        return $this;
    }

    /**
     * Prepare query
     */
    abstract public function prepare();

    /**
     * Run query
     */
    abstract public function run();

    /**
     * Return data from query response
     * @param array Data to map to a collection
     * @return mixed
     */
    abstract public function get();

    /**
     * Return collection of data from a query response
     * @return Collection
     * @throws \Strata\Data\Exception\MapperException
     */
    abstract public function getCollection(): Collection;
}
