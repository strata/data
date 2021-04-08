<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\FailedGraphQLException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

class GraphQLTest extends TestCase
{
    /**
     * Test valid GraphQL query building
     */
    public function testBuildQuery()
    {
        // ping
        $graphQL = new GraphQL('https://example.com/api');
        $query = '{ping}';
        $expected = <<<EOD
{
    "query": "{ping}"
}
EOD;
        $this->assertEquals($expected, $graphQL->buildQuery($query));

        // double-quotes
        $query = 'query { entries(section: "news", limit: 2) { id } }';
        $expected = <<<EOD
{
    "query": "query { entries(section: \"news\", limit: 2) { id } }"
}
EOD;
        $this->assertEquals($expected, $graphQL->buildQuery($query));

        // line returns
        $query = <<<EOD
query { 
  entries(section: "news", limit: 2) { 
    id 
  }
}
EOD;
        $expected = <<<EOD
{
    "query": "query { entries(section: \"news\", limit: 2) { id } }"
}
EOD;
        $this->assertEquals($expected, $graphQL->buildQuery($query));

        // variables
        $query = <<<'EOD'
query ($offset: Int) { 
  entries(section: "news", limit: 2, offset: $offset) { 
    id 
  }
}
EOD;
        $expected = <<<'EOD'
{
    "query": "query ($offset: Int) { entries(section: \"news\", limit: 2, offset: $offset) { id } }",
    "variables": {
        "offset": 10
    }
}
EOD;
        $this->assertEquals($expected, $graphQL->buildQuery($query, ["offset" => 10]));
    }

    /**
     * Test mock GraphQL graphql
     */
    public function testPing()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/ping.json'),
            new MockResponseFromFile(__DIR__ . '/graphql/invalid-ping.json'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setHttpClient(new MockHttpClient($responses));

        $this->assertTrue($graphQL->ping());

        $this->expectException('\Strata\Data\Exception\NotFoundException');
        $graphQL->ping();
    }

    /**
     * Test mock GraphQL graphql
     */
    public function testSuppressErrorsViaSubRequest()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/invalid-ping.json'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setHttpClient(new MockHttpClient($responses));
        $graphQL->suppressErrors();

        $this->assertFalse($graphQL->ping());
    }

    public function testGraphQLError()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/invalid.json'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setHttpClient(new MockHttpClient($responses));
        $foundException = false;

        try {
            $response = $graphQL->query('invalid');
        } catch (FailedGraphQLException $e) {
            $foundException = true;
            $this->assertStringContainsString('Syntax Error', $e->getMessage());
            $this->assertEquals('graphql', $e->getErrorData()[0]['category']);
        }

        $this->assertTrue($foundException);
    }

    public function testQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/query.json'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setHttpClient(new MockHttpClient($responses));

        // Simple query
        $query = '
query {
  entries(section: "news", limit: 2) {
    id
    postDate
    title
  }
}';
        $response = $graphQL->query($query);
        $results = $graphQL->decode($response);
        $this->assertEquals(257, $results['entries'][0]['id']);
        $this->assertEquals("The super-duper business event", $results['entries'][0]['title']);
    }

    public function testVariables()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/query.json'),
            new MockResponseFromFile(__DIR__ . '/graphql/query2.json'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setHttpClient(new MockHttpClient($responses,));

        $query = <<<'EOD'
query ($offset: Int) {
  entries(section: "news", limit: 2, offset: $offset) {
    id,
    postDate,
    title
  }
}
EOD;
        $response = $graphQL->query($query, ['offset' => 0]);
        $item = $graphQL->decode($response);
        $this->assertEquals(2, count($item['entries']));
        $this->assertEquals(257, $item['entries'][0]['id']);

        $response = $graphQL->query($query, ['offset' => 2]);
        $item = $graphQL->decode($response);
        $this->assertEquals(1, count($item['entries']));
        $this->assertEquals(8,$item['entries'][0]['id']);
    }

}
