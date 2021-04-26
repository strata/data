<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\Http;
use Strata\Data\Http\Rest;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpTest extends TestCase
{

    public function testUserAgent()
    {
        $api = new Http('https://example.com/api/');
        $version = $api->getUserAgent();

        $this->assertStringContainsString('Strata_Data', $version);
        $this->assertStringContainsString('(+https://github.com/strata/data)', $version);
    }

    public function testRss()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/../Decode/rss/example.rss'),
        ];
        $api = new Http('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $feed = $api->getRss('feed.rss');
        $this->assertInstanceOf('Laminas\Feed\Reader\Feed\FeedInterface', $feed);
        $this->assertEquals('News feed generator', $feed->getTitle());
    }

    public function testError()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/invalid.json'),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $this->expectException('\Strata\Data\Exception\HttpNotFoundException');
        $response = $api->get('test');
    }

    public function testQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $response = $api->get('test');
        $this->assertInstanceOf('Symfony\Contracts\HttpClient\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());

        $item = $api->decode($response);
        $this->assertIsArray($item);
        $this->assertSame('46', $item['id']);
        $this->assertSame("Test", $item['title']);

        /** ideas */
        return;

        // Data manager (deals with logging, events, data transformations)
        $manager = new DataManager();

        // Data provider (gets data)
        $api = new Rest('https://example.com/api/', $manager);
        $response = $api->get('test');
        $data = $api->decode($response);

        $valid = $manager->validate($data, new RequiredValidator(['id', 'title']));
        $data = $mamager->transform($data, new ArrayTransformer());

        // multiple transformers
        $manager->addTransformer(new GraphQLTransformer())
                ->addTransformer(new CollectionTransformer('entries'));
        $data = $mamager->transform($data);
    }

    public function testList()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/list.json'),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $response = $api->get('list.json');
        $data = $api->decode($response);

        $mapper = new MapCollection([
            '[id]' => '[id]',
            '[postDate]' => '[postDate]',
            '[title]' => '[title]',
        ]);
        $mapper->totalResults('[meta][total]')
                ->resultsPerPage(3)
                ->currentPage('[meta][page]');
        $collection = $mapper->map($data, '[data]');

        $this->assertEquals(10, $collection->getPagination()->getTotalResults());
        $this->assertEquals(3, $collection->getPagination()->getResultsPerPage());
        $this->assertEquals(2, $collection->getPagination()->getPage());
        $this->assertEquals("Test 12", $collection->current()['title']);
        $collection->next();
        $this->assertEquals("Test 13", $collection->current()['title']);
        $collection->next();
        $this->assertEquals("Test 14", $collection->current()['title']);
    }

    public function testExists()
    {
        $responses = [
            new MockResponse('OK'),
            new MockResponse('Error 404', ['http_code' => 404]),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $this->assertTrue($api->exists('status/200'));
        $this->assertFalse($api->exists('status/404'));
    }

    public function testConcurrent()
    {
        $mockResponses = [];
        for ($i = 0; $i < 379; ++$i) {
            $mockResponses[] = new MockResponse('OK');
        }

        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($mockResponses));

        $responses = [];
        for ($i = 0; $i < 379; ++$i) {
            $uri = "file-$i.html";
            $responses[] = $uri;
        }

        /** @var ResponseInterface $response */
        foreach ($api->getConcurrent($responses) as $response) {
        }

        $this->assertEquals('https://example.com/api/file-378.html', $response->getInfo('url'));
        $this->assertEquals(379, $api->getTotalHttpRequests());
    }

    public function testManualConcurrent()
    {
        $mockResponses = [];
        for ($i = 0; $i < 379; ++$i) {
            $mockResponses[] = new MockResponse('OK');
        }

        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($mockResponses));

        $responses = [];
        for ($i = 0; $i < 379; ++$i) {
            $uri = "file-$i.html";
            $responses[] = $api->prepareRequest('GET', $uri);
        }

        foreach ($responses as $response) {
            $response = $api->runRequest($response);
        }

        $this->assertEquals('https://example.com/api/file-378.html', $response->getInfo('url'));
        $this->assertEquals(379, $api->getTotalHttpRequests());
    }


    public function testBasicRestApiFunctions()
    {
        /**
         * Mock HTTP graphql
         *
         * @see ResponseInterface::getInfo()
         */
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/basic-test.json'),
            new MockResponseFromFile(__DIR__ . '/responses/basic-test.json'),
            new MockResponseFromFile(__DIR__ . '/responses/basic-test.json'),
        ];

        $api = new Http('https://example.com/');
        $api->setHttpClient(new MockHttpClient($responses, 'https://example.com/'));

        // GET
        $response = $api->get('test1.json');
        $data = $api->decode($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $data['message']);
        $this->assertEquals(1, $api->getTotalHttpRequests());

        // Headers
        $this->assertEquals('101', $api->getHeader($response, 'X-Total-Results'));
        $this->assertTrue(is_array($api->getHeader($response, 'X-Multiple-Header-Vals')));
        $this->assertEquals('B', $api->getHeader($response, 'X-Multiple-Header-Vals')[1]);
        $this->assertNull($api->getHeader($response, 'X-Fake-Header'));

        $response = $api->get('test2.json', ['page' => 1, 'foo' => 'bar']);
        $this->assertEquals('https://example.com/test2.json?page=1&foo=bar', $response->getInfo('url'));
        $this->assertEquals(2, $api->getTotalHttpRequests());

        // HEAD
        $response = $api->head('test3.json');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $api->getTotalHttpRequests());

        // POST
        $callback = function ($method, $url, $options) {
            parse_str($options['body'], $postData);
            if (!isset($postData['name'])) {
                return new MockResponse('Bad request: name not found', ['http_code' => 400]);
            }
            $body = sprintf('My name is %s', $postData['name']);
            return new MockResponse($body, ['http_code' => 200]);
        };

        $api->setHttpClient(new MockHttpClient($callback));
        $api->suppressErrors();
        $response = $api->post('http://examplecom/test4', ['data' => 'stored']);
        $this->assertEquals(400, $response->getStatusCode());

        $api->setHttpClient(new MockHttpClient($callback));
        $response = $api->post('http://example.com/test4', ['name' => 'John Smith', 'data' => 'stored']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('My name is John Smith', $response->getContent());

        $this->assertEquals(5, $api->getTotalHttpRequests());
    }
}
