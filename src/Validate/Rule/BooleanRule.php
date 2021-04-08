<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class BooleanRule extends ValidatorRuleAbstract
{
    const VALID = ["1", "0", "true", "false", "on", "off", "yes", "no"];

    /**
     * Is the item value valid?
     *
     * @param array|object $data
     * @return bool
     */
    public function validate($data): bool
    {
        $value = $this->getProperty($data);
        $result = in_array($value, self::VALID);
        if ($result === false) {
            $this->setErrorMessage(sprintf('%s is not a valid boolean', $this->getPropertyPath()));
            return false;
        }
        return true;
    }
}
