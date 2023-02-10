<?php

declare(strict_types=1);

namespace Cache;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\DataCache;
use Strata\Data\Exception\InvalidHttpMethodException;
use Strata\Data\Http\Rest;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataCacheTest extends TestCase
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

    public function testBasicCache()
    {
        $data = [
            'ABC123',
        ];
        $cache = new DataCache(new FilesystemAdapter('cache', 0, self::CACHE_DIR));

        $item = $cache->getItem('data_0');
        $this->assertFalse($item->isHit());

        $item->set($data[0]);
        $this->assertTrue($cache->save($item));

        // Cache defaults to one hour lifetime
        $item = $cache->getItem('data_0');
        $this->assertTrue($item->isHit());
        $this->assertEquals($data[0], $item->get());
    }

    public function testResponseCache()
    {
        $responses = [
            new MockResponse('OK 1', ['response_headers' => ['X-FOO' => 'BAR']]),
        ];
        $http = new MockHttpClient($responses, 'https://example.com');
        $cache = new DataCache(new FilesystemAdapter('cache', 0, self::CACHE_DIR));

        $item = $cache->getItem('test1');
        $this->assertFalse($item->isHit());

        $response = $http->request('GET', 'test1');
        $contents1 = $response->getContent();
        $item = $cache->setResponseToItem($item, $response);
        $this->assertTrue($cache->save($item));

        $item = $cache->getItem('test1');
        $this->assertTrue($item->isHit());

        sleep(2);

        /** @var MockResponse $response */
        $response = $cache->getResponseFromItem($item, 'GET', 'test1');
        $this->assertTrue($response instanceof ResponseInterface);
        $contents2 = $response->getContent();
        $this->assertEquals($contents1, $contents2);
        $headers = $response->getHeaders();
        $this->assertEquals('BAR', $headers['x-foo'][0]);

        // Cache age
        $age = $headers['x-cache-age'][0];
        $this->assertTrue(is_numeric($age));
        $this->assertSame('2', $age);
    }

    public function testDataCache()
    {
        $responses = [
            new MockResponse('OK 1'),
            new MockResponse('OK 2'),
            new MockResponse('OK 3'),
        ];
        $http = new MockHttpClient($responses);
        $api = new Rest('http://example.com/');
        $api->setHttpClient($http);
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);

        $api->setCache($adapter);
        $api->disableCache();

        $response = $api->get('test1');
        $contents1 = $response->getContent();

        $response = $api->get('test1');
        $contents2 = $response->getContent();

        $this->assertNotEquals($contents1, $contents2);

        // Enable cache
        $api->enableCache();

        $response = $api->get('test1');
        $contents1 = $response->getContent();

        $response = $api->get('test1');
        $contents2 = $response->getContent();

        $this->assertEquals($contents1, $contents2);
    }

    public function testHttpRequests()
    {
        $responses = [
            new MockResponse('OK 1'),
            new MockResponse('OK 2'),
            new MockResponse('OK 3'),
        ];
        $http = new MockHttpClient($responses);
        $api = new Rest('http://example.com/');
        $api->setHttpClient($http);
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $api->setCache($adapter);

        $api->enableCache();

        $response = $api->get('test1');
        $this->assertFalse($response->isHit());
        $contents1 = $response->getContent();

        $response = $api->get('test1');
        $this->assertTrue($response->isHit());

        $contents2 = $response->getContent();
        $this->assertEquals($contents1, $contents2);

        $response = $api->get('test2');
        $this->assertFalse($response->isHit());
        $contents3 = $response->getContent();
        $this->assertNotEquals($contents1, $contents3);
        $this->assertEquals('OK 2', $contents3);

        $response = $api->get('test2');
        $this->assertTrue($response->isHit());
        $this->assertEquals('OK 2', $response->getContent());

        $api->disableCache();
        $response = $api->get('test2');
        $this->assertFalse($response->isHit());
        $this->assertEquals('OK 3', $response->getContent());
    }

    public function testTagsNotSupported()
    {
        $api = new Rest('http://example.com/');
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $api->setCache($adapter);

        $this->expectException('Strata\Data\Exception\CacheException');
        $api->setCacheTags(['test-tag']);
    }

    public function testTags()
    {
        $responses = [
            new MockResponse('OK 1'),
            new MockResponse('OK 2'),
        ];
        $http = new MockHttpClient($responses);
        $api = new Rest('http://example.com/');
        $api->setHttpClient($http);
        $adapter = new FilesystemTagAwareAdapter('cache', 0, self::CACHE_DIR);
        $api->setCache($adapter);

        $api->enableCache();

        $api->setCacheTags(['test-tag']);

        $response = $api->get('test1');
        $this->assertFalse($response->isHit());

        $response = $api->get('test1');
        $this->assertTrue($response->isHit());

        $api->getCache()->invalidateTags(['test-tag']);

        $response = $api->get('test1');
        $this->assertFalse($response->isHit());
        $this->assertEquals('OK 2', $response->getContent());
    }
}
