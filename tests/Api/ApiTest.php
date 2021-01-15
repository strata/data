<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Api\RestApiAbstract;
use Strata\Data\Exception\UriPatternException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Strata\Data\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestRestApi extends RestApiAbstract
{

    public function setupHttpClient(): HttpClientInterface
    {
        return HttpClient::create([
            'base_uri' => $this->getBaseUri(),
            'headers' => [
                'User-Agent' => $this->getUserAgent(),
            ]
        ]);
    }
}

final class ApiTest extends TestCase
{

    public function testBasicMethods()
    {
        $api = new TestRestApi('https://example.com/');
        $this->assertEquals('https://example.com/', $api->getBaseUri());
        $this->assertStringContainsString('Strata', $api->getUserAgent());
    }

    public function testGetUri()
    {
        $api = new TestRestApi('https://example.com/');
        $this->assertEquals('posts/123', $api->getUri('one', 'posts', 123));
        $this->assertEquals('posts', $api->getUri('list', 'posts'));

        // Test custom URI
        $api->registerUri('test1', 'test/%s/?find=%s');
        $this->assertEquals('test/posts/?find=cheese', $api->getUri('test1', 'posts', 'cheese'));

        // Test literal %
        $api->registerUri('test2', 'test/%%/test/%s');
        $this->assertEquals('test/%/test/posts', $api->getUri('test2', 'posts'));
    }

    public function testGetInvalidUri1()
    {
        $api = new TestRestApi('https://example.com/');

        $this->expectException(UriPatternException::class);
        $api->getUri('one', 'posts');
    }

    public function testGetInvalidUri2()
    {
        $api = new TestRestApi('https://example.com/');
        $api->registerUri('test2', 'test/%%/test/%s');

        $this->expectException(UriPatternException::class);
        $api->getUri('test2');
    }

    public function testGetInvalidUri3()
    {
        $api = new TestRestApi('https://example.com/');

        $this->expectException(UriPatternException::class);
        $api->getUri('fishing', 'posts');
    }

    public function testBasicRestApiFunctions()
    {
        /**
         * Mock HTTP responses
         *
         * @see ResponseInterface::getInfo()
         */
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/basic-test'),
            new MockResponseFromFile(__DIR__ . '/responses/basic-test'),
            new MockResponseFromFile(__DIR__ . '/responses/basic-test'),
        ];

        $api = new TestRestApi('https://example.com/');
        $api->setClient(new MockHttpClient($responses, 'https://example.com/'));

        // GET
        $response = $api->get('test1');
        $data = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($api->isSuccess());
        $this->assertEquals('OK', $data['message']);
        $this->assertEquals(1, $api->getTotalRequests());

        // Headers
        $this->assertEquals('101', $api->getHeader($response, 'X-Total-Results'));
        $this->assertTrue(is_array($api->getHeader($response, 'X-Multiple-Header-Vals')));
        $this->assertEquals('B', $api->getHeader($response, 'X-Multiple-Header-Vals')[1]);
        $this->assertNull($api->getHeader($response, 'X-Fake-Header'));

        $response = $api->get('test2', ['page' => 1, 'foo' => 'bar']);
        $this->assertEquals('https://example.com/test2?page=1&foo=bar', $response->getInfo('url'));
        $this->assertEquals(2, $api->getTotalRequests());

        // HEAD
        $response = $api->head('test3');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $api->getTotalRequests());

        // POST
        $callback = function ($method, $url, $options) {
            parse_str($options['body'], $postData);
            if (!isset($postData['name'])) {
                return new MockResponse('Bad request: name not found', ['http_code' => 400]);
            }
            $body = sprintf('My name is %s', $postData['name']);
            return new MockResponse($body, ['http_code' => 200]);
        };

        $api->setClient(new MockHttpClient($callback));
        $api->throwOnFailedRequest(false);
        $response = $api->post('http://example.com/test4', ['data' => 'stored']);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($api->isSuccess());

        $api->setClient(new MockHttpClient($callback));
        $response = $api->post('http://example.com/test4', ['name' => 'John Smith', 'data' => 'stored']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('My name is John Smith', $response->getContent());

        $this->assertEquals(5, $api->getTotalRequests());
    }
}
