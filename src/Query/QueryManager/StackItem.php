<?php

declare(strict_types=1);

namespace Strata\Data\Query\QueryManager;

use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Query\Query;

/**
 * Simple class to manage a query added to the query manager, also provides a reference to the data provider & response
 */
class StackItem
{
    private Query $query;
    private string $dataProviderName;
    private CacheableResponse $response;

    public function __construct(Query $query, string $dataProviderName, CacheableResponse $response)
    {
        $this->setQuery($query);
        $this->setDataProviderName($dataProviderName);
        $this->setResponse($response);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query): void
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getDataProviderName(): string
    {
        return $this->dataProviderName;
    }

    /**
     * @param string $dataProviderName
     */
    public function setDataProviderName(string $dataProviderName): void
    {
        $this->dataProviderName = $dataProviderName;
    }

    /**
     * Has the response run and retrieved data?
     * @return bool
     */
    public function hasResponseRun(): bool
    {
        return (!empty($this->response->getInfo('http_code')));
    }

    /**
     * @return CacheableResponse
     */
    public function getResponse(): CacheableResponse
    {
        return $this->response;
    }

    /**
     * @param CacheableResponse $response
     */
    public function setResponse(CacheableResponse $response): void
    {
        $this->response = $response;
    }

}