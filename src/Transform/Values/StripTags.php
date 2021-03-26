<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Values;

/**
 * Strips tags from data values
 */
class StripTags extends ValuesAbstract
{

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool
    {
        return is_string($data);
    }

    /**
     * Transform array of data into something else
     *
     * @param $data
     * @return mixed
     */
    public function transform($data)
    {
        return strip_tags($data);
    }
}
