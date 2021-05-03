<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\CollectionInterface;
use Strata\Data\Exception\MapperException;
use Strata\Data\Transform\PropertyAccessorTrait;

abstract class MapperAbstract
{
    use PropertyAccessorTrait;

    private bool $mapToObject = false;
    private ?string $className = null;
    private string $collectionClass = 'Strata\Data\Collection';
    private MappingStrategyInterface $strategy;

    /**
     * MapItem constructor
     *
     * @param array|MappingStrategyInterface $strategy Array of mapping property paths, or MappingStrategy object
     */
    public function __construct($strategy)
    {
        if (is_array($strategy)) {
            $this->setStrategy(new MappingStrategy($strategy));
        }
        if ($strategy instanceof MappingStrategyInterface) {
            $this->setStrategy($strategy);
        }
    }

    /**
     * Map data to an object of class name
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
     * Set class name to use for a collection
     *
     * @param string $className
     * @return MapperAbstract Fluent interface
     * @throws MapperException
     */
    public function setCollectionClass(string $className): MapperAbstract
    {
        if (!class_exists($className)) {
            throw new MapperException(sprintf('Cannot set collection class name to %s since class not found', $className));
        }
        $test = new $className();
        if (!($test instanceof CollectionInterface)) {
            throw new MapperException(sprintf('%s must implement Strata\Data\CollectionInterface', $className));
        }
        $this->collectionClass = $className;

        return $this;
    }

    /**
     * @param MappingStrategyInterface $strategy
     * @return MapperAbstract Fluent interface
     */
    public function setStrategy(MappingStrategyInterface $strategy): MapperAbstract
    {
        $strategy->setPropertyAccessor($this->getPropertyAccessor());
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @return MappingStrategyInterface
     */
    public function getStrategy(): MappingStrategyInterface
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
     * Return class name to map item data to
     *
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Return collection class name to use for a collection
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return $this->collectionClass;
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
        $item = $this->getItem();
        return $this->getStrategy()->mapItem($data, $item);
    }
}
