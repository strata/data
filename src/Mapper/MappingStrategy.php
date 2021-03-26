<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;
use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Transform\TransformerChain;
use Strata\Data\Transform\TransformInterface;

class MappingStrategy implements MappingStrategyInterface
{
    use PropertyAccessorTrait;

    private array $propertyPaths;
    private TransformerChain $transformerChain;

    /**
     * Set fields to map from data to your new array/object
     *
     * @param array $propertyPaths Array of new property path => source data property path/s or callback to return value (with arguments: $data, $destinationPropertyPath)
     */
    public function __construct(array $propertyPaths)
    {
        $this->setPropertyPaths($propertyPaths);
        $this->transformerChain = new TransformerChain();
    }

    /**
     * Set field property paths
     *
     * @param array $propertyPaths Array of new property path => source data property path/s or callback to return value (with arguments: $data, $destinationPropertyPath)
     */
    public function setPropertyPaths(array $propertyPaths)
    {
        $this->propertyPaths = $propertyPaths;
    }

    public function addTransformer(TransformInterface $transformer)
    {
        $this->transformerChain->addTransformer($transformer);
    }

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
     * @param string|null $className Optional classname to map to
     * @return array|object Array or object
     * @throws MapperException
     */
    public function mapItem(array $data, string $className = null)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        // Instantiate object or array
        if ($className === null) {
            $item = [];
        } else {
            $item = new $className();
        }

        // Loop through property paths to map to new item (destination => source)
        foreach ($this->propertyPaths as $destination => $source) {
            if (is_callable($source)) {
                $propertyAccessor->setValue($item, $destination, $source($data, $destination));
                continue;
            }
            if (is_array($source)) {
                foreach ($source as $sourceValue) {
                    if ($propertyAccessor->isReadable($data, $sourceValue)) {
                        $source = $sourceValue;
                        break;
                    }
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
        $item = $this->transformerChain->transform($item);

        return $item;
    }

    /**
     * @todo
     */
    public function mapCollection()
    {
    }

    /**
     * @todo
     */
    public function map()
    {
    }
}
