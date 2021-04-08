<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Helper\UnionTypes;

final class UnionTypesTest extends TestCase
{

    public function testUnionTypes()
    {
        $this->assertFalse(UnionTypes::arrayOrObject(42));
        $this->assertFalse(UnionTypes::arrayOrObject(24.99));
        $this->assertFalse(UnionTypes::arrayOrObject('string'));
        $this->assertFalse(UnionTypes::arrayOrObject('24'));
        $this->assertTrue(UnionTypes::arrayOrObject([1,2,3]));
        $this->assertTrue(UnionTypes::arrayOrObject(new \stdClass()));
        $this->assertFalse(UnionTypes::arrayOrObject(null));

        $this->assertTrue(UnionTypes::stringOrInt(42));
        $this->assertFalse(UnionTypes::stringOrInt(24.99));
        $this->assertTrue(UnionTypes::stringOrInt('string'));
        $this->assertTrue(UnionTypes::stringOrInt('24'));
        $this->assertFalse(UnionTypes::stringOrInt([1,2,3]));
        $this->assertFalse(UnionTypes::stringOrInt(new \stdClass()));
        $this->assertFalse(UnionTypes::stringOrInt(null));
    }
}
