<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

interface TransformInterface
{
    const TRANSFORM_VALUE = 1;
    const TRANSFORM_DATA = 2;

    /**
     * Return what type of data this transformer transforms (either values, or an entire data array)
     * @return int
     */
    public function getType(): int;

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool;

    /**
     * Transform data into something else
     *
     * @param $data
     * @return mixed
     */
    public function transform($data);
}
