<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Mapper\MapArray;
use Strata\Data\Mapper\MapItem;

final class MapArrrayTest extends TestCase
{
    public function testMapArray()
    {
        $childrenMapping = [
            '[title]' => '[child_title]',
            '[url]'   => '[child_link]',
        ];
        $mapping = [
            '[name]'     => '[person_name]',
            '[children]' => new MapArray('[children]', $childrenMapping)
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'children'    => [
                ['child_title' => 'Test 1', 'child_link' => 'https://example/1'],
                ['child_title' => 'Test 2', 'child_link' => 'https://example/2'],
                ['child_title' => 'Test 3', 'child_link' => 'https://example/3'],
            ]
        ];
        $item = $mapper->map($data);

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertEquals(3, count($item['children']));
        $this->assertEquals('https://example/2', $item['children'][1]['url']);
    }

    public function testMapArrayWithMultipleSourceValues()
    {
        $childrenMapping = [
            '[title]' => '[child_title]',
            '[url]'   =>  ['[link]','[uri]'],
        ];
        $mapping = [
            '[name]'     => '[person_name]',
            '[children]' => new MapArray('[children]', $childrenMapping)
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'person_name' => 'Fred Bloggs',
            'children'    => [
                ['title' => 'Test 1', 'link' => 'https://example/1'],
                ['title' => 'Test 2', 'uri' => '/my-link'],
                ['title' => 'Test 3', 'link' => 'https://example/3'],
            ]
        ];
        $item = $mapper->map($data);

        $this->assertIsArray($item);
        $this->assertEquals('Fred Bloggs', $item['name']);
        $this->assertEquals(3, count($item['children']));
        $this->assertEquals('https://example/1', $item['children'][0]['url']);
        $this->assertEquals('/my-link', $item['children'][1]['url']);
    }
}
