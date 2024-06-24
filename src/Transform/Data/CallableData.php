<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

class CallableData extends DataAbstract
{
    private $callable;
    private array $dataPropertyPaths;

    /**
     * Constructor
     *
     * @param callable $callable Callable function, closure, or object method
     * @param ...$dataPropertyPaths Property paths of data fields to pass to the callable (if not set, passes entire data array)
     */
    public function __construct(callable $callable, ...$dataPropertyPaths)
    {
        $this->callable = $callable;
        if (!empty($dataPropertyPaths)) {
            $this->dataPropertyPaths = $dataPropertyPaths;
        }
    }

    /**
     * Whether any specific data property paths for this callable have been passed
     *
     * @return bool
     */
    public function hasDataPropertyPaths(): bool
    {
        return !empty($this->dataPropertyPaths);
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
        return (is_array($data) || is_object($data));
    }

    /**
     * Transform value based on a callable
     *
     * @param object|array $objectOrArray Source data
     * @param ...$arguments One or many arguments to pass to callable function/method
     * @return mixed
     */
    public function transform($objectOrArray)
    {
        $callable = $this->callable;

        // Only pass noted data fields to callable
        if ($this->hasDataPropertyPaths()) {
            $propertyAccessor = $this->getPropertyAccessor();
            $values = [];
            foreach ($this->dataPropertyPaths as $propertyPath) {
                if ($propertyAccessor->isReadable($objectOrArray, $propertyPath)) {
                    $values[] = $propertyAccessor->getValue($objectOrArray, $propertyPath);
                } else {
                    $values[] = null;
                }
            }

            // Pass data values to callable (note this strips away any additional arguments)
            return $callable(...$values);
        }

        // Pass entire data array/object to callable
        return $callable($objectOrArray);
    }

    /**
     * Transform value based on a callable
     *
     * @param object|array $objectOrArray Source data
     * @return mixed
     */
    public function __invoke($objectOrArray)
    {
        return $this->transform($objectOrArray);
    }
}
