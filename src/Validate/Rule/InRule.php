<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class InRule extends RuleAbstract
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
        $result = in_array($item->getProperty($propertyReference), $this->getValues());
        if (!$result) {
            $this->setErrorMessage(sprintf('%s does not contain a valid value', $propertyReference));
        }
        return $result;
    }
}
