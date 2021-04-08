<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\Json;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

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
        $decoder = new Json();
        $data = $decoder->decode($this->json);

        $this->assertIsArray($data);
        $this->assertEquals('John Smith', $data['name']);
        $this->assertEquals('place2', $data['places'][1]);
        $this->assertEquals('1234567', $data['tel']);
    }

    public function testJsonInHttpResponse()
    {
        $decoder = new Json();
        $responses = [
            new MockResponseFromFile(__DIR__ . '/../Http/http/query.json'),
        ];
        $client = new MockHttpClient($responses);
        $response = $client->request('GET', 'http://example.com/');
        $data = $decoder->decode($response);

        $this->assertEquals('Test', $data['title']);
        $this->assertEquals(46, $data['id']);
    }

    public function testInvalidJson()
    {
        $decoder = new Json();
        $this->expectException(DecoderException::class);
        $decoder->decode($this->invalidJson);
    }

    public function testInvalidJson2()
    {
        $decoder = new Json();
        $this->expectException(DecoderException::class);
        $decoder->decode(null);
    }
}
