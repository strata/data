<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Model\Item;
use Strata\Data\Validate\ValidationRules;

class ValidationRulesTest extends TestCase
{

    public function testImplode()
    {
        $this->assertEquals('1,2,3', ValidationRules::implode([1, 2, 3]));
        $this->assertEquals('test1,test2,test3', ValidationRules::implode(['test1', 'test2', 'test3']));
    }

    public function testRulesClass()
    {
        $validator = new ValidationRules(['prop' => 'required']);

        $this->expectException('Strata\Data\Exception\ValidatorRulesException');
        $validator->setRules(['prop' => 'invalidRule']);
    }

    public function testRule()
    {
        $validator = new ValidationRules([
            'data' => 'required',
            'email' => 'required|email',
        ]);
        $item = new Item('test');
        $item->setContent([
            'data' => 'something',
            'email' => 'hello@studio24.net'
        ]);
        $this->assertTrue($validator->validate($item));

        $item->setContent([
            'data' => 'something',
            'email' => 'invalid email'
        ]);

        $this->assertFalse($validator->validate($item));
        $this->assertStringContainsString('not a valid email', $validator->getErrorMessage());
    }

    public function testNestedProperty()
    {
        $validator = new ValidationRules([
            'data.title' => 'required',
            'data.item.type' => 'number',
            'data.item.url' => 'required|url',
        ]);
        $item = new Item('test');
        $item->setContent([
            'data' => [
                'title' => 'Testing',
                'item' => [
                    'type' => '2',
                    'url' => 'http://www.studio24.net/',
                ]
            ]
        ]);

        $this->assertTrue($validator->validate($item));
    }

}
