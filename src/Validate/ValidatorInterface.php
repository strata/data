<?php

declare(strict_types=1);

namespace Strata\Data\Validate;

use Strata\Data\Transform\PropertyAccessorInterface;

interface ValidatorInterface extends PropertyAccessorInterface
{
    /**
     * Is the item valid?
     *
     * @param mixed $data
     * @return bool
     */
    public function validate($data): bool;

    /**
     * Return error message from last validate() call
     *
     * @return string
     */
    public function getErrorMessage(): string;


}
