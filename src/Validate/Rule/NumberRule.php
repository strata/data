<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class NumberRule extends ValidatorRuleAbstract
{
    /**
     * Is the item value valid?
     *
     * @param array|object $data
     * @return bool
     */
    public function validate($data): bool
    {
        $value = $this->getProperty($data);
        $result = is_numeric($value);
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not a valid number', $this->getPropertyPath()));
        }
        return $result;
    }
}
