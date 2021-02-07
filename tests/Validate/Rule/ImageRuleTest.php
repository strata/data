<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\Rule\ImageRule;

class ImageRuleTest extends TestCase
{

    public function testValidationRule()
    {
        $validator = new ImageRule();
        $item = new Item('test');

        $item->setContent(['img' => 'test.webp']);
        $this->assertTrue($validator->validate('img', $item));

        $item->setContent(['img' => 'TEST.JPEG']);
        $this->assertTrue($validator->validate('img', $item));

        $item->setContent(['img' => 'test']);
        $this->assertFalse($validator->validate('img', $item));
    }

}
