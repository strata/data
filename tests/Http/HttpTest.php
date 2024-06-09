<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\DataCache;
use Strata\Data\Exception\InvalidHttpMethodException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Http\Http;
use Strata\Data\Http\Rest;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpTest extends TestCase
{
    const CACHE_DIR = __DIR__ . '/cache';

    /**
     * This method is called after each test.
     *
     * Delete cache files
     */
    protected function tearDown(): void
    {
        $cache = new DataCache(new FilesystemAdapter('cache', 0, self::CACHE_DIR));
        $cache->clear();
    }

    public function testValidMethod()
    {
        $this->assertTrue(Http::validMethod('GET'));
        $this->assertTrue(Http::validMethod('HEAD'));
        $this->assertTrue(Http::validMethod('POST'));
        $this->assertTrue(Http::validMethod('PUT'));
        $this->assertTrue(Http::validMethod('DELETE'));
        $this->assertTrue(Http::validMethod('CONNECT'));
        $this->assertTrue(Http::validMethod('OPTIONS'));
        $this->assertTrue(Http::validMethod('PATCH'));
        $this->assertTrue(Http::validMethod('PURGE'));
        $this->assertTrue(Http::validMethod('TRACE'));

        $this->assertFalse(Http::validMethod('FOO'));
        $this->assertFalse(Http::validMethod('GETPOST'));

        $this->assertTrue(Http::validMethod('get'));
        $this->assertTrue(Http::validMethod('post'));
        $this->assertTrue(Http::validMethod('put'));

        $this->assertTrue(Http::validMethod(['get', 'post']));
        $this->assertTrue(Http::validMethod(['get', 'options', 'purge', 'post']));
    }

    public function testInvalidMethod()
    {
        $this->expectException(\TypeError::class);
        Http::validMethod(99);
    }

    public function testInvalidMethodException()
    {
        $this->expectExceptionMessage('Invalid HTTP method/s passed: MADE UP, FOO');
        Http::validMethod(['GET', 'HEAD', 'MADE UP', 'FOO'], true);
    }

    public function testDefaultOptions()
    {
        $api = new Http('https://example.com/api/');

        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent()
            ]
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        $api->setDefaultOptions(['query' => ['foo' => 'bar']]);
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent(),
            ],
            'query' => [
                'foo' => 'bar'
            ]
        ];
        $test = $api->getCurrentDefaultOptions();
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        $api->setDefaultOptions(['headers' => ['Authorization' => 'testing123']]);
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent(),
                'Authorization' => 'testing123'
            ],
            'query' => [
                'foo' => 'bar'
            ]
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        $api->removeDefaultOption('query');
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent(),
                'Authorization' => 'testing123'
            ]
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        $api->removeDefaultOption(['headers', 'Authorization']);
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent()
            ]
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        // test replacement
        $api->setDefaultOptions([
            'auth_bearer' => 'ABC123'
        ]);
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent()
            ],
            'auth_bearer' => 'ABC123'
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());

        $api->setDefaultOptions([
            'auth_bearer' => 'DEF456'
        ]);
        $expected = [
            'headers' => [
                'User-Agent' => $api->getUserAgent()
            ],
            'auth_bearer' => 'DEF456'
        ];
        $this->assertSame($expected, $api->getCurrentDefaultOptions());
    }

    public function testUserAgent()
    {
        $api = new Http('https://example.com/api/');
        $version = $api->getUserAgent();

        $this->assertStringContainsString('Strata_Data', $version);
        $this->assertStringContainsString('(+https://github.com/strata/data)', $version);
    }

    /**
     * ID is based on method, URI and query params
     */
    public function testRequestIdentifier()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
        ];
        $api = new Http('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $response = $api->get('test');
        $expected = ContentHasher::hash('GET https://example.com/api/test');
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $api->getCurrentDefaultOptions()));

        $query = ['apikey' => 'ABC123'];
        $response = $api->get('test', $query);
        $expected = ContentHasher::hash('GET https://example.com/api/test?' . http_build_query($query));
        $options = $api->mergeHttpOptions($api->getCurrentDefaultOptions(), ['query' => $query]);
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $options));

        $query = ['foo' => 'bar', 'page' => 2];
        $response = $api->get('test', $query);
        $expected = ContentHasher::hash('GET https://example.com/api/test?' . http_build_query($query));
        $options = $api->mergeHttpOptions($api->getCurrentDefaultOptions(), ['query' => $query]);
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $options));

        $options = ['headers' => ['X-Foo' => 'Bar'], 'auth_basic' => 'user:pass'];
        $response = $api->get('test', $query, $options);
        $expected = ContentHasher::hash('GET https://example.com/api/test?' . http_build_query($query));
        $options = $api->mergeHttpOptions($api->getCurrentDefaultOptions(), ['query' => $query]);
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $options));
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
        $mapper->setTotalResults('[meta][total]')
                ->setResultsPerPage(3)
                ->setCurrentPage('[meta][page]');
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

        /** @phpstan-ignore-next-line */
        $this->assertEquals('https://example.com/api/file-378.html', $response->getInfo('url'));
        $this->assertEquals(379, $api->getTotalHttpRequests());
    }

    public function testConcurrentWithOptions()
    {
        $mockResponses = [];
        for ($i = 0; $i < 379; ++$i) {
            $mockResponses[] = new MockResponse('OK');
        }

        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($mockResponses));

        $requests = [];
        for ($i = 0; $i < 379; ++$i) {
            $uri = "file-$i.html";
            $requests[] = [
                'uri' => $uri,
                'options' => [
                    'query' => ['page' => $i, 'foo' => 'bar']
                ]
            ];
        }

        /** @var ResponseInterface $response */
        foreach ($api->getConcurrent($requests) as $response) {
        }

        /** @phpstan-ignore-next-line */
        $this->assertEquals('https://example.com/api/file-378.html?page=378&foo=bar', $response->getInfo('url'));
        $this->assertEquals(379, $api->getTotalHttpRequests());
    }

    /**
     * Note: live test of this with example used on https://symfony.com/doc/current/http_client.html#concurrent-requests
     * returns times of:
     *
     * Concurrent: 0.69 to 2.2 seconds for 380 requests
     * Not concurrent (multiple get() requests): 5.4 to 6.8 seconds for 380 requests
     */
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

    public function testDefaultCache()
    {
        $api = new Rest('http://example.com/');
        $api->enableCache();

        $this->assertTrue($api->isCacheEnabled());
        $this->assertEquals(60*60, $api->getCache()->getLifetime());
    }

    public function testStatusMethods()
    {
        $responses = [
            new MockResponse('OK'),
            new MockResponse('ERROR', ['http_code' => 500]),
            new MockResponse('REDIRECT', ['http_code' => 301, 'redirect_url' => 'http://example.com/new-url']),
        ];

        $api = new Rest('http://example.com/');
        $api->setHttpClient(new MockHttpClient($responses));
        $api->suppressErrors();

        $response = $api->get('test');
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isFailed());
        $this->assertFalse($response->isRedirect());

        $response = $api->get('failed');
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isFailed());
        $this->assertFalse($response->isRedirect());

        $response = $api->get('redirect');
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isFailed());
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('http://example.com/new-url', $response->getRedirectUrl());
    }

    public function testCacheableRequest()
    {
        $api = new Rest('http://example.com/');
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $adapter->clear();

        $this->assertFalse($api->isCacheEnabled());
        $api->setCache($adapter);
        $this->assertTrue($api->isCacheEnabled());

        // Default settings
        $this->assertTrue($api->isCacheableRequest('GET'));
        $this->assertTrue($api->isCacheableRequest('HEAD'));
        $this->assertFalse($api->isCacheableRequest('POST'));
        $this->assertFalse($api->isCacheableRequest('PUT'));
        $this->assertFalse($api->isCacheableRequest('DELETE'));
        $this->assertFalse($api->isCacheableRequest('CONNECT'));
        $this->assertFalse($api->isCacheableRequest('OPTIONS'));
        $this->assertFalse($api->isCacheableRequest('PATCH'));
        $this->assertFalse($api->isCacheableRequest('PURGE'));
        $this->assertFalse($api->isCacheableRequest('TRACE'));

        $api->disableCache();
        $this->assertFalse($api->isCacheableRequest('GET'));

        $api->enableCache();
        $api->setCacheableMethods(['GET', 'POST']);
        $this->assertTrue($api->isCacheableRequest('GET'));
        $this->assertTrue($api->isCacheableRequest('POST'));
        $this->assertFalse($api->isCacheableRequest('HEAD'));
    }
}
