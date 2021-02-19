<?php
declare(strict_types=1);

namespace Strata\Data\Pagination;

use Strata\Data\Exception\PaginationException;
use Strata\Data\Model\Response;

/**
 * Class to auto-build pagination from data
 *
 * Usage:
 * $pagination = PaginationBuilder::create($response, 'X-WP-Total', $perPage, $page);
 *
 * @package Strata\Data\Pagination
 */
class PaginationBuilder
{

    /**
     * Generate pagination from an array or passed data
     *
     * @param array $data Array of data
     * @param string|int|null $totalResults If string array property, or if int the value
     * @param string|int|null $resultsPerPage If string array property, or if int the value
     * @param string|int|null $currentPage If string array property, or if int the value
     * @return Pagination
     * @throws PaginationException
     */
    public static function fromArray(array $data, $totalResults = null, $resultsPerPage = null, $currentPage = null): Pagination
    {
        $pagination = new Pagination();

        switch (gettype($totalResults)) {
            case 'int':
                $pagination->setTotalResults($totalResults);
                break;
            case 'string':
                if (isset($data[$totalResults])) {
                    $pagination->setTotalResults($data[$totalResults]);
                }
                break;
        }
        switch (gettype($resultsPerPage)) {
            case 'int':
                $pagination->setResultsPerPage($resultsPerPage);
                break;
            case 'string':
                if (isset($data[$resultsPerPage])) {
                    $pagination->setResultsPerPage($data[$resultsPerPage]);
                }

                break;
        }
        switch (gettype($currentPage)) {
            case 'int':
                $pagination->setPage($currentPage);
                break;
            case 'string':
                if (isset($data[$currentPage])) {
                    $pagination->setPage($data[$currentPage]);
                }
                break;
        }

        return $pagination;
    }

}
