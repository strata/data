<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Http\RestApi;
use Strata\Data\Populate\PopulateCollection;
use Strata\Data\Populate\PopulateItem;
use Strata\Data\Populate\PopulateMetadata;
use Strata\Data\Response\MockResponseFromFile;
use Strata\Data\Populate\ArrayStrategy;
use Symfony\Component\HttpClient\MockHttpClient;

class RestApiTest extends TestCase
{

    public function testError()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/rest/invalid'),
        ];
        $api = new RestApi('https://example.com/api/');
        $api->setClient(new MockHttpClient($responses));

        $this->expectException('\Strata\Data\Exception\NotFoundException');
        $response = $api->get('test');
        $item = $response->getItem();
    }


    public function testQuery()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/rest/query'),
        ];
        $api = new RestApi('https://example.com/api/');
        $api->setClient(new MockHttpClient($responses));

        $response = $api->get('test');

        $populate = new PopulateItem();
        $populate->fromRoot();
        $populate->populate($response);

        $item = $response->getItem();

        $this->assertEquals(46, $item['id']);
        $this->assertEquals("Test", $item['title']);
    }

    public function testList()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/rest/list'),
        ];
        $api = new RestApi('https://example.com/api/');
        $api->setClient(new MockHttpClient($responses));

        $response = $api->get('test');

        $populate = new PopulateMetadata();
        $populate->fromProperty('meta');
        $response->populateStrategy($populate);

        $populate = new PopulateCollection($response);
        $populate->fromProperty('data')
                 ->totalResultsFromMeta('total')
                 ->resultsPerPage(3)
                 ->currentPageFromMeta('page');
        $response->populateStrategy($populate);

        $response->populate();

        $this->assertEquals(3, $response->getPagination()->getResultsPerPage());
        $this->assertEquals("Test 12", $response->current()['title']);
        $response->next();
        $this->assertEquals("Test 13", $response->current()['title']);
        $response->next();
        $this->assertEquals("Test 14", $response->current()['title']);
    }


    public function testInvalidPaginationField()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/rest/list'),
        ];
        $api = new RestApi('https://example.com/api/');
        $api->setClient(new MockHttpClient($responses));

        $response = $api->get('test');

        $this->expectException('Strata\Data\Exception\PopulateException');

        $populate = new PopulateCollection($response);
        $populate->fromProperty('data')
            ->totalResultsFromMeta('invalid');
        $populate->populate($response);
    }

}
