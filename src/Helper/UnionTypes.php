<?php

declare(strict_types=1);

namespace Strata\Data\Helper;

/**
 * Class to help validate union types for PHP 7.* in Strata package
 */
class UnionTypes
{
    /**
     * Is a value is one of a number of types
     *
     * @param mixed $value Value to check
     * @param string ...$types One or many types to check (array, callable, bool, float, int, string, iterable, object, or classname)
     * @return bool
     */
    public static function is($value, string ...$types): bool
    {
        $valid = false;

        foreach ($types as $type) {
            switch ($type) {
                case 'array':
                    if (is_array($value)) {
                        $valid = true;
                    }
                    break;
                case 'callable':
                    if (is_callable($value)) {
                        $valid = true;
                    }
                    break;
                case 'bool':
                    if (is_bool($value)) {
                        $valid = true;
                    }
                    break;
                case 'float':
                    if (is_float($value)) {
                        $valid = true;
                    }
                    break;
                case 'int':
                    if (is_int($value)) {
                        $valid = true;
                    }
                    break;
                case 'string':
                    if (is_string($value)) {
                        $valid = true;
                    }
                    break;
                case 'iterable':
                    if (is_iterable($value)) {
                        $valid = true;
                    }
                    break;
                case 'object':
                    if (is_object($value)) {
                        $valid = true;
                    }
                    break;
                default:
                    if (class_exists($type) || interface_exists($type)) {
                        if ($value instanceof $type) {
                            $valid = true;
                        }
                        break;
                    }
                    throw new \InvalidArgumentException(sprintf('Invalid union type passed "%s"', $type));
            }
        }

        return $valid;
    }

    /**
     * Assert a value is one of a number of types
     *
     * @param string $propertyName Name of the property (used for exception messages)
     * @param mixed $value Value to check
     * @param string ...$types One or many types to check (array, callable, bool, float, int, string, iterable, object, or classname)
     * @throws \InvalidArgumentException on error
     */
    public static function assert(string $propertyName, $value, string ...$types)
    {
        if (!self::is($value, ...$types)) {
            throw new \InvalidArgumentException(sprintf('%s must be a %s, %s passed', $propertyName, implode(' or ', $types), gettype($value)));
        }
    }
}
