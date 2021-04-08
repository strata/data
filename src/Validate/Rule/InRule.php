<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class InRule extends ValidatorRuleAbstract
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
        $result = in_array($value, $this->getValues());
        if (!$result) {
            $this->setErrorMessage(sprintf('%s does not contain a valid value', $this->getPropertyPath()));
        }
        return $result;
    }
}
