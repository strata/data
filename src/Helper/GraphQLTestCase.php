<?php

declare(strict_types=1);

namespace Strata\Data\Helper;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\TestCase;

/**
 * Custom assertions for testing GraphQL queries with PHPUnit
 */
class GraphQLTestCase extends TestCase
{
    /**
     * Assert whether two GraphQL queries are identical
     *
     * Strips spaces and line returns to help compare GraphQL more reliably
     *
     * @param string $expected
     * @param string $actual
     */
    public function assertGraphQLEquals(string $expected, string $actual)
    {
        // Strip line returns
        $expected = preg_replace('/\s+/', ' ', $expected);
        $expected = trim($expected);
        $actual = preg_replace('/\s+/', ' ', $actual);
        $actual = trim($actual);

        Assert::assertThat(
            $actual,
            new IsIdentical($expected),
            'GraphQL queries are not identical'
        );
    }
}
