<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Helper\UnionTypes;

class WildcardMappingStrategy extends MappingStrategy
{
    private array $mapping = [];
    private array $ignore = [];

    /**
     * Set fields to map from data to your new array/object
     *
     * @param array $transformers
     */
    public function __construct(array $transformers = [])
    {
        $this->setTransformers($transformers);
    }

    /**
     * Set specific fields to map
     *
     * @param string $field
     * @param array $mapping
     */
    public function addMapping(string $field, array $mapping)
    {
        if (!isset($this->mapping[$field])) {
            $this->mapping[$field] = [];
        }
        foreach ($mapping as $key => $value) {
            $this->mapping[$field][$key] = $value;
        }

        //array_push($this->mapping[$field], $mapping);
    }

    /**
     * Does a field have specific mapping setup?
     * @param string $field
     * @return bool
     */
    public function isRootElementInMapping(string $field): bool
    {
        return array_key_exists($field, $this->mapping);
    }

    /**
     * Return mapping for a specific field
     * @param string $field
     * @return array
     */
    public function getMappingByRootElement(string $field): array
    {
        return $this->mapping[$field];
    }

    /**
     * Set fields to ignore when mapping data
     *
     * @param array|string $field
     */
    public function addIgnore($field)
    {
        UnionTypes::assert('$field', $field, 'array', 'string');

        if (is_array($field)) {
            foreach ($field as $item) {
                $this->ignore[] = strtolower($item);
            }
        } else {
            $this->ignore[] = strtolower($field);
        }
    }

    /**
     * Whether a field is in the ignore list
     *
     * @param string $field
     * @return bool
     */
    public function isRootElementInIgnore(string $field): bool
    {
        $field = strtolower($field);
        return in_array($field, $this->ignore);
    }

    /**
     * Map array of data to an item (array or object)
     *
     * @param array $data
     * @param array|object $item
     * @return mixed
     */
    public function mapItem(array $data, $item)
    {
        UnionTypes::assert('$item', $item, 'array', 'object');

        // Loop through all root data to build property path mapping
        $propertyPaths = [];
        foreach ($data as $field => $value) {
            // Ignore these fields
            if ($this->isRootElementInIgnore($field)) {
                continue;
            }

            // Map these fields
            if ($this->isRootElementInMapping($field)) {
                $propertyPaths = array_merge($propertyPaths, $this->getMappingByRootElement($field));
            }

            // And anything else left, just map as-is
            $arrayPropertyPath = sprintf('[%s]', $field);
            switch (gettype($item)) {
                case 'array':
                    $propertyPaths[$arrayPropertyPath] = $arrayPropertyPath;
                    break;
                case 'object':
                    $propertyPaths[$field] = $arrayPropertyPath;
                    break;
            }
        }
        $this->setPropertyPaths($propertyPaths);

        // Run this through the standard mapping strategy
        return parent::mapItem($data, $item);
    }
}
