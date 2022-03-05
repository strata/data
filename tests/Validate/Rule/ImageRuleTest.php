<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\ImageRule;

class ImageRuleTest extends TestCase
{
    public function testValidationRule()
    {
        $validator = new ImageRule('[img]');

        $data = ['img' => 'test.webp'];
        $this->assertTrue($validator->validate($data));

        $data = ['img' => 'TEST.JPEG'];
        $this->assertTrue($validator->validate($data));

        $data = ['img' => 'test'];
        $this->assertFalse($validator->validate($data));
    }
}
