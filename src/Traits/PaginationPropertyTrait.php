<?php

declare(strict_types=1);

namespace Strata\Data\Traits;

use Strata\Data\Helper\UnionTypes;
use Strata\Data\Query\QueryInterface;

/**
 * Trait to help set pagination properties & build pagination
 */
trait PaginationPropertyTrait
{
    private $totalResults;
    private $resultsPerPage;
    private $currentPage = 1;

    /**
     * Set total results
     * @param string|int $totalResults Property path to retrieve data from, or actual value
     * @return $this Fluent interface
     */
    public function setTotalResults($totalResults)
    {
        UnionTypes::assert('$totalResults', $totalResults, 'string', 'int');
        $this->totalResults = $totalResults;
        return $this;
    }

    /**
     * Return total results
     * @return string|int Property path to retrieve data from (string), or actual value (int)
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Set results per page
     * @param string|int $resultsPerPage Property path to retrieve data from, or actual value
     * @return $this Fluent interface
     */
    public function setResultsPerPage($resultsPerPage)
    {
        UnionTypes::assert('$resultsPerPage', $resultsPerPage, 'string', 'int');
        $this->resultsPerPage = $resultsPerPage;
        return $this;
    }

    /**
     * Return results per page
     * @return string|int Property path to retrieve data from (string), or actual value (int)
     */
    public function getResultsPerPage()
    {
        return $this->resultsPerPage;
    }

    /**
     * Set current page
     * @param string|int $currentPage Property path to retrieve data from, or actual value
     * @return $this Fluent interface
     */
    public function setCurrentPage($currentPage)
    {
        UnionTypes::assert('currentPage', $currentPage, 'string', 'int');
        $this->currentPage = $currentPage;
        return $this;
    }

    /**
     * Return current page
     * @return string|int Property path to retrieve data from (string), or actual value (int)
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
}
