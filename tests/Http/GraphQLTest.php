<?php

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\GraphQLException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

class GraphQLTest extends TestCase
{
    public function testDefaultHttpOptions()
    {
        $graphQL = new GraphQL('https://example.com/api');
        $options = $graphQL->getCurrentDefaultOptions();
        $this->assertSame('application/json', $options['headers']['Content-Type']);
    }

    /**
     * ID is based on method, URI, query params and GraphQL query body
     */
    public function testRequestIdentifier()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/graphql/ping.json'),
            new MockResponseFromFile(__DIR__ . '/graphql/ping.json'),
            new MockResponseFromFile(__DIR__ . '/graphql/ping.json'),
            new MockResponseFromFile(__DIR__ . '/graphql/ping.json'),
        ];
        $api = new GraphQL('https://example.com/api/');
        $api->setHttpClient(new MockHttpClient($responses));

        $query = 'query { entries(section: "news", limit: 2) { id } }';
        $response = $api->query($query);
        $expected = ContentHasher::hash('GET https://example.com/api/test ' . $query);
        $options = $api->mergeHttpOptions($api->getCurrentDefaultOptions(), ['body' => $query]);
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $options));

        $query = 'query { entries(section: "news", limit: 2, page: $id) { id } }';
        $variables = ['page' => 42];
        $response = $api->query('test', $variables);
        $query = 'query { entries(section: "news", limit: 2, page: $id) { id } } variables { id: 42}';
        $expected = ContentHasher::hash('GET https://example.com/api/test ' . $query);
        $options = $api->mergeHttpOptions($api->getCurrentDefaultOptions(), ['body' => $query]);
        $this->assertSame($expected, $api->getRequestIdentifier('GET ' . $api->getUri('test'), $options));
    }

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

        $this->expectException('\Strata\Data\Exception\HttpNotFoundException');
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
        } catch (GraphQLException $e) {
            $foundException = true;
            $this->assertSame('GraphQL errors: Syntax Error: Unexpected Name "invalid" on line 1, column 1.', $e->getMessage());
            $this->assertEquals('graphql', $e->getResponseErrorData()[0]['category']);
            $this->assertStringContainsString('Request: POST https://example.com/api', $e->getRequestTrace());
            $this->assertStringContainsString('Request headers: Content-Type: application/json', $e->getRequestTrace());
            $this->assertStringContainsString('Response status: 200', $e->getRequestTrace());
            $this->assertStringContainsString('GraphQL error: Syntax Error: Unexpected Name "invalid"', $e->getRequestTrace());
            $expected = <<<EOD
{
    "query": "invalid"
}
EOD;

            $this->assertSame($expected, $e->getLastQuery());
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
        $graphQL->setHttpClient(new MockHttpClient($responses));

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
        $this->assertEquals(8, $item['entries'][0]['id']);
    }
}
