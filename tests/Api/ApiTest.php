<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Api\RestApi;
use Symfony\Component\HttpClient\MockHttpClient;
use Strata\Data\Response\MockResponseFromFile;

final class ApiTest extends TestCase
{

    public function testRestApi()
    {
        /**
         * Mock HTTP responses
         *
         * @see ResponseInterface::getInfo()
         */
        $responses = [
            new MockResponseFromFile(__DIR__ . '/responses/api-test'),
        ];

        $api = new RestApi('https://example.com/');
        $api->setClient(new MockHttpClient($responses, 'https://example.com/'));

        $response = $api->get('test1');
        $data = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $data['message']);
    }

}
