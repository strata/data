<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\RequiredRule;

class RequiredRuleTest extends TestCase
{

    /**
     * @dataProvider validDataProvider
     */
    public function testValid(string $propertyPath)
    {
        $data = ['email' => 'hello@studio24.net', 'title' => '', 'number' => 0, 'things' => [], 'null' => null];

        $validator = new RequiredRule($propertyPath);
        $this->assertTrue($validator->validate($data));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalid(string $propertyPath)
    {
        $data = ['email' => 'hello@studio24.net', 'title' => '', 'number' => 0, 'things' => [], 'null' => null];

        $validator = new RequiredRule($propertyPath);
        $this->assertFalse($validator->validate($data));
    }

    public function validDataProvider()
    {
        return [
            ['[email]'],
            ['[number]'],
        ];
    }

    public function invalidDataProvider()
    {
        return [
            ['[category]'],
            ['[title]'],
            ['[things]'],
            ['[null]'],
        ];
    }

}
