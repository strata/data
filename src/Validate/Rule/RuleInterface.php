<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

interface RuleInterface
{
    /**
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    public function validate(string $propertyReference, Item $item): bool;

    /**
     * Return error message from last validate() call
     *
     * This is optional, and will be empty if the validator is self-evident
     *
     * @return string
     */
    public function getErrorMessage(): string;
}
