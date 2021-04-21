<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Trait for property accessor functionality
 */
trait PropertyAccessorTrait
{
    /**
     * Property accessor
     * @see https://symfony.com/doc/current/components/property_access.html
     * @see PropertyAccessorInterface
     * @var PropertyAccessor
     */
    private ?PropertyAccessor $propertyAccessor = null;

    /**
     * Set PropertyAccessor
     *
     * @param PropertyAccessor $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

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
    public function getPropertyAccessor(): PropertyAccessor
    {
        if (!($this->propertyAccessor instanceof PropertyAccessor)) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor();
        }
        return $this->propertyAccessor;
    }

    /**
    * Get value from first matching property path in an array of property paths
    *
    * @param string|array $objectOrArray Data to find matching property path in
    * @param array $propertyPaths Array of property paths to check
    * @return ?string Matching property path, or null on not found
    */
    public function getFirstValue($objectOrArray, array $propertyPaths): ?string
    {
        foreach ($propertyPaths as $path) {
            if ($this->getPropertyAccessor()->isReadable($objectOrArray, $path)) {
                return $this->getPropertyAccessor()->getValue($objectOrArray, $path);
            }
        }
        return null;
    }

    /**
     * Returns whether a property path can be read from an object graph, matches first found property path
     *
     * @param string|array $objectOrArray Data to find matching property path in
     * @param array $propertyPaths Array of property paths to check
     * @return bool Whether property path found in object
     */
    public function isFirstValueReadable($objectOrArray, array $propertyPaths): bool
    {
        foreach ($propertyPaths as $path) {
            if ($this->getPropertyAccessor()->isReadable($objectOrArray, $path)) {
                return true;
            }
        }
        return false;
    }

}
