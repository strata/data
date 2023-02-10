<?php

namespace Query;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Rest;
use Strata\Data\Query\GraphQLQuery;

class GraphQLTest extends TestCase
{
    public function testSetDataProvider()
    {
        $query = new GraphQLQuery();
        $this->assertSame(GraphQL::class, $query->getRequiredDataProviderClass());

        $query->setDataProvider(new GraphQL('https://example.com'));
        $this->assertSame(GraphQL::class, get_class($query->getDataProvider()));

        $this->expectException(QueryException::class);
        $query->setDataProvider(new Rest('https://example.com'));
    }
}
