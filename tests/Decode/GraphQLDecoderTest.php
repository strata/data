<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\GraphQL;

final class GraphQLDecoderTest extends TestCase
{

    public function testValid()
    {
        $decoder = new GraphQL();

        $data = <<<EOD
{
  "data": { "name": "joe bloggs" },
  "errors": [ { "message": "test" } ]
}
EOD;

        $data = $decoder->decode($data);

        $this->assertIsArray($data);
        $this->assertEquals('joe bloggs', $data['name']);
        $this->assertFalse(isset($data['errors']));
    }

    public function testInvalid()
    {
        $decoder = new GraphQL();

        $data = <<<EOD
{
  "name": "joe bloggs"
}
EOD;

        $this->expectException('Strata\Data\Exception\DecoderException');
        $data = $decoder->decode($data);
    }
}
