<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Cache\CacheLifetime;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\CacheableResponse;

/**
 * Class to help craft a REST API query
 *
 * Any configurable fields for the query should be in this class
 */
class Query
{
    private ?CacheableResponse $response = null;
    private bool $enableCache = false;
    private ?int $cacheLifetime = null;
    private ?string $name = null;
    private bool $subRequest = false;
    private string $uri;
    private array $params = [];
    private array $fields = [];

    /**
     * Separator to separate array values in parameters
     *
     * E.g. ?param_field=one,two,three
     * @var string
     */
    public string $multipleValuesSeparator = ',';

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
    public bool $paginationDataFromHeaders = false;

    /**
     * Total results data property path
     * @var string
     */
    public string $totalResultsPropertyPath = '[total]';

    /**
     * Current page data property path
     * @var string
     */
    public string $currentPagePropertyPath = '[page]';

    /**
     * Results per page data property path
     * @var string
     */
    public string $resultsPerPagePropertyPath = '[page]';

    public function enableCache(?int $lifetime = null): Query
    {
        $this->enableCache = true;
        if ($lifetime !== null) {
            $this->cacheLifetime = $lifetime;
        }
    }

    public function disableCache(): Query
    {
        $this->enableCache = false;
    }

    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
    }

    /**
     * Prepare the request and populate the response object (does not run the request)
     * @param DataProviderInterface $dataProvider
     * @return CacheableResponse
     * @throws QueryException
     */
    public function prepareRequest(DataProviderInterface $dataProvider): CacheableResponse
    {
        $buildQuery = new BuildQuery($dataProvider);
        $this->setResponse($buildQuery->prepareRequest($this));
        return $this->getResponse();
    }

    /**
     * Set response object
     * @return Query Fluent interface
     */
    public function setResponse(CacheableResponse $response): Query
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Clear the response and mark it to be re-run
     * @return Query Fluent interface
     */
    public function clearResponse(): Query
    {
        $this->response = null;
        return $this;
    }

    /**
     * Whether the query has run and has a response
     * @return bool
     */
    public function hasResponse(): bool
    {
        return ($this->response instanceof CacheableResponse);
    }

    /**
     * Whether the response has actually run
     *
     * Checks for the existence of the HTTP status code, only populated once the request has run
     */
    public function hasResponseRun(): bool
    {
        if (!$this->hasResponse()) {
            return false;
        }
        return (!empty($this->response->getInfo('http_code')));
    }

    /**
     * Return response object
     * @return CacheableResponse|null
     */
    public function getResponse(): ?CacheableResponse
    {
        return $this->response;
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

}