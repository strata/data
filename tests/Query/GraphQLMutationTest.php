<?php

namespace Query;

use PHPUnit\Framework\TestCase;
use Strata\Data\Query\GraphQLMutation;

class GraphQLMutationTest extends TestCase
{
    public function testSetDefaults()
    {
        $query = new GraphQLMutation();
        $this->assertFalse($query->isCacheableRequest());
        $this->assertFalse($query->isConcurrent());
    }
}
