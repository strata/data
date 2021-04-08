<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Transform\Value\BaseValue;
use Strata\Data\Transform\Value\DateTimeValue;

final class TransformValueTest extends TestCase
{
    public function testBase()
    {
        $transformValue = new BaseValue('[value]');
        $data1 = ['value' => 'test'];
        $data2 = ['name' => 'test'];

        $this->assertTrue($transformValue->isReadable($data1));
        $this->assertSame('test', $transformValue->getValue($data1));
        $this->assertFalse($transformValue->isReadable($data2));
    }

    public function testDateTime()
    {
        $data = [
            'date' => '2021-04-05T11:09:15+00:00'
        ];

        $valueTransformer = new DateTimeValue('[date]');
        $this->assertInstanceOf('\DateTime', $valueTransformer->getValue($data));
        $this->assertSame('Mon, 05 Apr 2021 11:09:15 +0000', $valueTransformer->getValue($data)->format('r'));
    }
}
