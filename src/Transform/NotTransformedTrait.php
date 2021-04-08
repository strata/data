<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

trait NotTransformedTrait
{
    private array $notTransformed = [];

    /**
     * Are there any fields/values not transformed?
     *
     * @return bool
     */
    public function hasNotTransformed(): bool
    {
        return !empty($this->notTransformed);
    }

    /**
     * Add a value to the not transformed array
     *
     * @param $value
     */
    public function addNotTransformed($value)
    {
        $this->notTransformed[] = $value;
    }

    /**
     * Return array of fields/values not transformed
     *
     * @return array
     */
    public function getNotTransformed(): array
    {
        return $this->notTransformed;
    }
}
