<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\GraphQL;
use Strata\Data\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

class GraphQLTest extends TestCase
{
    public function testBuildQuery()
    {
        // ping
        $graphQL = new GraphQL();
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

    public function testPing()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/ping'),
            new MockResponseFromFile(__DIR__ . '/responses/invalid-ping'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setClient(new MockHttpClient($responses, $graphQL->getBaseUri()));

        $this->assertTrue($graphQL->ping());
        $this->assertFalse($graphQL->ping());
    }

    public function testNoBaseUri()
    {
        $graphQL = new GraphQL();

        $this->expectException('Strata\Data\Exception\BaseUriException');
        $graphQL->ping();
    }

    public function testError()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/invalid'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setClient(new MockHttpClient($responses, $graphQL->getBaseUri()));

        $response = $graphQL->query('invalid');
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Syntax Error', $response->getErrorMessage());
        $this->assertEquals('graphql', $response->getErrorData()['category']);
    }

    public function testQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/query'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setClient(new MockHttpClient($responses, $graphQL->getBaseUri()));

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
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(257, $response->toArray()['data']['entries'][0]['id']);
        $this->assertEquals("The super-duper business event", $response->toArray()['data']['entries'][0]['title']);
    }

    public function testVariables()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/query'),
            new MockResponseFromFile(__DIR__ . '/responses/query-2'),
        ];
        $graphQL = new GraphQL('https://example.com/api');
        $graphQL->setClient(new MockHttpClient($responses, $graphQL->getBaseUri()));

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
        $this->assertEquals(2, count($response->toArray()['data']['entries']));
        $this->assertEquals(257, $response->toArray()['data']['entries'][0]['id']);

        $response = $graphQL->query($query, ['offset' => 2]);
        $this->assertEquals(1, count($response->toArray()['data']['entries']));
        $this->assertEquals(8, $response->toArray()['data']['entries'][0]['id']);
    }

}
