<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\EmailRule;

class EmailRuleTest extends TestCase
{

    /**
     * @dataProvider validDataProvider
     */
    public function testValid(string $email)
    {
        $validator = new EmailRule('[email]');

        $data = ['email' => $email];
        $this->assertTrue($validator->validate($data));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalid(string $email)
    {
        $validator = new EmailRule('[email]');

        $data = ['email' => $email];
        $this->assertFalse($validator->validate($data));
    }

    public function validDataProvider()
    {
        return [
            ['name@domain.com'],
            ["TomO'Reilly@domain.com"],
        ];
    }

    public function invalidDataProvider()
    {
        return [
            ['invalid @ domain.com'],
            ['domain.com'],
        ];
    }
}
