<?php
declare(strict_types=1);

namespace Strata\Data\Filter;

use Strata\Data\Exception\FilterException;

/**
 * JSON filter
 *
 * Returns data as an associative array to ensure the same data format for all types of JSON data
 * @package Strata\Data\Filter
 */
class Json implements FilterInterface
{

    public function filter(string $data): array
    {
        $data = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        throw new FilterException('Error parsing JSON response body: ' . json_last_error_msg());
    }

}
