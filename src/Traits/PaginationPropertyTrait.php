<?php

declare(strict_types=1);

namespace Strata\Data\Traits;

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
    public function setTotalResults(string|int $totalResults)
    {
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
    public function setResultsPerPage(string|int $resultsPerPage)
    {
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
    public function setCurrentPage(string|int $currentPage)
    {
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
