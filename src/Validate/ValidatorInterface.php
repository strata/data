<?php
declare(strict_types=1);

namespace Strata\Data\Validate;

use Strata\Data\Model\Item;

interface ValidatorInterface
{
    /**
     * Is the item valid?
     *
     * @param Item $item
     * @return bool
     */
    public function validate(Item $item): bool;

    /**
     * Return error message from last validate() call
     *
     * @return string
     */
    public function getErrorMessage(): string;
}
