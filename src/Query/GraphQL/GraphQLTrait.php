<?php

declare(strict_types=1);

namespace Strata\Data\Query\GraphQL;

use Strata\Data\Exception\GraphQLQueryException;
use Strata\Data\Http\GraphQL;

trait GraphQLTrait
{
    private ?string $graphQL = null;

    /**
     * Set raw GraphQL query to use
     *
     * This overrides the automatic generation of GraphQL
     *
     * Format:
     * alias: queryName(params): { fields }
     *
     * @param string|null $graphQL
     * @return self Fluent interface
     */
    public function setGraphQL(?string $graphQL): self
    {
        $this->graphQL = $graphQL;
        return $this;
    }

    /**
     * Load GraphQL from file
     * @param string $filename
     * @return self Fluent interface
     * @throws GraphQLQueryException
     */
    public function setGraphQLFromFile(string $filename): self
    {
        $graphQl = file_get_contents($filename);
        if ($graphQl === false) {
            throw new GraphQLQueryException(sprintf('Cannot load GraphQL from file %s', $filename));
        }
        $this->graphQL = $graphQl;
        return $this;
    }

    /**
     * Whether this query has a raw GraphQL query set
     * @return bool
     */
    public function hasGraphQL(): bool
    {
        return (!empty($this->graphQL));
    }

    /**
     * Return raw GraphQL query to use in HTTP request
     *
     * @return string
     */
    public function getGraphQL(): string
    {
        return $this->graphQL;
    }
}