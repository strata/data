<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\NumberRule;

class NumberRuleTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     */
    public function testValid($number)
    {
        $validator = new NumberRule('[data]');

        $data = ['data' => $number];
        $this->assertTrue($validator->validate($data));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalid($number)
    {
        $validator = new NumberRule('[data]');

        $data = ['data' => $number];
        $this->assertFalse($validator->validate($data));
    }

    public static function validDataProvider()
    {
        return [
            [1],
            ["10"],
            ["0.1"],
        ];
    }

    public static function invalidDataProvider()
    {
        return [
            ['one'],
        ];
    }
}
