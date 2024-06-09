<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccessor;

interface MappingStrategyInterface
{
    public function setPropertyAccessor(PropertyAccessor $propertyAccessor);
    public function getPropertyAccessor(): PropertyAccessor;

    /**
     * Map array of data to an item (array or object)
     *
     * @param array $data
     * @param array|object $item
     * @return mixed
     */
    public function mapItem(array $data, array|object $item);
}
