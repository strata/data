<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\BooleanRule;

class BooleanRuleTest extends TestCase
{
    public function testValidationRule()
    {
        $validator = new BooleanRule('[data]');

        $data = ['data' => '1'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'true'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'false'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => true];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'on'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'yes'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'no'];
        $this->assertTrue($validator->validate($data));

        $data = ['data' => 'invalid'];
        $this->assertFalse($validator->validate($data));
    }
}
