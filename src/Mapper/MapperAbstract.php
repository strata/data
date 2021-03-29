<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Exception\MapperException;
use Strata\Data\Transform\PropertyAccessorTrait;

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
     * @return MapItem Fluent interface
     * @throws MapperException
     */
    public function toObject(string $className): MapItem
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
     * @return MapItem Fluent interface
     */
    public function setStrategy(MappingStrategy $strategy): MapItem
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
}