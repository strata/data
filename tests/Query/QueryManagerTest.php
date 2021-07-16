<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Http;
use Strata\Data\Http\Response\MockResponseFromFile;
use Strata\Data\Http\Rest;
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\Query;
use Strata\Data\Query\QueryManager;
use Strata\Data\Query\QueryStack;
use Symfony\Component\HttpClient\MockHttpClient;

class QueryManagerTest extends TestCase
{
    public function testDataProviderSupportsQuery()
    {
        $manager = new QueryManager();
        $manager->addDataProvider('Rest', new Rest('https://example.com/'));
        $manager->addDataProvider('GraphQL', new GraphQL('https://example.com/'));

        $this->assertTrue($manager->dataProviderSupportsQuery('GraphQL', new GraphQLQuery()));
        $this->assertFalse($manager->dataProviderSupportsQuery('GraphQL', new Query()));
        $this->assertFalse($manager->dataProviderSupportsQuery('Rest', new GraphQLQuery()));
        $this->assertTrue($manager->dataProviderSupportsQuery('Rest', new Query()));
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

        $query = new GraphQLQuery('landing', __DIR__ . '/graphql/landing-page.graphql');
        $query->addVariable('slug', 'landing-page');
        $manager->add($query);

        $query = new GraphQLQuery('localisation', __DIR__ . '/graphql/localisation.graphql');
        $query->addVariable('slug', 'landing-page');
        $manager->add($query);

        $landing = $manager->getItem('landing', '[entry]');
        $localistion = $manager->getItem('localisation', '[entry]');

        $this->assertSame('89', $landing['id']);
        $this->assertSame('Landing Page', $landing['title']);
        $this->assertSame("en-US", $localistion['language']);
    }

}