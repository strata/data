<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Validate\ValidatorInterface;

interface ValidatorRuleInterface extends ValidatorInterface
{
    /**
     * Return property path of data field to validate
     *
     * @return string
     */
    public function getPropertyPath(): string;

    /**
     * Set property path to data field to validate
     *
     * @param string $propertyPath
     */
    public function setPropertyPath(string $propertyPath);

    /**
     * Return property from data, or null if not found
     *
     * @param $data
     * @return mixed|null
     */
    public function getProperty($data);

    /**
     * Set array of values to use with this validation rule
     *
     * @param array $values
     */
    public function setValues(array $values);

    /**
     * Return array of any passed values for this validation rule
     *
     * @return array
     */
    public function getValues();
}
