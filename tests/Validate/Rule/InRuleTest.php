<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\InRule;

class InRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new InRule(['test1', 'test2', 'test3']);
        $item = new Item('test');

        $item->setContent(['name' => 'test1']);
        $this->assertTrue($validator->validate('name', $item));

        $item->setContent(['name' => 'test3']);
        $this->assertTrue($validator->validate('name', $item));

        $item->setContent(['name' => 'TEST1']);
        $this->assertFalse($validator->validate('name', $item));

        $item->setContent(['name' => 'test5']);
        $this->assertFalse($validator->validate('name', $item));
    }

}
