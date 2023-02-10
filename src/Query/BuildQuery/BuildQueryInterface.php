<?php

declare(strict_types=1);

namespace Strata\Data\Query\BuildQuery;

use Strata\Data\DataProviderInterface;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Query\Query;

/**
 * Interface to prepare requests
 */
interface BuildQueryInterface
{
    /**
     * Return a prepared request
     *
     * Request is not run since no data is accessed (Symfony HttpClient lazy runs requests when you access data)
     * If response is returned from cache then full response data is returned by this method
     *
     * @param Query $query
     * @return CacheableResponse
     */
    public function prepareRequest(Query $query): CacheableResponse;
}
