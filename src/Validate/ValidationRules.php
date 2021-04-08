<?php

declare(strict_types=1);

namespace Strata\Data\Validate;

use Strata\Data\Exception\ValidatorRulesException;
use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Validate\Rule\ValidatorRuleInterface;

/**
 * Simple example validator
 *
 * Pass an array of field rules in the form (data property, dots represent array level) and multiple rules separated by |
 *
 * E.g.
 * $rules = new ValidationRules([
 *     '[data][entries]' => 'array',
 *     '[data][entries][title]' => 'required',
 * ];
 *
 * @see https://laravel.com/docs/validation Inspired by Laravel's Validation
 * @package Strata\Data\Validator
 */
class ValidationRules implements ValidatorInterface
{
    use PropertyAccessorTrait;

    const RULE_SEPARATOR = '|';
    const RULE_VALUE_SEPARATOR = ':';
    const VALUES_SEPARATOR = ',';
    private array $rules = [];
    private array $errors = [];

    /**
     * Constructor
     *
     * @param array $fieldRules
     * @throws ValidatorRulesException For an invalid rule
     */
    public function __construct(array $fieldRules)
    {
        $this->setRules($fieldRules);
    }

    /**
     * Return class to use for validation rule
     *
     * @param string $ruleName
     * @return string Fully qualified class name
     * @throws ValidatorRulesException
     */
    protected function getRuleClass(string $ruleName): string
    {
        switch ($ruleName) {
            case 'array':
                return '\Strata\Data\Validate\Rule\ArrayRule';
            case 'boolean':
                return '\Strata\Data\Validate\Rule\BooleanRule';
            case 'email':
                return '\Strata\Data\Validate\Rule\EmailRule';
            case 'in':
                return '\Strata\Data\Validate\Rule\InRule';
            case 'number':
                return '\Strata\Data\Validate\Rule\NumberRule';
            case 'image':
                return '\Strata\Data\Validate\Rule\ImageRule';
            case 'required':
                return '\Strata\Data\Validate\Rule\RequiredRule';
            case 'url':
                return '\Strata\Data\Validate\Rule\UrlRule';
            default:
                throw new ValidatorRulesException(sprintf('Validation rule not recognised: %s', $ruleName));
        }
    }

    /**
     * Set rules to the validator
     *
     * @param array $rules
     * @throws ValidatorRulesException For an invalid rule
     */
    public function setRules(array $rules)
    {
        foreach ($rules as $propertyReference => $rule) {
            $this->addRule($propertyReference, $rule);
        }
    }

    /**
     * Convenience function to implode multiple values
     *
     * @param array $values
     * @return string
     */
    public static function implode(array $values): string
    {
        return implode(self::VALUES_SEPARATOR, $values);
    }

    /**
     * Add a rule for a single data property to the validator
     *
     * @param string $propertyReference E.g. data.entries.email
     * @param string|ValidatorRuleInterface $rules E.g. required|email or a Validator object
     * @throws ValidatorRulesException For an invalid rule
     */
    public function addRule(string $propertyReference, $rules)
    {
        if ($rules instanceof ValidatorRuleInterface) {
            $this->rules[$propertyReference] = $rules;
        }

        foreach (explode(self::RULE_SEPARATOR, $rules) as $rule) {
            // Detect rules (e.g. email) & rules with arguments (e.g. in:1,2,3)
            $elements = explode(self::RULE_VALUE_SEPARATOR, $rule);
            if (count($elements) == 1) {
                $class = $this->getRuleClass($rule);

                /** @var ValidatorRuleInterface $validator */
                $validator = new $class($propertyReference);
                $validator->setPropertyAccessor($this->getPropertyAccessor());
                $this->rules[$propertyReference] = $validator;
                continue;
            }
            if (count($elements) == 2) {
                $class = $this->getRuleClass($elements[0]);
                $values = explode(self::VALUES_SEPARATOR, $elements[1]);

                /** @var ValidatorInterface $rule */
                $validator = new $class($propertyReference, $values);
                $validator->setPropertyAccessor($this->getPropertyAccessor());
                $this->rules[$propertyReference] = $validator;
                continue;
            }
            throw new ValidatorRulesException(sprintf('Invalid validation rule: %s (for property ref %s)', $rule, $propertyReference));
        }
    }

    /**
     * Is the item data valid?
     *
     * @param array|object $data
     * @return bool
     */
    public function validate($data): bool
    {
        $valid = true;
        $this->errors = [];

        /** @var ValidatorRuleInterface $validator */
        foreach ($this->rules as $propertyReference => $validator) {
            if (!$validator->validate($data)) {
                $valid = false;
                $this->errors[] = $validator->getErrorMessage();
            }
        }

        return $valid;
    }

    /**
     * Return error message from last validate() call
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return implode(', ', $this->errors);
    }
}
