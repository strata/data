<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

use Strata\Data\Helper\UnionTypes;

class CallableData extends DataAbstract
{
    private $callable;

    /**
     * Constructor
     * @param callable $callable Callable function, closure, or object method
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
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
     * Transform value based on a callable
     *
     * @param object|array $objectOrArray Source data
     * @param ...$arguments One or many arguments to pass to callable function/method
     * @return mixed
     */
    public function transform($objectOrArray, ...$arguments)
    {
        $callable = $this->callable;
        return $callable($objectOrArray, ...$arguments);
    }

    /**
     * Transform value based on a callable
     *
     * @param object|array $objectOrArray Source data
     * @param ...$arguments One or many arguments to pass to callable function/method
     * @return mixed
     */
    public function __invoke($objectOrArray, ...$arguments)
    {
        return $this->transform($objectOrArray, ...$arguments);
    }
}
