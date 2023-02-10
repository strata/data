<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Transform\TransformerChain;
use Strata\Data\Transform\TransformInterface;

trait TransformerTrait
{
    private ?TransformerChain $transformerChain = null;

    /**
     * Transformer to apply to mapped data
     *
     * @param TransformInterface $transformer
     */
    public function addTransformer(TransformInterface $transformer)
    {
        if (!($this->transformerChain instanceof TransformerChain)) {
            $this->transformerChain = new TransformerChain();
        }
        $this->transformerChain->addTransformer($transformer);
    }

    /**
     * Set transformer chain
     *
     * @param array $transformers
     */
    public function setTransformers(array $transformers)
    {
        foreach ($transformers as $transformer) {
            if ($transformer instanceof TransformInterface) {
                $this->addTransformer($transformer);
            }
        }
    }

    /**
     * Return a chain of multiple transformers
     *
     * @return ?TransformerChain
     */
    public function getTransformerChain(): ?TransformerChain
    {
        return $this->transformerChain;
    }
}
