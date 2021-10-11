<?php

declare(strict_types=1);

namespace Strata\Data\Query;

/**
 * Class to help craft a GraphQL API query
 */
class GraphQLMutation extends GraphQLQuery implements GraphQLQueryInterface
{
    protected bool $concurrent = false;
    protected ?bool $cacheableRequest = false;
}
