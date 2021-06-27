<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Rest;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Transform\PropertyAccessorTrait;

/**
 * Class to help manage running queries
 *
 * Use a child class for different types of APIs to help automate things like sending params & collections
 * Default behaviour is for a REST API
 *
 * @package Strata\Data\Query
 */
class QueryManager
{
    use PropertyAccessorTrait;

    protected DataProviderInterface $dataProvider;
    private array $queries = [];
    private array $responses = [];

    /**
     * Name of the query parameter to set data fields to return
     * @var string
     */
    private string $fieldParameter = 'fields';

    /**
     * Name of the query parameter to set number of results to return per page
     * @var string
     */
    private string $resultsPerPageParam = 'limit';

    /**
     * Name of the query parameter to set page of results to return
     * @var string
     */
    private string $pageParam = 'page';

    /**
     * Total results data property path
     * @var string
     */
    private string $totalResultsPropertyPath = '[total]';

    /**
     * Current page data property path
     * @var string
     */
    private string $currentPagePropertyPath = '[page]';

    /**
     * Results per page data property path
     * @var string
     */
    private string $resultsPerPagePropertyPath = '[page]';

    /**
     * Separator to separate array values in parameters
     *
     * E.g. ?param_field=one,two,three
     * @var string
     */
    private string $multipleValuesSeparator = ',';

    /**
     * Constructor, automatically sets the data provider
     *
     * @param string|null $baseUri
     * @param array $options
     */
    public function __construct(?string $baseUri = null, array $options = [])
    {
        $this->dataProvider = new Rest($baseUri, $options);
    }

    /**
     * Set the data provider used to run API queries
     *
     * @param DataProviderInterface $dataProvider
     */
    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Return the data provider used to run API queries
     *
     * @return DataProviderInterface
     */
    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    /**
     * Add a query (does not run the query, this happens on data access)
     *
     * @param Query $query
     */
    public function add(Query $query)
    {
        if (array_key_exists($query->getName(), $this->queries)) {
            throw new QueryException(sprintf('Cannot add query since query with same name "%s" already exists', $query->getName()));
        }
        $this->queries[$query->getName()] = $query;
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
            $params[$this->fieldParameter] = $query->getFields();
        }
        foreach ($params as $key => $values) {
            if (is_array($values)) {
                $params[$key] = implode($this->multipleValuesSeparator, $values);
            }
        }
        return $params;
    }

    /**
     * Run queries on data access
     *
     * Only runs a query once, though you can force a query to be re-run via $query->setRun(false)
     */
    protected function runQueries()
    {
        $concurrent = [];

        /** @var Query $query */
        foreach ($this->queries as $query) {
            if ($query->hasRun()) {
                continue;
            }

            // Build query
            if ($query->isSubRequest()) {
                $this->getDataProvider()->suppressErrors();
            } else {
                $this->getDataProvider()->suppressErrors(false);
            }
            $options = [
                'query' => $this->getParameters($query)
            ];
            $concurrent[$query->getName()] = $this->getDataProvider()->prepareRequest('GET', $query->getUri(), $options);
        }

        // Run multiple queries concurrently for performance
        foreach ($concurrent as $name => $response) {
            $this->responses[$name] = $this->getDataProvider()->runRequest($response);

            // Mark query as run
            $this->getQuery($name)->setRun(true);
        }
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getQuery(string $name): ?Query
    {
        if (array_key_exists($name, $this->queries)) {
            return $this->queries[$name];
        }
        return null;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getResponse(string $name): ?CacheableResponse
    {
        if (array_key_exists($name, $this->responses)) {
            return $this->responses[$name];
        }
        return null;
    }

    /**
     * Return a data item
     *
     * Default functionality is to return decoded data as an array
     *
     * @param string $name
     * @throws QueryException
     */
    public function getItem(string $name): array
    {
        // Run queries
        $this->runQueries();
        if (!array_key_exists($name, $this->responses)) {
            throw new QueryException(sprintf('Cannot find query response for query name "%s"', $name));
        }

        // Return decoded data
        $response = $this->responses[$name];
        return $this->getDataProvider()->decode($response);
    }

    /**
     * Return a collection of data items with pagination
     *
     * Default functionality is to return decoded data as an array with pagination
     *
     * @param string $name
     * @param ?int $page
     * @param ?int $resultsPerPage
     */
    public function getCollection(string $name, ?int $page = null, ?int $resultsPerPage = null): Collection
    {
        // Allow queries to be re-run with collection parameters
        if ($page !== null) {
            $query = $this->getQuery($name);
            $query->addParam($this->pageParam, $page);
            $query->setRun(false);
        }
        if ($resultsPerPage !== null) {
            $query = $this->getQuery($name);
            $query->addParam($this->resultsPerPageParam, $resultsPerPage);
            $query->setRun(false);
        }

        // Run queries
        $this->runQueries();
        if (!array_key_exists($name, $this->responses)) {
            throw new QueryException(sprintf('Cannot find query response for query name "%s"', $name));
        }

        // Return collections object with decoded data & pagination
        /** @var CacheableResponse $response */
        $response = $this->responses[$name];
        $data = $this->getDataProvider()->decode($response);

        $mapper = new MapCollection(new WildcardMappingStrategy());
        $mapper->totalResults($this->totalResultsPropertyPath)
               ->resultsPerPage($this->resultsPerPagePropertyPath)
               ->currentPage($this->currentPagePropertyPath);

        return $mapper->map($data);
    }

}