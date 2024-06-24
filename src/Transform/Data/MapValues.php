<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

use Strata\Data\Transform\NotTransformedInterface;
use Strata\Data\Transform\NotTransformedTrait;

/**
 * Maps values in a data array to alternative values
 *
 * @package Strata\Data\Transform\Data
 */
class MapValues extends DataAbstract implements NotTransformedInterface
{
    use NotTransformedTrait;

    private string $propertyPath;
    private array $newValues;
    private array $mappingLookup;

    /**
     * MapItem constructor.
     * @param string $propertyPath Path to root item to map values for
     * @param array $mapping Array of new value => old value/s
     */
    public function __construct(string $propertyPath, array $mapping)
    {
        $this->setPropertyPath($propertyPath);
        $this->setMapping($mapping);
    }

    /**
     * Set property path to map values for
     *
     * @param string $propertyPath
     */
    public function setPropertyPath(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Set mapping values (array of new value => old value/s)
     *
     * When mapping is performed, old values are compared case-insensitively with trailing and leading spaces trimmed.
     *
     * @param array $mapping Array of new value => old value/s
     */
    public function setMapping(array $mapping)
    {
        // Build normalized lookup for old value => new key
        $this->mappingLookup = [];
        foreach ($mapping as $newValue => $oldValues) {
            $this->newValues[] = $newValue;
            if (is_string($oldValues)) {
                $this->mappingLookup[$this->normalize($oldValues)] = $newValue;
                continue;
            }
            if (is_iterable($oldValues)) {
                foreach ($oldValues as $oldValue) {
                    if (is_string($oldValue)) {
                        $this->mappingLookup[$this->normalize($oldValue)] = $newValue;
                    }
                }
            }
        }
    }

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool
    {
        return (is_array($data) || is_object($data));
    }

    /**
     * Transform data into something else
     *
     * @param $data
     * @return mixed Transformed data
     */
    public function transform($data)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        if (!$propertyAccessor->isReadable($data, $this->propertyPath)) {
            return $data;
        }
        $old = $propertyAccessor->getValue($data, $this->propertyPath);

        // Single value
        if (is_string($old)) {
            if (!$this->exists($old)) {
                if (!in_array($old, $this->newValues)) {
                    $this->addNotTransformed($old);
                }
                return $data;
            }
            $new = $this->lookup($old, $data);
            $propertyAccessor->setValue($data, $this->propertyPath, $new);
            return $data;
        }

        // Multiple values
        if (is_array($old)) {
            foreach ($old as $key => $value) {
                if (!$this->exists($value)) {
                    if (!in_array($value, $this->newValues)) {
                        $this->addNotTransformed($value);
                    }
                    continue;
                }
                $new = $this->lookup($value, $data);
                $path = $this->propertyPath . sprintf('[%s]', $key);
                $propertyAccessor->setValue($data, $path, $new);
            }
        }

        return $data;
    }

    /**
     * Normalize mapping value to help comparisons
     *
     * @param $value
     * @return string
     */
    private function normalize($value)
    {
        $value = strtolower($value);
        $value = trim($value);
        return $value;
    }

    /**
     * Does this value exist in the mapping array?
     *
     * If value is not a string, returns false
     *
     * @param $value
     * @return bool
     */
    private function exists($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $value = $this->normalize($value);
        return isset($this->mappingLookup[$value]);
    }

    /**
     * Return new value from mapping
     *
     * @param $value
     * @param $data
     * @return mixed
     */
    private function lookup($value, $data)
    {
        $value = $this->normalize($value);
        return $this->mappingLookup[$value];
    }
}
