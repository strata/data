<?php

namespace Query;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Rest;
use Strata\Data\Query\Query;

class QueryTest extends TestCase
{

    public function testSetDataProvider()
    {
        $query = new Query();
        $this->assertSame(Rest::class, $query->getRequiredDataProviderClass());

        $query->setDataProvider(new Rest('https://example.com'));
        $this->assertSame(Rest::class, get_class($query->getDataProvider()));

        $this->expectException(QueryException::class);
        $query->setDataProvider(new GraphQL('https://example.com'));
    }
}
