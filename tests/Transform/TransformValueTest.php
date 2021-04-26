<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Transform\Value\BaseValue;
use Strata\Data\Transform\Value\BooleanValue;
use Strata\Data\Transform\Value\DateTimeValue;
use Strata\Data\Transform\Value\FloatValue;
use Strata\Data\Transform\Value\IntegerValue;

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

    public function testInt()
    {
        $valueTransformer = new IntegerValue('[number]');
        $this->assertIsInt($valueTransformer->getValue(['number' => '2021']));
        $this->assertIsInt($valueTransformer->getValue(['number' => '9.99']));
        $this->assertNull($valueTransformer->getValue(['number' => 'Testing']));
    }

    public function testFloat()
    {
        $valueTransformer = new FloatValue('[float]');
        $this->assertIsFloat($valueTransformer->getValue(['float' => '9.99']));
        $this->assertIsFloat($valueTransformer->getValue(['float' => '42']));
        $this->assertNull($valueTransformer->getValue(['float' => 'Testing']));
    }

    public function testBool()
    {
        $valueTransformer = new BooleanValue('[question]');
        $this->assertTrue($valueTransformer->getValue(['question' => 'true']));
        $this->assertFalse($valueTransformer->getValue(['question' => 'false']));
        $this->assertNull($valueTransformer->getValue(['question' => 'not true']));

        $valueTransformer = new BooleanValue('[question]', ['yes'], ['no']);
        $this->assertTrue($valueTransformer->getValue(['question' => 'YES']));
        $this->assertFalse($valueTransformer->getValue(['question' => 'NO']));
        $this->assertNull($valueTransformer->getValue(['question' => 'true']));
    }
}
