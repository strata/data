<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

class FloatValue extends BaseValue
{
    /**
     * Return property as a float
     *
     * @param $objectOrArray Data to read property from
     * @return float|null
     */
    public function getValue($objectOrArray): ?float
    {
        $value = parent::getValue($objectOrArray);

        if (is_numeric($value)) {
            return (float) $value;
        }
        return null;
    }
}
