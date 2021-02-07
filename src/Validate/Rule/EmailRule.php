<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class EmailRule extends RuleAbstract
{

    /**
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    public function validate(string $propertyReference, Item $item): bool
    {
        $result = filter_var($this->getProperty($item, $propertyReference), FILTER_VALIDATE_EMAIL);
        if ($result === false) {
            $this->setErrorMessage(sprintf('%s is not a valid email', $propertyReference));
            return false;
        }
        return true;
    }
}