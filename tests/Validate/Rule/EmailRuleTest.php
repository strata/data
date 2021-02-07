<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\EmailRule;

class EmailRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new EmailRule();
        $item = new Item('test');

        $item->setContent(['email' => 'hello@studio24.net']);
        $this->assertTrue($validator->validate('email', $item));

        $item->setContent(['email' => 'invalid @ domain.com']);
        $this->assertFalse($validator->validate('email', $item));
    }

}
