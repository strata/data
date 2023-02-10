<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Transform\Data\Concatenate;

final class ConcatenateTest extends TestCase
{
    public function testConcatenate()
    {
        $mapping = [
            '[name]'  => new Concatenate('[first_name]', '[last_name]')
        ];
        $mapper = new MapItem($mapping);

        $data = [
            'first_name' => "John",
            "last_name" => "Doe"
        ];

        $item = $mapper->map($data);
        $this->assertSame('John Doe', $item['name']);
    }
}
