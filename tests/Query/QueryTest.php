<?php

namespace Query;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\CacheLifetime;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Rest;
use Strata\Data\Query\Query;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class QueryTest extends TestCase
{
    const CACHE_DIR = __DIR__ . '/../Cache/cache';

    public function testSetDataProvider()
    {
        $query = new Query();
        $this->assertSame(Rest::class, $query->getRequiredDataProviderClass());

        $query->setDataProvider(new Rest('https://example.com'));
        $this->assertSame(Rest::class, get_class($query->getDataProvider()));

        $this->expectException(QueryException::class);
        $query->setDataProvider(new GraphQL('https://example.com'));
    }

    public function testRepeatQuery()
    {
        $responses = [
            new MockResponse('{"message": "OK 1"}'),
            new MockResponse('{"message": "OK 2"}'),
            new MockResponse('{"message": "OK 3"}'),
        ];
        $http = new MockHttpClient($responses);

        $api = new Rest('https://example.com/');
        $api->setHttpClient($http);

        $query = new Query();
        $query->setUri('test')
              ->setDataProvider($api)
        ;

        // Responses should be different
        $data1 = $query->get();
        $this->assertSame('OK 1', $data1['message']);

        $data2 = $query->get();
        $this->assertSame('OK 1', $data2['message']);

        $query->clearResponse();
        $data3 = $query->get();
        $this->assertSame('OK 2', $data3['message']);

        $query->clearResponse();
        $data4 = $query->get();
        $this->assertSame('OK 3', $data4['message']);
    }

    public function testQueryWithCache()
    {
        $responses = [
            new MockResponse('{"message": "OK 1"}'),
            new MockResponse('{"message": "OK 2"}'),
            new MockResponse('{"message": "OK 3"}'),
            new MockResponse('{"message": "OK 4"}'),
            new MockResponse('{"message": "OK 5"}'),
            new MockResponse('{"message": "OK 6"}'),
        ];
        $http = new MockHttpClient($responses);

        $api = new Rest('https://example.com/');
        $api->setHttpClient($http);

        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $adapter->clear();

        $api->setCache($adapter);
        $api->disableCache();

        $query = new Query();
        $query->setUri('test')
              ->setDataProvider($api)
        ;

        // Responses should be different since cache disabled
        $data1 = $query->get();

        $query->clearResponse();
        $this->assertNotSame($data1, $query->get());
        $this->assertFalse($query->isHit());

        $query->clearResponse();
        $this->assertNotSame($data1, $query->get());
        $this->assertFalse($query->isHit());

        // Responses should be identical since cache enabled
        $query->clearResponse();
        $query->cache(CacheLifetime::HOUR);

        $data4 = $query->get();
        $this->assertFalse($query->isHit());

        $query->clearResponse();
        $this->assertSame($data4, $query->get());
        $this->assertTrue($query->isHit());

        $query->clearResponse();
        $this->assertSame($data4, $query->get());
        $this->assertTrue($query->isHit());

        // Cache should be disabled, since Query should reset this
        $this->assertFalse($api->isCacheEnabled());
    }
}
