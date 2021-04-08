<?php
declare(strict_types=1);

namespace Strata\Data\Transform\Value;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

interface MapValueInterface
{
    /**
     * Return property path to this value
     * @return string
     */
    public function getPropertyPath(): string;

    /**
     * Is the property path readable in the passed data?
     *
     * @param $objectOrArray Data to read property from
     * @return mixed
     */
    public function isReadable($objectOrArray);

    /**
     * Get the property from the passed data, or null if not found
     *
     * @param $objectOrArray Data to read property from
     * @return mixed|null
     */
    public function getValue($objectOrArray);

    public function setPropertyAccessor(PropertyAccessor $propertyAccessor);

    public function getPropertyAccessor(): PropertyAccessor;
}