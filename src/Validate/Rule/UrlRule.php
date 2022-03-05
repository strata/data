<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class UrlRule extends ValidatorRuleAbstract
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
        $result = filter_var($value, FILTER_VALIDATE_URL);
        if ($result === false) {
            $this->setErrorMessage(sprintf('%s is not a valid URL', $this->getPropertyPath()));
            return false;
        }
        return true;
    }
}
