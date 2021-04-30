<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Value;

use Strata\Data\Helper\UnionTypes;

class CallableValue extends BaseValue
{
    private $callable;

    /**
     * Constructor
     * @param string $propertyPath Path to root item to map values for
     * @param callable $callable Callable function, closure, or object method
     */
    public function __construct(string $propertyPath, callable $callable)
    {
        $this->callable = $callable;
        parent::__construct($propertyPath);
    }

    /**
     * Return callable function or object method
     *
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool
    {
        return UnionTypes::is($data, 'array', 'object');
    }

    /**
     * Return property using a callback to transform value
     *
     * @param $objectOrArray Data to read property from
     * @return ?bool
     */
    public function getValue($objectOrArray)
    {
        $value = parent::getValue($objectOrArray);
        if (null === $value) {
            return null;
        }

        $callable = $this->callable;
        return $callable($value);
    }

    /**
     * Return property using a callback to transform value
     *
     * @param object|array $objectOrArray Source data
     * @param ...$arguments One or many arguments to pass to callable function/method
     * @return mixed
     */
    public function __invoke($objectOrArray)
    {
        return $this->getValue($objectOrArray);
    }
}
