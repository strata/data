<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class RequiredRule extends ValidatorRuleAbstract
{
    /**
     * Is the item value valid?
     *
     * @param array|object $data
     * @return bool
     */
    public function validate($data): bool
    {
        $result = true;

        $value = $this->getProperty($data);
        if ($value === null) {
            $result = false;
        } elseif ($value === '') {
            $result = false;
        } elseif (is_array($value) && count($value) === 0) {
            $result = false;
        }

        if (!$result) {
            $this->setErrorMessage(sprintf('%s does not exist or is empty', $this->getPropertyPath()));
        }
        return $result;
    }
}
