<?php

declare(strict_types=1);

namespace Strata\Data\Query;


use Strata\Data\Collection;
use Strata\Data\Http\Rest;

interface QueryInterface
{
    /**
     * Data provider class required for use with this query
     * @var string Class name
     */
    public function getRequiredDataProviderClass(): string;

    /**
     * Set root property path to retrieve data for this query
     * @param string $path
     */
    public function setRootPropertyPath(string $path);

    /**
     * Return root property path for this query, e.g. [data]
     * @return ?string
     */
    public function getRootPropertyPath(): ?string;

    /**
     * Return string to separate array values in parameters
     * @return string
     */
    public function getMultipleValuesSeparator(): string;

    /**
     * Enable cache for this query
     * @param int|null $lifetime
     * @return $this
     */
    public function enableCache(?int $lifetime = null): self;

    /**
     * Disable cache for this query
     * @return $this
     */
    public function disableCache(): self;

    /**
     * Is cache enabled for this query?
     * @return bool
     */
    public function isCacheEnabled(): bool;

    /**
     * Return cache lifetime for this query
     * @return int|null
     */
    public function getCacheLifetime(): ?int;

    /**
     * Whether this query has a name
     * @return bool
     */
    public function hasName(): bool;

    /**
     * Set query name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set whether this request is a sub-request
     *
     * This suppresses HTTP exceptions for sub-requests
     *
     * @param bool $subRequest
     * @return self Fluent interface
     */
    public function setSubRequest(bool $subRequest): self;

    /**
     * Whether this request is a sub-request
     * @return bool
     */
    public function isSubRequest(): bool;

    /**
     * Return the URI for this query
     * @return string
     */
    public function getUri(): string;

    /**
     * Return array of parameters to apply to this query
     * @return array
     */
    public function getParams(): array;

    /**
     * Whether the query has any parameters set
     * @return bool
     */
    public function hasParams(): bool;

    /**
     * Whether any fields are set for this query
     * @return bool
     */
    public function hasFields(): bool;

    /**
     * Return array of fields to return for this query
     * @return array
     */
    public function getFields(): array;

    /**
     * Set whether pagination data is set from headers data
     * @param bool $paginationDataFromHeaders
     * @return $this
     */
    public function setPaginationDataFromHeaders(bool $paginationDataFromHeaders): self;

    /**
     * Is pagination data set from headers?
     * @return bool
     */
    public function isPaginationDataFromHeaders(): bool;

    /**
     * Map data and return aray/object
     * @param array Data to map to a collection
     * @return mixed
     */
    public function mapItem(array $data);

    /**
     * Map data and return a collection object for this query
     * @param array $data Data to map to a collection
     * @param array|object|null $paginationData Data to retrieve pagination information from
     * @return Collection
     * @throws \Strata\Data\Exception\MapperException
     */
    public function mapCollection(array $data, $paginationData = null): Collection;

}