<?php

declare(strict_types=1);

namespace Strata\Data\Transform;

interface NotTransformedInterface
{
    /**
     * Are there any fields/values not transformed?
     *
     * @return bool
     */
    public function hasNotTransformed(): bool;

    /**
     * Return array of fields/values not transformed
     *
     * @return array
     */
    public function getNotTransformed(): array;
}
