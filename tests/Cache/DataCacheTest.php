<?php
declare(strict_types=1);

namespace Cache;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\DataCache;
use Strata\Data\Http\RestApi;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
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

        /** @var MockResponse $response */
        $response = $cache->getResponseFromItem($item, 'GET', 'test1');
        $this->assertTrue($response instanceof ResponseInterface);
        $contents2 = $response->getContent();
        $this->assertEquals($contents1, $contents2);
        $headers = $response->getHeaders();
        $this->assertEquals('BAR', $headers['x-foo'][0]);
    }

    public function testDataCache()
    {
        $responses = [
            new MockResponse('OK 1'),
            new MockResponse('OK 2'),
            new MockResponse('OK 3'),
        ];
        $http = new MockHttpClient($responses);
        $api = new RestApi('http://example.com/');
        $api->setHttpClient($http);
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $api->setCache($adapter);
        $api->enableCache();

        $response = $api->get('test1');
        $contents1 = $response->getContent();

        $response = $api->get('test1');
        $contents2 = $response->getContent();

        $this->assertEquals($contents1, $contents2);
    }

    /**
     * @todo move to HttpAbstract test
     */
    public function testCacheableRequest()
    {
        $api = new RestApi('http://example.com/');
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $api->setCache($adapter);

        $this->assertFalse($api->isCacheableRequest('GET'));

        $api->enableCache();

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

        $this->expectException('Strata\Data\Exception\CacheException');
        $api->setCacheableMethods(['GET', 'MADE UP']);
    }

    public function testHttpRequests()
    {
        $responses = [
            new MockResponse('OK 1'),
            new MockResponse('OK 2'),
            new MockResponse('OK 3'),
        ];
        $http = new MockHttpClient($responses);
        $api = new RestApi('http://example.com/');
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
        $api = new RestApi('http://example.com/');
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
        $api = new RestApi('http://example.com/');
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