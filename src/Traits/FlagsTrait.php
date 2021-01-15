<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

/**
 * Trait FlagsTrait
 *
 * This uses bitwise operators to calculate whether options are enabled.
 *
 * It is recommended to define options in your class via constants with valid binary numbers that increase in increment
 * by a power of 2, e.g.: 1, 2, 4, 8, 16, 32, 64, 128, etc.
 *
 * E.g. given:
 * const OPTION_A = 1;
 * const OPTION_B = 2;
 * const OPTION_B = 4;
 *
 * // Set enabled flags to A and B
 * $class = new Class();
 * $class->setFlags(Class::OPTION_A | Class::OPTION_B);
 *
 * // This will return true
 * if ($class->flagEnabled(class::OPTION_B)) {
 * }
 *
 * Please note you can only have a maximum of 64 flags on aq 64-bit system
 *
 * @package Strata\Data\Traits
 */
trait FlagsTrait
{
    protected $flags;

    public function __construct(int $options = null)
    {
        if ($options !== null) {
            $this->setFlags($options);
        }
    }

    /**
     * Set options
     * @param int $flags
     */
    public function setFlags(int $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Is an option enabled?
     * @param int $flag
     * @return bool
     */
    public function flagEnabled(int $flag): bool
    {
        return (($this->flags & $flag) !== 0);
    }

}
