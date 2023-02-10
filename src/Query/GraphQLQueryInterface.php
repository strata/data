<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Query\GraphQL\Fragment;

interface GraphQLQueryInterface extends QueryInterface
{
    /**
     * Whether this query has a raw GraphQL query set
     * @return bool
     */
    public function hasGraphQL(): bool;

    /**
     * Return raw GraphQL query to use in HTTP request
     *
     * @return string
     */
    public function getGraphQL(): string;

    /**
     * Whether alias is set
     * @return bool
     */
    public function hasAlias(): bool;

    /**
     * Return alias
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * Does this variable exist for this query?
     * @param string $name
     * @return bool
     */
    public function isVariableDefined(string $name): bool;

    /**
     * Return variable type, or null if variable not available
     * @param string $name
     * @return string|null
     */
    public function getVariableType(string $name): ?string;

    /**
     * Return GraphQL variables
     * @return array
     */
    public function getVariables(): array;

    /**
     * Whether the query has fragments defined
     * @return bool
     */
    public function hasFragments(): bool;

    /**
     * Return fragments
     * @return Fragment[]
     */
    public function getFragments(): array;
}
