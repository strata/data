<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

class IntegerValue extends BaseValue
{
    /**
     * Return property as an integer
     *
     * @param $objectOrArray Data to read property from
     * @return DateTime|null
     */
    public function getValue($objectOrArray)
    {
        $value = parent::getValue($objectOrArray);

        if (is_numeric($value)) {
            return (int) $value;
        }
        return null;
    }
}
