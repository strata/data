<?php

namespace Http;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\DataCache;
use Strata\Data\Http\Response\MockResponseFromFile;
use Strata\Data\Http\Rest;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\MockHttpClient;

class CacheableResponseTest extends TestCase
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

    public function testNonCachedResponse()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
            new MockResponseFromFile(__DIR__ . '/http/query2.json'),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $response = $api->get('test');
        $this->assertFalse($response->isHit());
        $this->assertEquals(200, $response->getStatusCode());
        $item = $api->decode($response);
        $this->assertSame('46', $item['id']);
        $this->assertSame("Test", $item['title']);

        $response = $api->get('test');
        $this->assertFalse($response->isHit());
        $this->assertSame(null, $response->getAge());
        $this->assertEquals(200, $response->getStatusCode());
        $item = $api->decode($response);
        $this->assertSame('47', $item['id']);
        $this->assertSame("Test 2", $item['title']);
    }

    public function testCachedResponse()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/http/query.json'),
            new MockResponseFromFile(__DIR__ . '/http/query2.json'),
        ];
        $api = new Rest('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));
        $api->setCache(new FilesystemAdapter('cache', 0, self::CACHE_DIR));
        $api->enableCache();

        $response = $api->get('test');
        $this->assertFalse($response->isHit());
        $this->assertEquals(200, $response->getStatusCode());
        $item = $api->decode($response);
        $this->assertSame('46', $item['id']);
        $this->assertSame("Test", $item['title']);

        sleep(2);

        $response = $api->get('test');
        $this->assertTrue($response->isHit());
        $this->assertTrue($response->isHit());
        $this->assertSame(2, $response->getAge());
        $this->assertEquals(200, $response->getStatusCode());
        $item = $api->decode($response);
        $this->assertSame('46', $item['id']);
        $this->assertSame("Test", $item['title']);
    }
}
