<?php

declare(strict_types=1);

namespace Strata\Data\Transform\AllValues;

/**
 * Decodes HTML entities back to characters
 */
class HtmlEntitiesDecode extends ValuesAbstract
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
        return htmlspecialchars_decode($data);
    }
}
