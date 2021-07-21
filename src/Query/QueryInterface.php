<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\DataProviderInterface;
use Strata\Data\Http\Response\CacheableResponse;

interface QueryInterface
{
    /**
     * Data provider class required for use with this query
     * @var string Class name
     */
    public function getRequiredDataProviderClass(): string;

    /**
     * Set data provider to use with this query
     * @param DataProviderInterface $dataProvider
     * @return $this
     */
    public function setDataProvider(DataProviderInterface $dataProvider): self;

    /**
     * Return data provider
     * @return DataProviderInterface
     */
    public function getDataProvider(): DataProviderInterface;

    /**
     * Does this query have a valid data provider set?
     * @return bool
     */
    public function hasDataProvider(): bool;

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
     * Prepare query
     */
    public function prepare();

    /**
     * Run query
     */
    public function run();

    /**
     * Return response for a query
     * @return CacheableResponse|null
     */
    public function getResponse(): ?CacheableResponse;

    /**
     * Has the response run and retrieved data?
     * @return bool
     */
    public function hasResponseRun(): bool;

    /**
     * Return data from query response
     * @param array Data to map to a collection
     * @return mixed
     */
    public function get();

    /**
     * Return collection of data from a query response
     * @return Collection
     * @throws \Strata\Data\Exception\MapperException
     */
    public function getCollection(): Collection;
}
