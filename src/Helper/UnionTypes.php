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
     * @param ?string $class Class name
     * @return bool
     */
    public static function arrayOrObject($data, ?string $class = null): bool
    {
        if (null !== $class) {
            return is_array($data) || ($data instanceof $class);
        }
        return is_array($data) || is_object($data);
    }

    /**
     * Validate union type string or object
     *
     * @param $data
     * @param ?string $class Class name
     * @return bool
     */
    public static function stringOrObject($data, ?string $class = null): bool
    {
        if (null !== $class) {
            return is_string($data) || ($data instanceof $class);
        }
        return is_string($data) || is_object($data);
    }

}
