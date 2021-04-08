<?php

declare(strict_types=1);

namespace Strata\Data\Helper;

/**
 * Class to help validate union types for PHP 7.* in Strata package
 */
class UnionTypes
{
    /**
     * Validate union type string or int
     *
     * @param $data
     * @return bool
     */
    public static function stringOrInt($data): bool
    {
        return is_string($data) || is_int($data);
    }

    /**
     * Validate union type array or object
     *
     * @param $data
     * @return bool
     */
    public static function arrayOrObject($data): bool
    {
        return is_array($data) || is_object($data);
    }
}
