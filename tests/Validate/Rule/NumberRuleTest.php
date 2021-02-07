<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\NumberRule;

class NumberRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new NumberRule();
        $item = new Item('test');

        $item->setContent(['data' => '24']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => '0.1']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'one']);
        $this->assertFalse($validator->validate('data', $item));
    }

}
