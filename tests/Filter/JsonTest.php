<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Filter\Json;

final class JsonTest extends TestCase
{
    protected $json = <<<EOD
{
	"name": "John Smith",
	"places": ["place1", "place2", "place3"],
	"tel": 1234567
}
EOD;

    protected $invalidJson = <<<EOD
{
	name: "John Smith",
	places: ["place1", "place2", "place3"],
	tel: 1234567
}
EOD;

    public function testJson()
    {
        $json = new Json();
        $data = $json->filter($this->json);

        $this->assertIsArray($data);
        $this->assertEquals('John Smith', $data['name']);
        $this->assertEquals('place2', $data['places'][1]);
        $this->assertEquals('1234567', $data['tel']);
    }

    public function testInvalidJson()
    {
        $json = new Json();

        $this->expectException(DecoderException::class);
        $data = $json->filter($this->invalidJson);
    }

}
