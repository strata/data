<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Helper\UnionTypes;

final class UnionTypesTest extends TestCase
{

    public function testArrayOrObject()
    {
        $this->assertFalse(UnionTypes::is(42, 'array', 'object'));
        $this->assertFalse(UnionTypes::is(24.99, 'array', 'object'));
        $this->assertFalse(UnionTypes::is('string', 'array', 'object'));
        $this->assertFalse(UnionTypes::is('24', 'array', 'object'));
        $this->assertTrue(UnionTypes::is([1, 2, 3], 'array', 'object'));
        $this->assertTrue(UnionTypes::is(new \stdClass(), 'array', 'object'));
        $this->assertFalse(UnionTypes::is(null, 'array', 'object'));

        $this->assertTrue(UnionTypes::is(new \DateTime(), 'DateTime', 'array'));
        $this->assertFalse(UnionTypes::is(new \stdClass(), 'DateTime', 'array'));
    }

    public function testStringOrInt()
    {
        $this->assertTrue(UnionTypes::is(42, 'string', 'int'));
        $this->assertFalse(UnionTypes::is(24.99, 'string', 'int'));
        $this->assertTrue(UnionTypes::is('string', 'string', 'int'));
        $this->assertTrue(UnionTypes::is('24', 'string', 'int'));
        $this->assertFalse(UnionTypes::is([1,2,3], 'string', 'int'));
        $this->assertFalse(UnionTypes::is(new \stdClass(), 'string', 'int'));
        $this->assertFalse(UnionTypes::is(null, 'string', 'int'));
    }

    public function testStringOrObject()
    {
        $this->assertFalse(UnionTypes::is(42, 'string', 'object'));
        $this->assertFalse(UnionTypes::is(24.99, 'string', 'object'));
        $this->assertTrue(UnionTypes::is('string', 'string', 'object'));
        $this->assertTrue(UnionTypes::is('24', 'string', 'object'));
        $this->assertFalse(UnionTypes::is([1,2,3], 'string', 'object'));
        $this->assertTrue(UnionTypes::is(new \stdClass(), 'string', 'object'));
        $this->assertFalse(UnionTypes::is(null, 'string', 'object'));

        $this->assertTrue(UnionTypes::is(new \DateTime(), 'DateTime', 'string'));
        $this->assertFalse(UnionTypes::is(new \stdClass(), 'DateTime', 'string'));
    }

    public function testStringOrArray()
    {
        $this->assertFalse(UnionTypes::is(42, 'string', 'array'));
        $this->assertFalse(UnionTypes::is(24.99, 'string', 'array'));
        $this->assertTrue(UnionTypes::is('string', 'string', 'array'));
        $this->assertTrue(UnionTypes::is('24', 'string', 'array'));
        $this->assertTrue(UnionTypes::is([1,2,3], 'string', 'array'));
        $this->assertFalse(UnionTypes::is(new \stdClass(), 'string', 'array'));
        $this->assertFalse(UnionTypes::is(null, 'string', 'array'));
    }

    public function testAssertStringArray()
    {
        $this->expectException('InvalidArgumentException');
        UnionTypes::assert('test', 42, 'string', 'array');
    }

    public function testAssertArrayInt()
    {
        $this->expectException('InvalidArgumentException');
        UnionTypes::assert('test', 'value', 'array', 'int');
    }

    public function testAssertObjectString()
    {
        $this->expectException('InvalidArgumentException');
        UnionTypes::assert('test', [1,2,3], 'object', 'string');
    }
}
