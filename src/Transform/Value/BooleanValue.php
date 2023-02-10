<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

class BooleanValue extends BaseValue
{
    private array $trueValues = [1, '1', 'true', 'yes', 'y'];
    private array $falseValues = [0, '0', 'false', 'no', 'n'];

    /**
     * Constructor
     * @param string $propertyPath Property path to read data from
     * @param array|null $trueValues Array of valid true values
     * @param array|null $falseValues Array of valid false values
     */
    public function __construct($propertyPath, ?array $trueValues = null, ?array $falseValues = null)
    {
        parent::__construct($propertyPath);

        if (null !== $trueValues) {
            $this->trueValues = $trueValues;
        }
        if (null !== $falseValues) {
            $this->falseValues = $falseValues;
        }
    }

    /**
     * Return property as a boolean
     *
     * @param $objectOrArray Data to read property from
     * @return ?bool
     */
    public function getValue($objectOrArray)
    {
        $value = parent::getValue($objectOrArray);
        if (null === $value) {
            return null;
        }

        $value = strtolower($value);
        if (in_array($value, $this->trueValues, true)) {
            return true;
        }
        if (in_array($value, $this->falseValues, true)) {
            return false;
        }
        return null;
    }
}
