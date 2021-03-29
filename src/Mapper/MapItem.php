<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;

class MapItem extends MapperAbstract implements MapperInterface
{
    /**
     * Map data to an item
     *
     * By default this returns an array, or pass a classname to return as an object
     *
     * Symfony's property accessor will attempt to set properties on an object via:
     * - public properties
     * - getters and setters
     * - magic __set() method
     *
     * @see https://symfony.com/doc/current/components/property_access.html#writing-to-objects
     * @param array $data Data to map from
     * @param string|null $rootProperty Root property to map data from
     * @return array|object Mapped array or object
     * @throws MapperException
     */
    public function map(array $data, ?string $rootProperty = null)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $strategy = $this->getStrategy();

        if (null !== $rootProperty) {
            if (!$propertyAccessor->isReadable($data, $rootProperty)) {
                throw new MapperException(sprintf('Root property path %s cannot be found in data', $rootProperty));
            }
            $data = $propertyAccessor->getValue($data, $rootProperty);
        }

        // Instantiate object or array
        if ($this->isMapToObject()) {
            $className = $this->getClassName();
            $item = new $className();
        } else {
            $item = [];
        }

        // Loop through property paths to map to new item (destination => source)
        foreach ($strategy->getPropertyPaths() as $destination => $source) {
            if (is_callable($source)) {
                $propertyAccessor->setValue($item, $destination, $source($data, $destination));
                continue;
            }
            if (is_array($source)) {
                $found = false;
                foreach ($source as $sourceValue) {
                    if ($propertyAccessor->isReadable($data, $sourceValue)) {
                        $source = $sourceValue;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $propertyAccessor->setValue($item, $destination, null);
                    continue;
                }
            }
            if (is_string($source)) {
                if (!$propertyAccessor->isReadable($data, $source)) {
                    $propertyAccessor->setValue($item, $destination, null);
                    continue;
                }
                $propertyAccessor->setValue($item, $destination, $propertyAccessor->getValue($data, $source));
                continue;
            }
            throw new MapperException(sprintf('Source for destination "%s" not a valid type, must be a string, array of strings or callback', $destination));
        }

        // Transform data in destination item
        $item = $strategy->getTransformerChain()->transform($item);

        return $item;
    }

}
