<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\MapItemToObject;
use Strata\Data\Transform\Data\CallableData;
use Strata\Data\Transform\Value\CallableValue;

function user_transform($value): string
{
    return 'X ' . strtoupper($value);
}

final class MapCallableTest extends TestCase
{

    public function testPhpFunction()
    {
        $mapping = [
            '[name]' => new CallableValue('[person_name]', 'strtolower')
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
        ];
        $item = $mapper->map($data);

        $this->assertEquals('fred bloggs', $item['name']);
    }

    public function testClosure()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[code]' => new CallableData(function ($data) {
                return $data['person_town'] . '_' . $data['person_name'];
            }),
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'Norwich'
        ];
        $item = $mapper->map($data);

        $this->assertEquals('Norwich_Fred Bloggs', $item['code']);
    }

    public function testFunction()
    {
        $mapping = [
            '[name]' => new CallableValue('[person_name]', 'Tests\user_transform'),
            '[age]' => '[person_age]',
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
        ];
        $item = $mapper->map($data);

        $this->assertEquals('X FRED BLOGGS', $item['name']);
    }

    public function testObjectMethod()
    {
        $mapping = [
            '[name]' => new CallableData([$this, 'populateContent']),
            '[age]' => '[person_age]',
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
        ];
        $item = $mapper->map($data);

        $this->assertEquals('FRED BLOGGS', $item['name']);
    }

    public function populateContent(array $data, string $destination)
    {
        switch ($destination) {
            case '[name]':
                return strtoupper($data['person_name']);
        }
    }

    public function testObjectStaticMethod()
    {
        $mapping = [
            '[name]' => new CallableData(['Tests\MapCallableTest', 'staticPopulateContent']),
            '[age]' => '[person_age]',
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
        ];
        $item = $mapper->map($data);

        $this->assertEquals('fred bloggs', $item['name']);
    }

    public static function staticPopulateContent(array $data)
    {
        return strtolower($data['person_name']);
    }
}
