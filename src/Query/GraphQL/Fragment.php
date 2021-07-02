<?php

declare(strict_types=1);

namespace Strata\Data\Query\GraphQL;


class Fragment
{
    use GraphQLTrait;

    public string $name;
    public string $object;
    public string $fragment;

}