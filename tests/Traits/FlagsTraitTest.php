<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Traits\FlagsTrait;

class TestFlags
{
    use FlagsTrait;

    const OPTION_A = 1;
    const OPTION_B = 2;
    const OPTION_C = 4;
    const OPTION_D = 8;
    const OPTION_E = 16;
}

final class FlagsTest extends TestCase
{
    public function testFlags()
    {
        $class = new TestFlags();
        $class->setFlags(TestFlags::OPTION_A | TestFlags::OPTION_C | TestFlags::OPTION_E);

        $this->assertTrue($class->flagEnabled(TestFlags::OPTION_A));
        $this->assertFalse($class->flagEnabled(TestFlags::OPTION_B));
        $this->assertTrue($class->flagEnabled(TestFlags::OPTION_C));
        $this->assertFalse($class->flagEnabled(TestFlags::OPTION_D));
        $this->assertTrue($class->flagEnabled(TestFlags::OPTION_E));

        $class->setFlags(TestFlags::OPTION_B);

        $this->assertFalse($class->flagEnabled(TestFlags::OPTION_A));
        $this->assertTrue($class->flagEnabled(TestFlags::OPTION_B));
        $this->assertFalse($class->flagEnabled(TestFlags::OPTION_C));
    }
}
