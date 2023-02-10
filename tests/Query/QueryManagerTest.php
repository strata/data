<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\CacheLifetime;
use Strata\Data\Exception\MissingDataProviderException;
use Strata\Data\Exception\QueryManagerException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\MockResponseFromFile;
use Strata\Data\Http\Rest;
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\Query;
use Strata\Data\Query\QueryManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class QueryManagerTest extends TestCase
{
    const CACHE_DIR = __DIR__ . '/../Cache/cache';

    public function testAddDataProvider()
    {
        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example.com'));

        // No cache
        $this->assertTrue($manager->hasDataProvider('test1'));
        $this->assertFalse($manager->getDataProvider('test1')->isCacheEnabled());
        $this->assertEmpty($manager->getCacheTags());

        // Set cache
        $manager->setCache(new FilesystemAdapter());
        $manager->addDataProvider('test2', new Rest('https://example.com'));
        $this->assertTrue($manager->getDataProvider('test1')->isCacheEnabled());
        $this->assertTrue($manager->getDataProvider('test2')->isCacheEnabled());

        // Disabled cache
        $manager->disableCache();
        $manager->addDataProvider('test3', new Rest('https://example.com'));
        $this->assertFalse($manager->getDataProvider('test3')->isCacheEnabled());
        $this->assertFalse($manager->getDataProvider('test1')->isCacheEnabled());
        $this->assertFalse($manager->getDataProvider('test2')->isCacheEnabled());

        // Cache tags, should throw exception without any taggable cache adapters
        $this->assertEmpty($manager->getCacheTags());
        $this->expectException(QueryManagerException::class);
        $tags = ['tag1', 'tag2', 'tag3'];
        $manager->setCacheTags($tags);
    }

    public function testDisableCache()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 11, new MockResponse('{"message": "OK"}'));

        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        // Set cache
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $adapter->clear();
        $manager->setCache($adapter);
        $this->assertTrue($manager->isCacheEnabled());

        $query1 = (new Query())->setUri('query1')->cache();
        $manager->add('query1', $query1);
        $query2 = (new Query())->setUri('query2')->cache();
        $manager->add('query2', $query2);

        // Disable cache
        $manager->disableCache();
        $this->assertFalse($manager->isCacheEnabled());

        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $manager->clearResponse('query1');
        $manager->clearResponse('query2');

        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $this->assertFalse($response1->isHit());
        $this->assertFalse($response2->isHit());
    }

    // @todo update this method to use Query::doNotCache instead
    public function testDoNotCache()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 11, new MockResponse('{"message": "OK"}'));

        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        // Set cache
        $adapter = new FilesystemAdapter('cache', 0, self::CACHE_DIR);
        $adapter->clear();
        $manager->setCache($adapter);
        $this->assertTrue($manager->isCacheEnabled());

        // Disable cache
        $manager->disableCache();
        $this->assertFalse($manager->isCacheEnabled());

        $query1 = (new Query())->setUri('query1');
        $manager->add('query1', $query1);
        $query2 = (new Query())->setUri('query2')->doNotCache();
        $manager->add('query2', $query2);
        $query3 = (new Query())->setUri('query3')->method('post');
        $manager->add('query3', $query3);

        $this->assertNull($query1->isCacheableRequest());
        $this->assertSame(false, $query2->isCacheableRequest());
        $this->assertNull($query3->isCacheableRequest());

        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $response3 = $manager->getResponse('query3');
        $this->assertFalse($response1->isHit());
        $this->assertFalse($response2->isHit());

        $manager->clearResponse('query1');
        $manager->clearResponse('query2');
        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $this->assertFalse($response1->isHit());
        $this->assertFalse($response2->isHit());

        // Enable cache
        $manager->enableCache();
        $manager->clearResponse('query1');
        $manager->clearResponse('query2');
        $manager->clearResponse('query3');

        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $response3 = $manager->getResponse('query3');
        $this->assertFalse($response1->isHit());
        $this->assertFalse($response2->isHit());
        $this->assertFalse($response3->isHit());

        $manager->clearResponse('query1');
        $manager->clearResponse('query2');
        $manager->clearResponse('query3');

        $response1 = $manager->getResponse('query1');
        $response2 = $manager->getResponse('query2');
        $response3 = $manager->getResponse('query3');
        $this->assertTrue($response1->isHit());
        $this->assertFalse($response2->isHit());
        $this->assertFalse($response3->isHit());
    }

    public function testSetDataProvider()
    {
        $manager = new QueryManager();
        $expected1 = new Rest('https://example.com');
        $manager->addDataProvider('test1', $expected1);
        $expected2 = new GraphQL('https://example.com');
        $manager->addDataProvider('test2', $expected2);

        $this->assertTrue($manager->hasDataProvider('test1'));
        $this->assertSame($expected1, $manager->getDataProvider('test1'));
        $this->assertTrue($manager->hasDataProvider('test2'));
        $this->assertSame($expected2, $manager->getDataProvider('test2'));

        $this->assertFalse($manager->hasDataProvider('test3'));
        $this->expectException(MissingDataProviderException::class);
        $manager->getDataProvider('test3');
    }

    public function testNoDataProvider()
    {
        $manager = new QueryManager();

        $this->expectException(MissingDataProviderException::class);
        $manager->getDataProvider('test1');
    }

    public function testGetDataProviderForQuery()
    {
        $manager = new QueryManager();
        $expected1 = new Rest('https://example1.com');
        $expected2 = new GraphQL('https://example2.com');
        $expected3 = new Rest('https://example3.com');

        $manager->addDataProvider('test1', $expected1);
        $manager->addDataProvider('test2', $expected2);
        $manager->addDataProvider('test3', $expected3);

        $query = new Query();
        $this->assertSame('test1', $manager->getDataProviderNameForQuery($query));

        $query = new GraphQLQuery();
        $this->assertSame('test2', $manager->getDataProviderNameForQuery($query));
    }

    public function testSimpleQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/landing.json'),
            new MockResponseFromFile(__DIR__ . '/responses/localisation.json'),
        ];

        $manager = new QueryManager();
        $manager->addDataProvider('GraphQL', new GraphQL('https://example.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        $query = new GraphQLQuery();
        $query->setGraphQLFromFile(__DIR__ . '/graphql/landing-page.graphql')
            ->setRootPropertyPath('[entry]')
            ->addVariable('slug', 'landing-page');
        $manager->add('query1', $query);

        $query = new GraphQLQuery();
        $query->setGraphQLFromFile(__DIR__ . '/graphql/localisation.graphql')
            ->setRootPropertyPath('[entry]')
            ->addVariable('slug', 'landing-page');
        $manager->add('query2', $query);

        $landing = $manager->get('query1');
        $localistion = $manager->get('query2');

        $this->assertSame('89', $landing['id']);
        $this->assertSame('Landing Page', $landing['title']);
        $this->assertSame("en-US", $localistion['language']);
    }

    public function testMultipleQueryData()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/multiple.json'),
        ];

        $manager = new QueryManager();
        $manager->addDataProvider('test', new Rest('https://example.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        $query = new Query();
        $query->setUri('test')
              ->setRootPropertyPath('[data][entry]');
        $manager->add('query', $query);

        $landing = $manager->get('query');
        $entries = $manager->getCollection('query', '[data][entries]');

        $this->assertSame("https://example.com/landing-page", $landing['url']);
        $this->assertSame("test 1", $entries[0]['name']);
        $this->assertSame("test 2", $entries[1]['name']);
    }

    public function testNoDefaultRootPropertyPath()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/multiple.json'),
        ];

        $manager = new QueryManager();
        $manager->addDataProvider('test', new Rest('https://example.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        $query = new Query();
        $query->setUri('test');
        $manager->add('query', $query);

        $all = $manager->get('query');
        $entries = $manager->getCollection('query', '[data][entries]');

        $this->assertSame("https://example.com/landing-page", $all['data']['entry']['url']);
        $this->assertSame("test 1", $entries[0]['name']);
        $this->assertSame("test 2", $entries[1]['name']);
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
        $manager = new QueryManager();
        $manager->addDataProvider('test', $api);

        $this->assertFalse($manager->isCacheEnabled());
        $manager->setCache($adapter);
        $this->assertTrue($manager->isCacheEnabled());
        $manager->disableCache();

        $query = new Query();
        $query->setUri('test');
        $manager->add('test', $query);

        // Responses should be different since cache disabled
        $data1 = $manager->get('test');

        $manager->clearResponse('test');
        $this->assertNotSame($data1, $manager->get('test'));
        $this->assertFalse($manager->isHit('test'));

        $manager->clearResponse('test');
        $this->assertNotSame($data1, $manager->get('test'));
        $this->assertFalse($manager->isHit('test'));

        // Responses should be identical since cache enabled
        $manager->enableCache();
        $manager->clearResponse('test');
        $query->cache(CacheLifetime::HOUR);

        $data4 = $manager->get('test');
        $this->assertFalse($manager->isHit('test'));

        $manager->clearResponse('test');
        $this->assertSame($data4, $manager->get('test'));
        $this->assertTrue($manager->isHit('test'));

        $manager->clearResponse('test');
        $this->assertSame($data4, $manager->get('test'));
        $this->assertTrue($manager->isHit('test'));
    }

    public function testGetQueries()
    {
        $responses = [
            new MockResponse('{"message": "OK 1"}'),
            new MockResponse('{"message": "OK 2"}'),
            new MockResponse('{"message": "OK 3"}'),
        ];

        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example1.com'));
        $manager->addDataProvider('test2', new Rest('https://example2.com'));
        $manager->setHttpClient(new MockHttpClient($responses));

        $query = new Query();
        $query->setUri('test');
        $manager->add('query1', $query, 'test1');

        $query = new Query();
        $query->setUri('test');
        $manager->add('query2', $query, 'test2');

        $query = new Query();
        $query->setUri('test2');
        $manager->add('query3', $query, 'test2');

        $this->assertSame(3, count($manager->getQueries()));
        $this->assertSame(1, count($manager->getDataProviderQueries('test1')));
        $this->assertSame(2, count($manager->getDataProviderQueries('test2')));
    }

    public function testSharedHttpClient()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 8, new MockResponse('{"message": "OK"}'));

        // Test separate HTTP clients
        $manager = new QueryManager();
        $rest1 = new Rest('https://example1.com/');
        $manager->addDataProvider('test1', $rest1);
        $rest2 = new Rest('https://example2.com/');
        $manager->addDataProvider('test2', $rest2);
        $manager->addDataProvider('test3', new Rest('https://example3.com/'));

        $this->assertFalse($manager->getDataProvider('test1')->hasHttpClient());
        $this->assertFalse($manager->getDataProvider('test2')->hasHttpClient());
        $this->assertFalse($manager->getDataProvider('test3')->hasHttpClient());

        // Test base URI
        $rest1->setHttpClient(new MockHttpClient($responses));
        $request =  $rest1->prepareRequest('GET', 'test1');
        $this->assertSame('https://example1.com/test1', $request->getInfo('url'));

        $rest2->setHttpClient(new MockHttpClient($responses));
        $request =  $rest2->prepareRequest('GET', 'test1');
        $this->assertSame('https://example2.com/test1', $request->getInfo('url'));

        $this->assertTrue($manager->getDataProvider('test1')->hasHttpClient());
        $this->assertTrue($manager->getDataProvider('test2')->hasHttpClient());
        $this->assertFalse($manager->getDataProvider('test3')->hasHttpClient());
        $this->assertNotSame($manager->getDataProvider('test1')->getHttpClient(), $manager->getDataProvider('test2')->getHttpClient());

        // Test shared HTTP clients
        $manager->shareHttpClient();
        $this->assertTrue($manager->getDataProvider('test1')->hasHttpClient());
        $this->assertTrue($manager->getDataProvider('test2')->hasHttpClient());
        $this->assertTrue($manager->getDataProvider('test3')->hasHttpClient());
        $this->assertSame($manager->getDataProvider('test1')->getHttpClient(), $manager->getDataProvider('test2')->getHttpClient());
        $this->assertSame($manager->getDataProvider('test3')->getHttpClient(), $manager->getDataProvider('test2')->getHttpClient());

        // Test base URI
        $request =  $rest1->prepareRequest('GET', 'test2');
        $this->assertSame('https://example1.com/test2', $request->getInfo('url'));
        $request =  $rest2->prepareRequest('GET', 'test2');
        $this->assertSame('https://example2.com/test2', $request->getInfo('url'));
        $rest3 = $manager->getDataProvider('test3');
        $request = $rest3->prepareRequest('GET', 'test2');
        $this->assertSame('https://example3.com/test2', $request->getInfo('url'));

        // Test again
        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example1.com/'));
        $manager->addDataProvider('test2', new Rest('https://example2.com/'));
        $manager->addDataProvider('test3', new Rest('https://example3.com/'));
        $rest1 = $manager->getDataProvider('test1');
        $rest1->setHttpClient(new MockHttpClient($responses));
        $manager->shareHttpClient();

        $this->assertTrue($manager->getDataProvider('test1')->hasHttpClient());
        $this->assertTrue($manager->getDataProvider('test2')->hasHttpClient());
        $this->assertTrue($manager->getDataProvider('test3')->hasHttpClient());
        $this->assertSame($manager->getDataProvider('test1')->getHttpClient(), $manager->getDataProvider('test2')->getHttpClient());
        $this->assertSame($manager->getDataProvider('test3')->getHttpClient(), $manager->getDataProvider('test2')->getHttpClient());

        // Test base URI
        $rest2 = $manager->getDataProvider('test2');
        $rest3 = $manager->getDataProvider('test3');
        $request =  $rest1->prepareRequest('GET', 'test2');
        $this->assertSame('https://example1.com/test2', $request->getInfo('url'));
        $request =  $rest2->prepareRequest('GET', 'test2');
        $this->assertSame('https://example2.com/test2', $request->getInfo('url'));
        $request = $rest3->prepareRequest('GET', 'test2');
        $this->assertSame('https://example3.com/test2', $request->getInfo('url'));
    }

    public function testGetDataCollector()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 4, new MockResponse('{"message": "OK"}'));

        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example1.com/'));
        $manager->addDataProvider('test2', new Rest('https://example2.com/'));
        $rest1 = $manager->getDataProvider('test1');
        $rest1->setHttpClient(new MockHttpClient($responses));
        $manager->shareHttpClient();

        // Queries
        $query1 = (new Query())->setUri('path1');
        $query2 = (new Query())->setUri('path2');
        $manager->add('test1', $query1, 'test1');
        $manager->add('test2', $query2, 'test2');

        $data = $manager->getDataCollector();
        $this->assertSame(0, $data['total']);
        $this->assertSame(0, $data['cached']);

        $response = $manager->get('test1');
        $data = $manager->getDataCollector();
        $this->assertSame(2, $data['total']);
        $this->assertSame(0, $data['cached']);

        $query3 = (new Query())->setUri('path3');
        $query4 = (new Query())->setUri('path4');
        $manager->add('test3', $query3, 'test1');
        $manager->add('test4', $query4, 'test1');
        $data = $manager->getDataCollector();
        $this->assertSame(2, $data['total']);

        $response = $manager->get('test3');
        $data = $manager->getDataCollector();
        $this->assertSame(4, $data['total']);
    }

    public function testAddQueries()
    {
        $responses = array_fill(0, 3, new MockResponse('{"message": "OK"}'));
        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example1.com/'));
        $rest1 = $manager->getDataProvider('test1');
        $rest1->setHttpClient(new MockHttpClient($responses));

        $manager->addQueries([
            'query1' => (new Query())->setUri('path1'),
            'query2' => (new Query())->setUri('path2')->concurrent(false),
            '0'      => (new Query())->setUri('path3'),
        ]);

        $this->assertSame(2, count($manager->getQueries()));
        $this->assertTrue($manager->hasQuery('query1'));
        $this->assertTrue($manager->hasQuery('query2'));
        $this->assertFalse($manager->hasQuery('query3'));

        $manager->addQueries([
            (new Query())->setUri('path1'),
            (new Query())->setUri('path2')->concurrent(false),
        ]);

        $this->assertSame(2, count($manager->getQueries()));
    }

    public function testNonConcurrentQueries()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 3, new MockResponse('{"message": "OK"}'));

        $manager = new QueryManager();
        $manager->addDataProvider('test1', new Rest('https://example1.com/'));
        $rest1 = $manager->getDataProvider('test1');
        $rest1->setHttpClient(new MockHttpClient($responses));

        $manager->addQueries([
            'query1' => (new Query())->setUri('path1'),
            'query2' => (new Query())->setUri('path2')->concurrent(false),
            'query3' => (new Query())->setUri('path3'),
        ]);

        $this->assertTrue($manager->hasQuery('query1'));
        $this->assertTrue($manager->hasQuery('query2'));
        $this->assertTrue($manager->hasQuery('query3'));
        $this->assertTrue($manager->getQuery('query1')->isConcurrent());
        $this->assertFalse($manager->getQuery('query2')->isConcurrent());
        $this->assertTrue($manager->getQuery('query3')->isConcurrent());
        $this->assertFalse($manager->getQuery('query1')->hasResponseRun());
        $this->assertFalse($manager->getQuery('query2')->hasResponseRun());
        $this->assertFalse($manager->getQuery('query3')->hasResponseRun());

        $data = $manager->get('query1');
        $this->assertTrue($manager->getQuery('query1')->hasResponseRun());
        $this->assertTrue($manager->getQuery('query3')->hasResponseRun());
        $this->assertFalse($manager->getQuery('query2')->hasResponseRun());

        $manager->clearResponse('query1');
        $manager->clearResponse('query3');
        $this->assertFalse($manager->getQuery('query1')->hasResponseRun());
        $this->assertFalse($manager->getQuery('query3')->hasResponseRun());

        $data = $manager->get('query2');
        $this->assertFalse($manager->getQuery('query1')->hasResponseRun());
        $this->assertTrue($manager->getQuery('query2')->hasResponseRun());
        $this->assertFalse($manager->getQuery('query3')->hasResponseRun());
    }

    public function testSwitchHttpHeaders()
    {
        // Create a bunch of mock responses
        $responses = array_fill(0, 3, new MockResponse('{"message": "OK"}'));

        $manager = new QueryManager();
        $rest1 = new Rest('https://example1.com/');
        $rest1->setDefaultOptions([
            'auth_bearer' => 'ABC123'
        ]);
        $rest1->setHttpClient(new MockHttpClient($responses));
        $manager->addDataProvider('test1', $rest1);

        $manager->add('query1', (new Query())->setUri('path1'));
        $manager->add('query2', (new Query())->setUri('path2')->setOptions([
            'auth_bearer' => 'DEF456'
        ]));
        $manager->add('query3', (new Query())->setUri('path3'));

        $mockRequest = $manager->getResponse('query1')->getDecorated();
        $authorizationRequestHeader = $mockRequest->getRequestOptions()['normalized_headers']['authorization'][0];
        $this->assertSame('Authorization: Bearer ABC123', $authorizationRequestHeader);

        $mockRequest = $manager->getResponse('query2')->getDecorated();
        $authorizationRequestHeader = $mockRequest->getRequestOptions()['normalized_headers']['authorization'][0];
        $this->assertSame('Authorization: Bearer DEF456', $authorizationRequestHeader);

        $mockRequest = $manager->getResponse('query3')->getDecorated();
        $authorizationRequestHeader = $mockRequest->getRequestOptions()['normalized_headers']['authorization'][0];
        $this->assertSame('Authorization: Bearer ABC123', $authorizationRequestHeader);
    }
}
