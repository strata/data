<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class RequiredRule extends RuleAbstract
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
        $result = true;
        $value = $this->getProperty($item, $propertyReference);
        if ($value === null) {
            $result = false;
        } else if ($value === '') {
            $result = false;
        } else if (is_array($value) && count($value) === 0) {
            $result = false;
        }

        if (!$result) {
            $this->setErrorMessage(sprintf('%s does not exist or is empty', $propertyReference));
        }
        return $result;
    }

}