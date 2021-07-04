<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Rest;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Traits\PaginationPropertyTrait;

/**
 * Class to help craft a REST API query
 *
 * Any configurable fields for the query should be in this class
 */
class Query implements QueryInterface
{
    use PaginationPropertyTrait;

    private bool $enableCache = false;
    private ?int $cacheLifetime = null;
    private ?string $name = null;
    private bool $subRequest = false;
    private string $uri;
    private array $params = [];
    private array $fields = [];
    protected ?string $rootPropertyPath = null;
    protected string $multipleValuesSeparator = ',';

    /**
     * Name of the query parameter to set data fields to return
     * @var string
     */
    public string $fieldParameter = 'fields';

    /**
     * Name of the query parameter to set number of results to return per page
     * @var string
     */
    public string $resultsPerPageParam = 'limit';

    /**
     * Name of the query parameter to set page of results to return
     * @var string
     */
    public string $pageParam = 'page';

    /**
     * Is the data source for pagination data stored in HTTP headers?
     * @var bool
     */
    protected bool $paginationDataFromHeaders = false;

    /**
     * Total results data property path
     * @var string
     */
    protected string $totalResultsPropertyPath = '[total]';

    /**
     * Current page data property path
     * @var string
     */
    protected string $currentPagePropertyPath = '[page]';

    /**
     * Results per page data property path
     * @var string
     */
    protected string $resultsPerPagePropertyPath = '[page]';

    /**
     * Constructor
     * @param string|null $name Query name
     */
    public function __construct(?string $name = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * Data provider class required for use with this query
     * @var string
     */
    public function getRequiredDataProviderClass(): string
    {
        return Rest::class;
    }


    /**
     * Set root property path to retrieve data for this query
     * @param string $path
     */
    public function setRootPropertyPath(string $path)
    {
        $this->rootPropertyPath = $path;
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
    public function setPaginationDataFromHeaders(bool $paginationDataFromHeaders): self
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
     * Enable cache for this query
     * @param int|null $lifetime
     * @return $this
     */
    public function enableCache(?int $lifetime = null): Query
    {
        $this->enableCache = true;
        if ($lifetime !== null) {
            $this->cacheLifetime = $lifetime;
        }
    }

    /**
     * Disable cache for this query
     * @return $this
     */
    public function disableCache(): Query
    {
        $this->enableCache = false;
    }

    /**
     * Is cache enabled for this query?
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
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
     * Return query name
     *
     * @param string|null $name
     * @return Query Fluent interface
     */
    public function setName(?string $name): Query
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Whether this query has a name
     * @return bool
     */
    public function hasName(): bool
    {
        return (!empty($this->name));
    }

    /**
     * Set query name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set whether this request is a sub-request
     *
     * This suppresses HTTP exceptions for sub-requests
     *
     * @param bool $subRequest
     * @return Query Fluent interface
     */
    public function setSubRequest(bool $subRequest): Query
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
     * @return Query Fluent interface
     */
    public function setUri(string $uri): Query
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
     * @return Query Fluent interface
     */
    public function setParams(array $params): Query
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Add parameter
     *
     * @param string $key
     * @param mixed $value
     * @return Query Fluent interface
     */
    public function addParam(string $key, $value): Query
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
     * @return Query Fluent interface
     */
    public function setFields(array $fields): Query
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
     * Map data and return array/object
     * @param array Data to map to a collection
     * @return mixed
     */
    public function mapItem(array $data)
    {
        $mapper = new MapItem(new WildcardMappingStrategy());
        return $mapper->map($data, $this->getRootPropertyPath());
    }

    /**
     * Map data and return a collection object for this query
     * @param array $data Data to map to a collection
     * @param array|object|null $paginationData Data to retrieve pagination information from
     * @return Collection
     * @throws \Strata\Data\Exception\MapperException
     */
    public function mapCollection(array $data, $paginationData = null): Collection
    {
        $mapper = new MapCollection(new WildcardMappingStrategy());
        $mapper->setTotalResults($this->getTotalResults())
            ->setResultsPerPage($this->getResultsPerPage())
            ->setCurrentPage($this->getCurrentPage());

        if ($paginationData !== null) {
            $mapper->fromPaginationData($paginationData);
        }

        return $mapper->map($data, $this->getRootPropertyPath());
    }

}