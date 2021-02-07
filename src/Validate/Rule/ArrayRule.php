<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class ArrayRule extends RuleAbstract
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
        $result = is_array($this->getProperty($item, $propertyReference));
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not an array', $propertyReference));
        }
        return $result;
    }
}