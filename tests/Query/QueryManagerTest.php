<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\MockResponseFromFile;
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\QueryManager;
use Symfony\Component\HttpClient\MockHttpClient;

class QueryManagerTest extends TestCase
{
    public function testSimpleQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/landing.json'),
            new MockResponseFromFile(__DIR__ . '/responses/localisation.json'),
        ];

        $manager = new QueryManager();
        $manager->addDataProvider('craft', new GraphQL('https://example.com'));
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