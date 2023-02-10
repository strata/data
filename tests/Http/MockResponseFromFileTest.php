<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

final class MockResponseFromFileTest extends TestCase
{
    public function testOnlyBody()
    {
        $client = new MockHttpClient([new MockResponseFromFile(__DIR__ . '/mock-response/mock-response.json')]);
        $response = $client->request('GET', 'http://localhost');

        $body = <<<EOD
{
  "message": "HELLO"
}
EOD;

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, $response->getContent());
    }

    public function testWithStatusCodeAndHeader()
    {
        $client = new MockHttpClient([new MockResponseFromFile(__DIR__ . '/mock-response/mock-response2.json')]);
        $response = $client->request('GET', 'http://localhost');

        $body = <<<EOD
{
  "message": "OK"
}
EOD;

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('101', $response->getHeaders()['x-total-results'][0]);
        $this->assertEquals($body, $response->getContent());
    }

    public function testFailed()
    {
        $client = new MockHttpClient([new MockResponseFromFile(__DIR__ . '/mock-response/mock-response3.json')]);
        $response = $client->request('GET', 'http://localhost');

        $body = <<<EOD
{
  "message": "NOT OK"
}
EOD;

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals($body, $response->getContent(false));
    }

    public function testNoFile()
    {
        $this->expectException('\Exception');
        $client = new MockHttpClient([new MockResponseFromFile(__DIR__ . '/mock-response/fake.json')]);
    }

    public function testInvalidDomainAsFile()
    {
        $this->expectException('Exception');
        $client = new MockHttpClient([new MockResponseFromFile('https://httpbin.org/status/200')]);
    }
}
