<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

use Strata\Data\Helper\UnionTypes;

class Concatenate extends DataAbstract
{
    private array $propertyPaths;

    public function __construct(...$propertyPaths)
    {
        $this->propertyPaths = $propertyPaths;
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
     * @param object|array $data Source data
     * @return mixed
     */
    public function transform($data)
    {
        $transformed = [];
        foreach ($this->propertyPaths as $path) {
            $transformed[] = $this->getPropertyAccessor()->getValue($data, $path);
        }
        return implode(' ', $transformed);
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
