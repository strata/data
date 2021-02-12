<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class BooleanRule extends RuleAbstract
{
    const VALID = ["1", "0", "true", "false", "on", "off", "yes", "no"];

    /**
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    public function validate(string $propertyReference, Item $item): bool
    {
        $result = in_array($item->getProperty($propertyReference), self::VALID);
        if ($result === false) {
            $this->setErrorMessage(sprintf('%s is not a valid boolean', $propertyReference));
            return false;
        }
        return true;
    }
}
