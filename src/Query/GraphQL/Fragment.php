<?php

declare(strict_types=1);

namespace Strata\Data\Query\GraphQL;

/**
 * Simple class to manage GraphQL fragments
 */
class Fragment
{
    use GraphQLTrait;

    public string $name;
    public string $object;
    public string $fragment;

}