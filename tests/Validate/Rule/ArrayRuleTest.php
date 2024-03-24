<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\ArrayRule;

class ArrayRuleTest extends TestCase
{
    public function testValidationRule()
    {
        $validator = new ArrayRule('[data]');

        $data = ['data' => []];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => ['name' => 1, 'item' => 2]];
        $this->assertTrue($validator->validate($data));

        $data = 'string content';
        $this->expectException(\TypeError::class);
        $this->assertFalse($validator->validate($data));
    }
}
