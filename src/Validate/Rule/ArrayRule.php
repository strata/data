<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class ArrayRule extends ValidatorRuleAbstract
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
        $result = is_array($value);
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not an array', $this->getPropertyPath()));
        }
        return $result;
    }
}
