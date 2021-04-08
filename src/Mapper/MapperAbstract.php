<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;
use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Transform\Value\MapValueInterface;

abstract class MapperAbstract
{
    use PropertyAccessorTrait;

    private bool $mapToObject = false;
    private ?string $className = null;
    private MappingStrategy $strategy;

    /**
     * MapItem constructor
     *
     * @param array|MappingStrategy $strategy Array of mapping property paths, or MappingStrategy object
     */
    public function __construct($strategy)
    {
        if (is_array($strategy)) {
            $this->setStrategy(new MappingStrategy($strategy));
        }
        if ($strategy instanceof MappingStrategy) {
            $this->setStrategy($strategy);
        }
    }

    /**
     * Set class name to map data to
     *
     * @param string $className
     * @return MapperAbstract Fluent interface
     * @throws MapperException
     */
    public function toObject(string $className): MapperAbstract
    {
        if (!class_exists($className)) {
            throw new MapperException(sprintf('Cannot set class name to %s since class not found', $className));
        }
        $this->className = $className;
        $this->mapToObject = true;

        return $this;
    }

    /**
     * @param MappingStrategy $strategy
     * @return MapperAbstract Fluent interface
     */
    public function setStrategy(MappingStrategy $strategy): MapperAbstract
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @return MappingStrategy
     */
    public function getStrategy(): MappingStrategy
    {
        return $this->strategy;
    }

    /**
     * Whether we need to map to an object rather than an array
     *
     * @return bool
     */
    public function isMapToObject(): bool
    {
        return $this->mapToObject;
    }

    /**
     * Return class name to map data to
     *
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Return data from root property, if set
     *
     * @param array $data
     * @param string|null $rootProperty
     * @return array
     * @throws MapperException
     */
    public function getRootData(array $data, ?string $rootProperty = null): array
    {
        $propertyAccessor = $this->getPropertyAccessor();
        if (null !== $rootProperty) {
            if (!$propertyAccessor->isReadable($data, $rootProperty)) {
                throw new MapperException(sprintf('Root property path %s cannot be found in data', $rootProperty));
            }
            $data = $propertyAccessor->getValue($data, $rootProperty);
        }
        return $data;
    }

    /**
     * Instantiates a new object or array
     *
     * @return array|object
     */
    public function getItem()
    {
        if ($this->isMapToObject()) {
            $className = $this->getClassName();
            $item = new $className();
        } else {
            $item = [];
        }
        return $item;
    }

    /**
     * Return an item (array or object) mapped from passed array data
     *
     * @param array $data
     * @return mixed
     * @throws MapperException
     */
    public function buildItemFromData(array $data)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $strategy = $this->getStrategy();
        $item = $this->getItem();

        // Loop through property paths to map to new item (destination => source)
        foreach ($strategy->getPropertyPaths() as $destination => $source) {
            // Source is a MapValue object
            if ($source instanceof MapValueInterface) {
                /** @var MapValueInterface $source */
                $source->setPropertyAccessor($propertyAccessor);
                if ($source->isReadable($data)) {
                    $propertyAccessor->setValue($item, $destination, $source->getValue($data));
                    continue;
                }
            }

            // Source is a callable function/method
            if (is_callable($source)) {
                $propertyAccessor->setValue($item, $destination, $source($data, $destination));
                continue;
            }

            // Source is an array, pick first match
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

            // Source is a string
            if (is_string($source)) {
                if (!$propertyAccessor->isReadable($data, $source)) {
                    $propertyAccessor->setValue($item, $destination, null);
                    continue;
                }
                $propertyAccessor->setValue($item, $destination, $propertyAccessor->getValue($data, $source));
                continue;
            }

            // Invalid source type
            throw new MapperException(sprintf('Source for destination "%s" not a valid type, must be a string, array of strings or callback', $destination));
        }

        // Transform data in destination item
        $item = $strategy->getTransformerChain()->transform($item);

        return $item;
    }
}
