<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

/**
 * Class to run a chain of transformers over data
 */
class TransformerChain
{
    /** @var TransformInterface[] */
    private array $valueTransformers = [];

    /** @var TransformInterface[] */
    private array $dataTransformers = [];

    /**
     * Add transformer to alter data
     *
     * @param TransformInterface $transformer
     */
    public function addTransformer(TransformInterface $transformer)
    {
        switch ($transformer->getType()) {
            case TransformInterface::TRANSFORM_VALUE:
                $this->valueTransformers[] = $transformer;
                break;
            case TransformInterface::TRANSFORM_DATA:
                $this->dataTransformers[] = $transformer;
                break;
        }
    }

    /**
     * Transform data
     *
     * This runs transformers for all data array values first (e.g. StripTags),
     * then runs transformers for the entire data array (e.g. RenameFields)
     *
     * @param $data
     * @return mixed
     */
    public function transform($data)
    {
        // First transform all values in data array
        foreach ($this->valueTransformers as $transformer) {
            array_walk_recursive($data, function (&$value) use ($transformer) {
                if ($transformer->canTransform($value)) {
                    $value = $transformer->transform($value);
                }
            });
        }

        // Next transform data array
        foreach ($this->dataTransformers as $transformer) {
            if ($transformer->canTransform($data)) {
                $data = $transformer->transform($data);
            }
        }
        return $data;
    }
}
