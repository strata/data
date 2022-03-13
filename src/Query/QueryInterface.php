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
     * Set options for this query
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self;

    /**
     * Return options for this query
     * @return array
     */
    public function getOptions(): array;

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
     * Set whether it's safe to run this query concurrently with other queries
     * @param bool $concurrent
     * @return $this
     */
    public function concurrent(bool $concurrent = true): self;

    /**
     * Return whether it's safe to run this query concurrently
     * @return bool
     */
    public function isConcurrent(): bool;

    /**
     * Is this a cacheable request?
     * @return ?bool
     */
    public function isCacheableRequest(): ?bool;

    /**
     * Cache this query
     * @param int|null $lifetime
     * @return $this
     */
    public function cache(?int $lifetime = null): self;

    /**
     * Do not cache this query
     * @return $this
     */
    public function doNotCache(): self;

    /**
     * Add cache tags to this query
     * @param array $tags
     * @return $this
     */
    public function cacheTags(array $tags = []): self;

    public function cacheTag(string $tag): self;

    public function cacheTagNew(string $tag): self;

    public function cacheTagGlobal(): self;

    public function hasCacheTags(): bool;

    public function getCacheTags(): array;

    /**
     * Whether a cache lifetime is set up
     * @return bool
     */
    public function hasCacheLifetime(): bool;

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
