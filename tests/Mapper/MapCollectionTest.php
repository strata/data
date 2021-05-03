<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Collection;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\MapperAbstract;

class Item
{
    public string $name;
    public int $id;
}

class MyCollection extends Collection
{

}

class InvalidCollection
{

}

final class MapCollectionTest extends TestCase
{
    private array $data = [
        'items' => [
            0 => [
                'item_name' => 'Apple',
                'id' => 1
            ],
            1 => [
                'item_name' => 'Banana',
                'id' => 2
            ],
            2 => [
                'item_name' => 'Orange',
                'id' => 3
            ]
        ],
            'meta_data' => [
                'total' => 10,
                'page' => 1,
                'per_page' => 3
        ]
    ];

    public function testEmptyArray()
    {
        $mapping = [
            '[name]'   => '[item_name]',
            '[id]'     => '[id]',
        ];
        $mapper = new MapCollection($mapping);
        $mapper->totalResults('[data][total]');

        $data = [
            'data' => [
                'total' => 0,
                'items' => []
            ]
        ];
        $collection = $mapper->map($data, '[data][items]');

        $this->assertEquals(0, count($collection));
    }

    public function testMapToArray()
    {
        $mapping = [
            '[name]'   => '[item_name]',
            '[id]'     => '[id]',
        ];
        $mapper = new MapCollection($mapping);
        $mapper->totalResults('[meta_data][total]')
               ->resultsPerPage('[meta_data][per_page]');

        $collection = $mapper->map($this->data, '[items]');

        $this->assertInstanceOf('Strata\Data\Collection', $collection);
        $this->assertEquals(3, count($collection));
        $this->assertEquals('Banana', $collection[1]['name']);
        $this->assertEquals(1, $collection->getPagination()->getPage());
        $this->assertEquals(3, $collection->getPagination()->getResultsPerPage());
        $this->assertEquals(10, $collection->getPagination()->getTotalResults());
        $this->assertEquals(4, $collection->getPagination()->getTotalPages());
        $this->assertEquals(4, count($collection->getPagination()));
    }

    public function testMapToCollection()
    {
        $mapping = [
            'name'   => '[item_name]',
            'id'     => '[id]',
        ];
        $mapper = new MapCollection($mapping);
        $mapper->totalResults('[meta_data][total]')
            ->resultsPerPage('[meta_data][per_page]')
            ->toObject('Tests\Item');

        $collection = $mapper->map($this->data, '[items]');
        $this->assertTrue($collection instanceof Collection);

        $item = $collection->current();
        $this->assertEquals('Apple', $item->name);

        $item = $collection[1];
        $this->assertEquals('Banana', $item->name);
    }

    public function testMapToCustomClass()
    {
        $mapping = [
            'name'   => '[item_name]',
            'id'     => '[id]',
        ];
        $mapper = new MapCollection($mapping);
        $mapper->totalResults('[meta_data][total]')
            ->resultsPerPage('[meta_data][per_page]')
            ->toObject('Tests\Item')
            ->setCollectionClass('Tests\MyCollection');

        $collection = $mapper->map($this->data, '[items]');
        $this->assertTrue($collection instanceof MyCollection);
        $item = $collection->current();
        $this->assertEquals('Apple', $item->name);

        $item = $collection[1];
        $this->assertEquals('Banana', $item->name);
    }

    public function testInvalidCustomClass()
    {
        $this->expectException('Strata\Data\Exception\MapperException');

        $mapper = new MapCollection([]);
        $mapper->toObject('Tests\Item')
               ->setCollectionClass('Tests\InvalidCollection');
    }
}
