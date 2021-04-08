<?php

declare(strict_types=1);

namespace Strata\Data\Transform\AllValues;

/**
 * Set empty values to null so they are consistent
 */
class SetEmptyToNull extends ValuesAbstract
{

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool
    {
        return true;
    }

    /**
     * Transform array of data into something else
     *
     * @param $data
     * @return mixed
     */
    public function transform($data)
    {
        if (empty($data)) {
            return null;
        }
        return $data;
    }
}
