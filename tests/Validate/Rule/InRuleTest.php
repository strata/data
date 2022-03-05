<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\InRule;

class InRuleTest extends TestCase
{
    public function testValidationRule()
    {
        $validator = new InRule('[name]', ['test1', 'test2', 'test3']);

        $data = ['name' => 'test1'];
        $this->assertTrue($validator->validate($data));

        $data = ['name' => 'test3'];
        $this->assertTrue($validator->validate($data));

        $data = ['name' => 'TEST1'];
        $this->assertFalse($validator->validate($data));

        $data = ['name' => 'test5'];
        $this->assertFalse($validator->validate($data));
    }
}
