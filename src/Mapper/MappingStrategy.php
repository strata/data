<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Transform\TransformerChain;
use Strata\Data\Transform\TransformInterface;

/**
 * Class to manage strategy of mapping data to an item
 */
class MappingStrategy
{
    private array $propertyPaths;
    private TransformerChain $transformerChain;

    /**
     * Set fields to map from data to your new array/object
     *
     * @param array $propertyPaths Array of new property path => source data property path/s or callback to return value (with arguments: $data, $destinationPropertyPath)
     * @param array $transformers Array of transformers to apply to mapped data
     */
    public function __construct(array $propertyPaths, array $transformers = [])
    {
        $this->setPropertyPaths($propertyPaths);
        $this->transformerChain = new TransformerChain();

        foreach ($transformers as $transformer) {
            if ($transformer instanceof TransformInterface) {
                $this->addTransformer($transformer);
            }
        }
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
     * Transformer to apply to mapped data
     *
     * @param TransformInterface $transformer
     */
    public function addTransformer(TransformInterface $transformer)
    {
        $this->transformerChain->addTransformer($transformer);
    }

    /**
     * @return TransformerChain
     */
    public function getTransformerChain(): TransformerChain
    {
        return $this->transformerChain;
    }
}
