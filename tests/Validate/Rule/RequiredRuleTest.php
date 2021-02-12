<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\RequiredRule;

class RequiredRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new RequiredRule();
        $item = new Item('test');
        $item->setContent(['email' => 'hello@studio24.net', 'title' => '', 'number' => 0, 'things' => [], 'null' => null]);

        $this->assertTrue($validator->validate('email', $item));
        $this->assertFalse($validator->validate('category', $item));
        $this->assertFalse($validator->validate('title', $item));
        $this->assertTrue($validator->validate('number', $item));
        $this->assertFalse($validator->validate('things', $item));
        $this->assertFalse($validator->validate('null', $item));
    }

}
