<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;

interface MapperInterface
{
    /**
     * Map data to an item
     *
     * By default this returns an array, or pass a classname to return as an object
     *
     * @param array $data Data to map from
     * @param string|null $rootProperty Root property to map data from
     * @return array|object Mapped array or object
     * @throws MapperException
     */
    public function map(array $data, ?string $rootProperty = null);
}
