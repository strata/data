<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;
use Strata\Data\Helper\UnionTypes;
use Strata\Data\Transform\Data\CallableData;
use Strata\Data\Transform\PropertyAccessorInterface;
use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Transform\TransformerChain;
use Strata\Data\Transform\TransformInterface;
use Strata\Data\Transform\Value\BaseValue;
use Strata\Data\Transform\Value\CallableValue;
use Strata\Data\Transform\Value\MapValueInterface;

/**
 * Class to manage strategy of mapping data to an item
 */
class MappingStrategy implements MappingStrategyInterface, PropertyAccessorInterface
{
    use PropertyAccessorTrait;
    use TransformerTrait;

    private array $propertyPaths;

    /**
     * Set fields to map from data to your new array/object
     *
     * @param array $propertyPaths Array of new property path => source data property path/s or callback to return value (with arguments: $data, $destinationPropertyPath)
     * @param array $transformers Array of transformers to apply to mapped data
     */
    public function __construct(array $propertyPaths, array $transformers = [])
    {
        $this->setPropertyPaths($propertyPaths);
        $this->setTransformers($transformers);
    }

    /**
     * Set field property paths to map data from
     *
     * Array of new => old property paths
     *
     * New property paths to map data to
     * Old property paths to map data from, array or callback
     *
     * If old property path is an array, mapping checks each location for a value (taking first found)
     * If old property path is a callback, this is called to return value. Callback is a function that can accept the
     * following arguments: array $data, string $destinationPropertyPath
     *
     * @param array $propertyPaths Array of new property path => source data property path/s or callback to return value
     */
    public function setPropertyPaths(array $propertyPaths)
    {
        $this->propertyPaths = $propertyPaths;
    }

    /**
     * @return array
     */
    public function getPropertyPaths(): array
    {
        return $this->propertyPaths;
    }

    /**
     * Map array of data to an item (array or object)
     *
     * @param array $data Source data to map data from
     * @param array|object $item Destination item to map data to
     * @return mixed
     */
    public function mapItem(array $data, $item)
    {
        UnionTypes::assert('$item', $item, 'array', 'object');
        $propertyAccessor = $this->getPropertyAccessor();

        // Loop through property paths to map to new item (destination => source)
        foreach ($this->getPropertyPaths() as $destination => $source) {
            // Source is a callable function/method
            if ($source instanceof CallableValue || $source instanceof CallableData) {
                if ($source->getCallable() instanceof PropertyAccessorInterface) {
                    $source->getCallable()->setPropertyAccessor($this->getPropertyAccessor());
                }

                // This runs the _invoke() method of the mapper
                $propertyAccessor->setValue($item, $destination, $source($data));
                continue;
            }

            if ($source instanceof TransformInterface) {
                if ($source instanceof PropertyAccessorInterface) {
                    $source->setPropertyAccessor($this->getPropertyAccessor());
                }
                $propertyAccessor->setValue($item, $destination, $source->transform($data));
                continue;
            }

            // Source is a MapValue object
            if ($source instanceof MapValueInterface) {
                /** @var MapValueInterface $source */
                $source->setPropertyAccessor($propertyAccessor);
                if ($source->isReadable($data)) {
                    $propertyAccessor->setValue($item, $destination, $source->getValue($data));
                } else {
                    // Set null value if not found in source data
                    $propertyAccessor->setValue($item, $destination, null);
                }
                continue;
            }

            // Invalid source type
            if (!UnionTypes::is($source, 'string', 'array')) {
                $type = gettype($source);
                if ($type === 'object') {
                    $type = get_class($source);
                }
                throw new MapperException(sprintf('Source for destination "%s" not a valid type. Must be a string, array, CallableValue, CallableData, or MapValueInterface object. %s passed.', $destination, $type));
            }

            // Default functionality maps value as is
            $transformer = new BaseValue($source);
            $transformer->setPropertyAccessor($propertyAccessor);
            if ($transformer->isReadable($data)) {
                $propertyAccessor->setValue($item, $destination, $transformer->getValue($data));
            } else {
                // Set null value if not found in source data
                $propertyAccessor->setValue($item, $destination, null);
            }
            continue;
        }

        // Transform data
        if ($this->getTransformerChain() instanceof TransformerChain) {
            $item = $this->getTransformerChain()->transform($item);
        }

        return $item;
    }
}
