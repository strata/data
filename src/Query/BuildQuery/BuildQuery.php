<?php

declare(strict_types=1);

namespace Strata\Data\Query\BuildQuery;

use Strata\Data\Http\Http;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Query\Query;
use Strata\Data\Query\QueryInterface;

/**
 * Class to help prepare HTTP requests
 */
class BuildQuery implements BuildQueryInterface
{
    private Http $dataProvider;

    /**
     * Constructor
     * @param Http $dataProvider Data provider to use to build this query
     */
    public function __construct(Http $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Return parameters (key => values) for use in an API query
     *
     * @param Query $query
     * @return array
     */
    public function getParameters(Query $query): array
    {
        $params = $query->getParams();
        if ($query->hasFields()) {
            $params[$query->getFieldParameter()] = $query->getFields();
        }
        // Convert arrays for use in query
        foreach ($params as $key => $values) {
            if (is_array($values)) {
                $params[$key] = implode($query->getMultipleValuesSeparator(), $values);
            }
        }
        return $params;
    }

    /**
     * Return a prepared request
     *
     * Request is not run since no data is accessed (Symfony HttpClient lazy runs requests when you access data)
     * If response is returned from cache then full response data is returned by this method
     *
     * @param Query $query
     * @return CacheableResponse
     */
    public function prepareRequest(QueryInterface $query): CacheableResponse
    {
        // Build query
        if ($query->isSubRequest()) {
            $this->dataProvider->suppressErrors();
        }

        $options = [
            'query' => $this->getParameters($query)
        ];
        $response = $this->dataProvider->prepareRequest($query->getMethod(), $query->getUri(), $options, $query->isCacheableRequest());

        // Set caching rules for this query
        if ($response->isCacheable()) {
            $cacheItem = $response->getCacheItem();
            if ($query->hasCacheLifetime()) {
                $cacheItem->expiresAfter($query->getCacheLifetime());
            }
            if ($query->hasCacheTags() && $query->getDataProvider()->getCache()->isTaggable()) {
                $cacheItem->tag($query->getCacheTags());
            }
        }

        // Reset suppress errors to previous values
        if ($query->isSubRequest()) {
            $this->dataProvider->resetSuppressErrors();
        }

        return $response;
    }
}
