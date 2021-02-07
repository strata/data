<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\ArrayRule;

class ArrayRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new ArrayRule();
        $item = new Item('test');

        $item->setContent(['data' => []]);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => ['name' => 1, 'item' => 2]]);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent('string content');
        $this->assertFalse($validator->validate('data', $item));
        $this->assertStringContainsString('data is not an array', $validator->getErrorMessage());
    }

}
