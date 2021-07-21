<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\MissingDataProviderException;
use Strata\Data\Exception\QueryManagerException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\MockResponseFromFile;
use Strata\Data\Http\Rest;
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\Query;
use Strata\Data\Query\QueryManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\MockHttpClient;

class QueryManagerTest extends TestCase
{

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
        $this->assertTrue($manager->getDataProvider('test2')->isCacheEnabled());
        $this->assertTrue($manager->getDataProvider('test1')->isCacheEnabled());

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
        $this->assertSame($expected1, $manager->getDataProviderForQuery($query));

        $query = new GraphQLQuery();
        $this->assertSame($expected2, $manager->getDataProviderForQuery($query));
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
}
