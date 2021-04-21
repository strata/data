<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

use Strata\Data\Helper\UnionTypes;
use Strata\Data\Transform\PropertyAccessorTrait;

class BaseValue implements MapValueInterface
{
    use PropertyAccessorTrait;

    protected $propertyPath;

    /**
     * BaseValue constructor.
     * @param string|array $propertyPath Property path to read data from
     */
    public function __construct($propertyPath)
    {
        UnionTypes::assert('$propertyPath', $propertyPath, 'string', 'array');
        $this->propertyPath = $propertyPath;
    }

    /**
     * Return property path to this value
     * @return string|array
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * Is the property path readable in the passed data?
     *
     * @param $objectOrArray Data to read property from
     * @return mixed
     */
    public function isReadable($objectOrArray)
    {
        if (is_array($this->propertyPath)) {
            return $this->isFirstValueReadable($objectOrArray, $this->propertyPath);
        }

        $propertyAccessor = $this->getPropertyAccessor();
        return $propertyAccessor->isReadable($objectOrArray, $this->propertyPath);
    }

    /**
     * Get the property from the passed data, or null if not found
     *
     * @param $objectOrArray Data to read property from
     * @return mixed|null
     */
    public function getValue($objectOrArray)
    {
        if (is_array($this->propertyPath)) {
            return $this->getFirstValue($objectOrArray, $this->propertyPath);
        }

        $propertyAccessor = $this->getPropertyAccessor();
        return $propertyAccessor->getValue($objectOrArray, $this->propertyPath);
    }
}
