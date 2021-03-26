<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

/**
 * Maps values in a data array to alternative values
 *
 * @package Strata\Data\Transform\Data
 */
class MapValues extends DataAbstract
{
    private string $propertyPath;
    private array $mapping;
    private array $mappingLookup;

    /**
     * MapItem constructor.
     * @param string $propertyPath Path to root item to map values for
     * @param array $mapping Array of old => new values or callback to calculate value with the parameters: $value, $data, $propertyPath
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
     * Set mapping values (array of old => new values)
     *
     * When mapping is performed, old values are compared case-insensitively with trailing and leading spaces trimmed.
     * You can pass a callback as the new value, this must be a function with three parameters ($value, $data, $propertyPath)
     *
     * @param array $mapping Array of old => new values
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;

        // Build normalized lookup
        $this->mappingLookup = [];
        foreach (array_keys($mapping) as $key) {
            $this->mappingLookup[$this->normalize($key)] = $key;
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
        return is_array($data) || is_object($data);
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
     * Return new value from mapping or callback
     *
     * @param $value
     * @param $data
     * @return mixed
     */
    private function lookup($value, $data)
    {
        $value = $this->normalize($value);
        $new = $this->mapping[$this->mappingLookup[$value]];

        if (is_callable($new)) {
            return $new($value, $data, $this->propertyPath);
        } else {
            return $new;
        }
    }
}
