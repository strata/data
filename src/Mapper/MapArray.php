<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Transform\Value\Data;
use Strata\Data\Transform\Value\MapValueInterface;

class MapArray implements MapValueInterface
{
    use PropertyAccessorTrait;

    private string $propertyPath;
    private MapItem $mapItem;

    /**
     * MapItem constructor
     *
     * @param string $propertyPath Property path to read data from (this must be an array of data, otherwise it is not mapped)
     * @param array|MappingStrategyInterface $strategy Array of mapping property paths, or MappingStrategy object
     */
    public function __construct(string $propertyPath, $strategy)
    {
        $this->propertyPath = $propertyPath;
        $this->mapItem = new MapItem($strategy);
    }

    /**
     * Return property path to this value
     * @return string|array
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * Is the property path readable in the passed data?
     *
     * @param $objectOrArray Data to read property from
     * @return bool
     */
    public function isReadable($objectOrArray): bool
    {
        $propertyAccessor = $this->getPropertyAccessor();
        if ($propertyAccessor->isReadable($objectOrArray, $this->propertyPath)) {
            $data = $propertyAccessor->getValue($objectOrArray, $this->propertyPath);
            return is_array($data);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getValue($objectOrArray)
    {
        $arrayData = $this->getPropertyAccessor()->getValue($objectOrArray, $this->propertyPath);
        $mappedData = [];

        foreach ($arrayData as $item) {
            $mappedData[] = $this->mapItem->map($item);
        }

        return $mappedData;
    }
}
