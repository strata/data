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
        $data = $this->getRootData($data, $rootProperty);
        return $this->buildItemFromData($data);
    }
}
