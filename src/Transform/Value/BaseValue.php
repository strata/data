<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

use Strata\Data\Transform\PropertyAccessorTrait;

class BaseValue implements MapValueInterface
{
    use PropertyAccessorTrait;

    protected string $propertyPath;

    /**
     * BaseValue constructor.
     * @param string $propertyPath Property path to read data from
     */
    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Return property path to this value
     * @return string
     */
    public function getPropertyPath(): string
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
        $propertyAccessor = $this->getPropertyAccessor();
        return $propertyAccessor->getValue($objectOrArray, $this->propertyPath);
    }
}
