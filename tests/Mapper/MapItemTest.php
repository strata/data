<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\MapItemToObject;
use Strata\Data\Mapper\MappingStrategy;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Transform\Data\MapValues;
use Strata\Data\Transform\Value\DateTimeValue;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Person
{
    public string $name;
    public int $age;
    public string $region;
    public string $job_title;
}
class Person2 extends Person
{
    public function setName($name)
    {
        $this->name = strtolower($name);
    }
}

final class MapItemTest extends TestCase
{
    public function testMapItemToArray()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[age]' => '[person_age]',
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
        ];
        $item = $mapper->map($data);

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertEquals('42', $item['age']);
    }

    public function testMapFromRootProperty()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[age]' => '[person_age]',
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'data' => [
                'person_name' => 'Fred Bloggs',
                'person_age'   => '42',
            ]
        ];
        $item = $mapper->map($data, '[data]');

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertEquals('42', $item['age']);
    }

    public function testNullData()
    {
        $mapper = new MapItem(new WildcardMappingStrategy());
        $data = [
            'data' => null
        ];

        $item = $mapper->map($data, '[data]');

        $this->assertNull($item);
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
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'Norwich'
        ];
        $item = $mapper->map($data);

        $this->assertEquals('Norwich', $item['region']);
        $this->assertArrayNotHasKey('age', $item);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_region' => 'Norwich'
        ];
        $item = $mapper->map($data);
        $this->assertEquals('Norwich', $item['region']);
    }

    public function testMapValues()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[age]' => '[person_age]',
            '[region]' => [
                '[person_region]', '[person_town]'
            ]
        ];
        $regionMapping = [
            'East of England' => ['cambridge', 'norwich'],
            'Yorkshire and the Humber' => ['Leeds', 'Sheffield', 'Bradford'],
        ];
        $strategy = new MappingStrategy($mapping, [
            new MapValues('[region]', $regionMapping)
        ]);
        $mapper = new MapItem($strategy);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'Norwich'
        ];
        $item = $mapper->map($data);
        $this->assertEquals('East of England', $item['region']);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_town' => 'sheffield'
        ];
        $item = $mapper->map($data);
        $this->assertEquals('Yorkshire and the Humber', $item['region']);
    }

    public function testDateTimeValue()
    {
        $mapping = [
            '[name]' => '[person_name]',
            '[date_of_birth]' => new DateTimeValue('[dob]')
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'dob' => '1981-10-15'
        ];
        $item = $mapper->map($data);

        $this->assertInstanceOf('\DateTime', $item['date_of_birth']);
        $this->assertSame('15 Oct 1981', $item['date_of_birth']->format('d M Y'));
        $this->assertSame('Thu, 15 Oct 1981 00:00:00 +0000', $item['date_of_birth']->format('r'));
    }

    public function testMapClass()
    {
        $mapping = [
            'name' => '[person_name]',
            'age' => '[person_age]',
            'region' => '[person_region]',
            'job_title' => '[occupation]',
        ];
        $mapper = new MapItem($mapping);
        $mapper->toObject('Strata\Data\Tests\Person');

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
            'person_region' => 'Cambridge',
            'occupation' => 'PHP Developer',
        ];

        /** @var Person $item */
        $item = $mapper->map($data);

        $this->assertIsObject($item);
        $this->assertEquals('Fred Bloggs', $item->name);
        $this->assertEquals(42, $item->age);
        $this->assertEquals('Cambridge', $item->region);
        $this->assertEquals('PHP Developer', $item->job_title);
    }

    public function testWildcardMapping()
    {
        $regionMapping = [
            'East of England' => ['cambridge', 'norwich']
        ];
        $strategy = new WildcardMappingStrategy(['person_age', 'invalid'], [
            new MapValues('[person_region]', $regionMapping)
        ]);
        $mapper = new MapItem($strategy);

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_age'   => '42',
            'person_region' => 'Cambridge',
            'INVALID' => '12345'
        ];
        $item = $mapper->map($data);

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['person_name']);
        $this->assertArrayNotHasKey('person_age', $item);
        $this->assertEquals('East of England', $item['person_region']);
        $this->assertArrayNotHasKey('invalid', $item);
    }
}
