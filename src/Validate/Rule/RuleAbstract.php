<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;
use Strata\Data\Validate\ValidationRules;

abstract class RuleAbstract implements RuleInterface
{
    private array $values = [];
    private string $errorMessage = '';

    /**
     * Constructor
     *
     * @param array $values Array of values to pass to the validation rule (e.g. for a rule of "in:1,2,3" pass [1,2,3])
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * Return array of any passed values for this validation rule
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Explode property reference into an array of nested property references
     *
     * E.g. 'data.title' returns ['data', 'title']
     *
     * @param string $propertyReference
     * @return array
     */
    protected function explodeNestedProperty(string $propertyReference): array
    {
        return explode(ValidationRules::NESTED_PROPERTY_SEPARATOR, $propertyReference);
    }

    /**
     * Internal method to return nested property
     *
     * Usage:
     *
     * $nestedItem = clone $item;
     * foreach ($this->explodeNestedProperty($propertyReference) as $nestedProperty) {
     *     $nestedItem = $this->getNestedProperty($nestedItem, $nestedProperty);
     * }
     *
     * @param $nestedItem
     * @param $property
     * @return mixed|null Nested value, or null if it doesn't exist
     */
    protected function getNestedProperty($nestedItem, $property)
    {
        if ($nestedItem instanceof Item && $nestedItem->containsString()) {
            return null;
        }
        if (isset($nestedItem[$property])) {
            return $nestedItem[$property];
        }
        return null;
    }

    /**
     * Return nested property from an item, or null if property does not exist
     *
     * @param Item $item
     * @param string $propertyReference
     * @return mixed|null
     */
    public function getProperty(Item $item, string $propertyReference)
    {
        $nestedItem = clone $item;
        foreach ($this->explodeNestedProperty($propertyReference) as $nestedProperty) {
            $nestedItem = $this->getNestedProperty($nestedItem, $nestedProperty);
            if ($nestedItem === null) {
                return null;
            }
        }
        return $nestedItem;
    }

    /**
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    abstract public function validate(string $propertyReference, Item $item): bool;

    /**
     * Set error message
     *
     * @param string $message
     */
    protected function setErrorMessage(string $message)
    {
        $this->errorMessage = $message;
    }

    /**
     * Return error message from last validate() call
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

}