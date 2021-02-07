<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\BooleanRule;

class BooleanRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new BooleanRule();
        $item = new Item('test');

        $item->setContent(['data' => '1']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'true']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'false']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => true]);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'on']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'yes']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'no']);
        $this->assertTrue($validator->validate('data', $item));

        $item->setContent(['data' => 'invalid']);
        $this->assertFalse($validator->validate('data', $item));
    }

}
