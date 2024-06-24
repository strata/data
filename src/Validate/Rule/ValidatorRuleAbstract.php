<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Transform\PropertyAccessorTrait;

abstract class ValidatorRuleAbstract implements ValidatorRuleInterface
{
    use PropertyAccessorTrait;

    private string $propertyPath;
    private array $values = [];
    private string $errorMessage = '';

    /**
     * Constructor
     *
     * @param string $propertyPath
     * @param array $values Array of values to pass to the validator (e.g. for a rule of "in:1,2,3" pass [1,2,3])
     */
    public function __construct(string $propertyPath, array $values = [])
    {
        $this->setPropertyPath($propertyPath);
        if (!empty($values)) {
            $this->setValues($values);
        }
    }

    /**
     * Set array of values to use with this validation rule
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        $this->values = $values;
    }
    /**
     * Return array of any passed values for this validation rule
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Set error message
     *
     * @param string $message
     */
    protected function setErrorMessage(string $message)
    {
        $this->errorMessage = $message;
    }

    /**
     * Return error message from last validate() call
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Set property path to data field to validate
     *
     * @param string $propertyPath
     */
    public function setPropertyPath(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Return property path of data field to validate
     *
     * @return string
     */
    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    /**
     * Return property from data, or null if not found
     *
     * @param object|array $data
     * @return mixed|null
     */
    public function getProperty(object|array $data)
    {
        if (!$this->getPropertyAccessor()->isReadable($data, $this->propertyPath)) {
            return null;
        }
        return $this->getPropertyAccessor()->getValue($data, $this->propertyPath);
    }
}
