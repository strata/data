<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Mapper\MappingStrategy;
use Strata\Data\Transform\Data\MapValues;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Person {
    public string $name;
    public int $age;
    public string $region;
}

final class MapItemTest extends TestCase
{
    public function testMapItemToArray()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[age]' => '[person_age]',
            '[region]' => [
                '[person_region]', '[person_town]'
            ]
        ];
        $regionMapping = [
            'cambridge' => 'East of England',
            'norwich' => 'East of England',
        ];
        $mapper = new MappingStrategy($mapping);
        $mapper->addTransformer(new MapValues('[region]', $regionMapping));

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
            'person_region' => 'Cambridge'
        ];
        $item = $mapper->mapItem($data);

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertEquals('42', $item['age']);
        $this->assertEquals('East of England', $item['region']);
    }

    public function testMultipleSourcePaths()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[age]' => '[person_age]',
            '[region]' => [
                '[person_region]', '[person_town]'
            ]
        ];
        $regionMapping = [
            'cambridge' => 'East of England',
            'norwich' => 'East of England',
        ];
        $mapper = new MappingStrategy($mapping);
        $mapper->addTransformer(new MapValues('[region]', $regionMapping));

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'Norwich'
        ];
        $item = $mapper->mapItem($data);

        $this->assertEquals('East of England', $item['region']);
        $this->assertNull($item['age']);
    }

    public function testCallback()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[code]' => function ($data) {
                return $data['person_town'] . '_' . $data['person_name'];
            },
        ];
        $mapper = new MappingStrategy($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'Norwich'
        ];
        $item = $mapper->mapItem($data);

        $this->assertEquals('Norwich_Fred Bloggs', $item['code']);
    }

    public function testMapClass()
    {
        $mapping = [
            'name' => '[person_name]',
            'age' => '[person_age]',
            'region' => '[person_region]'
        ];
        $mapper = new MappingStrategy($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
            'person_region' => 'Cambridge'
        ];

        /** @var Person $item */
        $item = $mapper->mapItem($data, 'Strata\Data\Tests\Person');

        $this->assertIsObject($item);
        $this->assertEquals('Fred Bloggs', $item->name);
        $this->assertEquals('42', $item->age);
        $this->assertEquals('Cambridge', $item->region);
    }

}
