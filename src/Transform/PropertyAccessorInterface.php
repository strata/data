<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

use Symfony\Component\PropertyAccess\PropertyAccessor;

interface PropertyAccessorInterface
{

    /**
     * Set PropertyAccessor
     *
     * @param PropertyAccessor $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessor $propertyAccessor);

    /**
     * Return PropertyAccessor
     *
     * This sets up the PropertyAccessor so isReadable() works on arrays and objects and exceptions are thrown if
     * you try to get a value that does not exist in an array and object
     *
     * @see Symfony\Component\PropertyAccess\Exception\NoSuchIndexException
     * @see Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @return PropertyAccessor
     */
    public function getPropertyAccessor(): PropertyAccessor;
}
