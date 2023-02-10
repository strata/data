<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\DataProviderCommonTrait;

class Base
{
    use DataProviderCommonTrait;
}

class DataProviderTest extends TestCase
{
    public function testBaseUri()
    {
        $api = new Base();
        $api->setBaseUri('test-uri');

        $this->assertEquals('test-uri', $api->getBaseUri());
        $this->assertEquals('test-uri/my-location', $api->getUri('my-location'));

        $api->setBaseUri('https://example.com/api');
        $this->assertEquals('https://example.com/api/list', $api->getUri('list'));
        $this->assertEquals('https://example.com/api/list', $api->getUri('/list'));

        $api->setBaseUri('https://example.com/api/');
        $this->assertEquals('https://example.com/api/list', $api->getUri('list'));
        $this->assertEquals('https://example.com/api/list', $api->getUri('/list'));
    }
}
