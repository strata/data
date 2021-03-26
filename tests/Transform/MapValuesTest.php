<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Transform\Data\MapItem;
use Strata\Data\Transform\Data\MapValues;

final class MapValuesTest extends TestCase
{
    public $mapping = [
        'Engineering' => 'engineering jobs',
        'Design & Engineering Management'  => 'engineering jobs',
        'Construction - Engineering'                 => 'construction jobs',
        'Construction - Project and Site Management' => 'construction jobs',
        'Construction - Site Supervision'            => 'construction jobs',
    ];

    public function testMapValues()
    {
        $data1 = [
            'item' => [
                'id'        => 42,
                'category'  => 'Engineering'
            ]
        ];
        $data2 = [
            'item' => [
                'id'        => 42,
                'category'  => 'Construction - Engineering'
            ]
        ];
        $data3 = [
            'item' => [
                'id'        => 42,
                'category'  => ' Construction - Engineering '
            ]
        ];
        $data4 = [
            'item' => [
                'id'        => 42,
                'category'  => 'ENGINEERING'
            ]
        ];

        $mapper = new MapValues('[item][category]', $this->mapping);
        $data1 = $mapper->transform($data1);
        $this->assertEquals('engineering jobs', $data1['item']['category']);

        $data2 = $mapper->transform($data2);
        $this->assertEquals('construction jobs', $data2['item']['category']);

        $data3 = $mapper->transform($data3);
        $this->assertEquals('construction jobs', $data3['item']['category']);

        $data4 = $mapper->transform($data4);
        $this->assertEquals('engineering jobs', $data4['item']['category']);
    }

    public function testArray()
    {
        $data = [
            'categories'  => [
                'Engineering',
                'Construction - Engineering'
            ]
        ];

        $mapper = new MapValues('[categories]', $this->mapping);
        $data = $mapper->transform($data);
        $this->assertEquals('engineering jobs', $data['categories'][0]);
        $this->assertEquals('construction jobs', $data['categories'][1]);
    }

    public function testCallback()
    {
        $data = [
            'id'        => 42,
            'category'  => 'ENGINEERING'
        ];

        $mapping = [
            'Engineering' => function ($value, $data, $propertyPath) {
                return strtolower($value) . ' (' . $data['id'] . ')';
            },
        ];

        $mapper = new MapValues('[category]', $mapping);
        $data = $mapper->transform($data);
        $this->assertEquals('engineering (42)', $data['category']);
    }

}
