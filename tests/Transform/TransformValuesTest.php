<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Transform\Values\HtmlEntitiesDecode;
use Strata\Data\Transform\Values\SetEmptyToNull;
use Strata\Data\Transform\Values\StripTags;
use Strata\Data\Transform\Values\Trim;

final class TransformValuesTest extends TestCase
{
    public function testHtmlEntitiesDecode()
    {
        $transform = new HtmlEntitiesDecode();

        $data = 'Foo=Bar&amp;Page=4&amp;Data=My name is Joe';
        $this->assertTrue($transform->canTransform($data));
        $this->assertEquals('Foo=Bar&Page=4&Data=My name is Joe', $transform->transform($data));
        $this->assertEquals('My test sentence is here', $transform->transform('My test sentence is here'));
    }

    public function testSetEmptyToNull()
    {
        $transform = new SetEmptyToNull();

        $this->assertNull($transform->transform(''));
        $this->assertNull($transform->transform('0'));
        $this->assertNull($transform->transform(0));
        $this->assertNull($transform->transform([]));

        $this->assertNotNull($transform->transform('a'));
        $this->assertNotNull($transform->transform(1));
        $this->assertNotNull($transform->transform([0]));
        $this->assertNotNull($transform->transform([1,2]));
    }

    public function testStripTags()
    {
        $transform = new StripTags();

        $data = '<bold>Testing</bold>';
        $this->assertTrue($transform->canTransform($data));
        $this->assertEquals('Testing', $transform->transform($data));
        $this->assertEquals('Testing', $transform->transform('<a href="http://www.bbc.co.uk/">Testing</a>'));
        $this->assertFalse($transform->canTransform(42));
    }

    public function testTrim()
    {
        $transform = new Trim();

        $this->assertEquals('Testing', $transform->transform(' Testing '));
        $this->assertEquals('Testing', $transform->transform("Testing\t"));
        $this->assertEquals('Testing', $transform->transform("Testing       "));
    }

}
