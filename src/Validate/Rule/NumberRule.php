<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class NumberRule extends RuleAbstract
{

    /**
     * Is the item value valid?
     *
     * @see https://www.php.net/is-numeric
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    public function validate(string $propertyReference, Item $item): bool
    {
        $result = is_numeric($item->getProperty($propertyReference));
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not a valid number', $propertyReference));
        }
        return $result;
    }
}
