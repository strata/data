<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;
use Strata\Data\Validate\ValidationRules;

abstract class RuleAbstract implements RuleInterface
{
    private array $values = [];
    private string $errorMessage = '';

    /**
     * Constructor
     *
     * @param array $values Array of values to pass to the validation rule (e.g. for a rule of "in:1,2,3" pass [1,2,3])
     */
    public function __construct(array $values = [])
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
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    abstract public function validate(string $propertyReference, Item $item): bool;

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
}
