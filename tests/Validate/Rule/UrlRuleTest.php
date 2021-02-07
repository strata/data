<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\UrlRule;

class UrlRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new UrlRule();
        $item = new Item('test');

        $item->setContent(['url' => 'https://www.php.net/']);
        $this->assertTrue($validator->validate('url', $item));

        $item->setContent(['url' => 'hello@studio24.net']);
        $this->assertFalse($validator->validate('url', $item));
    }

}
