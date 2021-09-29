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

final class WildcardMapperTest extends TestCase
{
    public function testSetIgnore()
    {
        $strategy = new WildcardMappingStrategy();
        $strategy->addIgnore(['name', 'address']);
        $this->assertTrue($strategy->isRootElementInIgnore('name'));
        $this->assertTrue($strategy->isRootElementInIgnore('address'));
        $this->assertFalse($strategy->isRootElementInIgnore('foobar'));
    }

    public function testSetMapping()
    {
        $strategy = new WildcardMappingStrategy();
        $strategy->addMapping('person_name', ['[name]' => '[person_name]']);
        $mapping = [
            '[town]' => '[address][town]',
            '[postcode]' => '[address][nested][postcode]'
        ];
        $strategy->addMapping('address', $mapping);

        $this->assertTrue($strategy->isRootElementInMapping('person_name'));
        $this->assertTrue($strategy->isRootElementInMapping('address'));
        $this->assertSame(['[name]' => '[person_name]'], $strategy->getMappingByRootElement('person_name'));
        $this->assertSame($mapping, $strategy->getMappingByRootElement('address'));
    }

    public function testEmptyMapping()
    {
        $mapper = new MapItem(new WildcardMappingStrategy());

        $data = [
            'person_name' => 'Fred Bloggs',
            'person_region' => 'Cambridge',
            'INVALID' => '12345'
        ];
        $item = $mapper->map($data);

        $this->assertSame(3, count($item));
        $this->assertSame('12345', $item['INVALID']);
    }

    public function testWildcardMapping()
    {
        $regionMapping = [
            'East of England' => ['cambridge', 'norwich']
        ];
        $mapping = [
            '[name]' => '[person_name]'
        ];
        $strategy = new WildcardMappingStrategy([
            new MapValues('[person_region]', $regionMapping)
        ]);
        $strategy->addIgnore(['person_age', 'invalid']);
        $strategy->addMapping('person_name', [
            '[name]' => '[person_name]'
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
        $this->assertSame(2, count($item));
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertArrayNotHasKey('person_age', $item);
        $this->assertEquals('East of England', $item['person_region']);
        $this->assertArrayNotHasKey('invalid', $item);

        // Example
        // @see https://docs.strata.dev/data/changing-data/mapping#an-example-1
        $wildcard = new WildcardMappingStrategy();
        $wildcard->addIgnore('Field_to_ignore');
        $wildcard->addMapping('full_name', [
            '[name]' => '[full_name]'
        ]);
        $mapper = new MapItem($wildcard);

        $data = [
            'full_name' => 'Joe Bloggs',
            'Field_to_ignore' => '123',
            'category' => 'fishing'
        ];
        $item = $mapper->map($data);

        $this->assertSame(2, count($item));
        $this->assertSame('Joe Bloggs', $item['name']);
        $this->assertSame('fishing', $item['category']);
    }

    public function testNestedWildcardMapping()
    {
        $strategy = new WildcardMappingStrategy();
        $strategy->addIgnore('person_age');
        $strategy->addMapping('address', ['[postcode]' => '[address][postcode]']);
        $strategy->addMapping('full_name', ['[name]' => '[full_name]']);
        $mapper = new MapItem($strategy);

        $data = [
            'title' => 'Mr',
            'full_name' => 'Fred Bloggs',
            'address'   => [
                'street' => '123 Mill Road',
                'town' => 'Cambridge',
                'postcode' => 'CB1 ABC',
            ],
        ];
        $item = $mapper->map($data);

        $this->assertSame('Mr', $item['title']);
        $this->assertSame('Fred Bloggs', $item['name']);
        $this->assertSame('CB1 ABC', $item['postcode']);
        $this->assertFalse(isset($item['adddress']));
        $this->assertFalse(isset($item['town']));

        $strategy = new WildcardMappingStrategy();
        $strategy->addIgnore('person_age');
        $strategy->addMapping('address', [
            '[street]'   => '[address][street]',
            '[town]'     => '[address][town]',
            '[postcode]' => '[address][postcode]',
        ]);
        $mapper = new MapItem($strategy);

        $item = $mapper->map($data);
        $this->assertSame('123 Mill Road', $item['street']);
        $this->assertSame('Cambridge', $item['town']);
        $this->assertSame('CB1 ABC', $item['postcode']);
    }
}
