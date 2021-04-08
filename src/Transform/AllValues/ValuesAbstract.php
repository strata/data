<?php

declare(strict_types=1);

namespace Strata\Data\Transform\AllValues;

use Strata\Data\Transform\NotTransformedTrait;
use Strata\Data\Transform\TransformInterface;

abstract class ValuesAbstract implements TransformInterface
{
    use NotTransformedTrait;

    /**
     * Return what type of data this transformer transforms (either values, or an entire data array)
     * @return int
     */
    public function getType(): int
    {
        return TransformInterface::TRANSFORM_VALUE;
    }

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    abstract public function canTransform($data): bool;

    /**
     * Transform data into something else
     *
     * @param $data
     * @return mixed
     */
    abstract public function transform($data);
}
