<?php

declare(strict_types=1);

namespace Strata\Data\Query\BuildQuery;

use Strata\Data\Exception\GraphQLQueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\Query;

/**
 * Class to help build REST API HTTP requests
 * @package Strata\Data\Query
 */
class BuildGraphQLQuery implements BuildQueryInterface
{
    private GraphQL $dataProvider;

    /**
     * Constructor
     * @param GraphQL $dataProvider Data provider to use to build this query
     */
    public function __construct(GraphQL $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Indent GraphQL
     * @param int $depth
     * @return string
     */
    public function indent(int $depth = 1): string
    {
        return str_pad('', $depth * 2, ' ');
    }

    /**
     * Return parameters (key: "values") for use in an GraphQL query
     *
     * @param GraphQLQuery $query
     * @return array
     */
    public function getGraphQLParameters(GraphQLQuery $query): string
    {
        $params = [];

        // Build param list
        foreach ($query->getParams() as $name => $value) {
            switch (gettype($value)) {
                case 'boolean':
                    $paramValue = ($value) ? 'true' : 'false';
                    break;
                case 'array':
                    $paramValue = '"' . implode(', ', $value) . '"';
                    break;
                default:
                    // If a variable do not quote
                    if (strpos($value, '$') === 0) {
                        $paramValue = $value;
                        break;
                    }
                    $paramValue = '"' . $value . '"';
            }
            $params[] = sprintf('%s: %s', $name, $paramValue);
        }

        return implode(', ', $params);
    }

    /**
     * Return GraphQL query from array of queries
     *
     * @param GraphQLQuery $query
     * @return string
     */
    public function buildGraphQL(GraphQLQuery $query): string
    {
        if ($query->hasGraphQL()) {
            $graphQL = $query->getGraphQL();

        } else {
            /**
             * Build GraphQL query
             * Format:
             * query NameQuery(variables) {
             *   alias: Name(params): {
             *     fields
             *   }
             * }
             */
            $graphQL = '';
            $variables = [];
            $variablesDefinition = [];

            // Query name
            if (empty($query->getName())) {
                throw new GraphQLQueryException('Cannot return GraphQL since query name is not set');
            }
            if ($query->hasAlias()) {
                $graphQL .= $this->indent() . sprintf('%s: %s', $query->getAlias(), $query->getName());
            } else {
                $graphQL .= $this->indent() . $query->getName();
            }

            // Params
            if ($query->hasParams()) {
                $graphQL .= '(';
                $graphQL .= $this->getGraphQLParameters($query);
                $graphQL .= ') {' . PHP_EOL;
            }

            // Fields
            foreach ($query->getFields() as $field) {
                $graphQL .= $this->indent(2) . $field . PHP_EOL;
            }

            $graphQL .= $this->indent() . '}' . PHP_EOL;

            // Variables
            foreach ($query->getVariables() as $name => $value) {
                $variables[$name] = $value;
                $variablesDefinition[$name] = $query->getVariableType($name);
            }

            // Wrap GraphQL query in query statement
            $query = 'query ' . ucfirst($query->getName()) . 'Query';
            if (!empty($variablesDefinition)) {
                $queryVariables = [];
                foreach ($variablesDefinition as $name => $type) {
                    $queryVariables[] = sprintf('$%s: %s', $name, $type);
                }
                $query .= '(';
                $query .= implode(', ', $queryVariables);
                $query .= ')';
            }
            $graphQL .= $query . '{ ' . PHP_EOL . $graphQL . PHP_EOL . '}' . PHP_EOL;
        }

        // Fragments
        if ($query->hasFragments()) {
            $graphQL .= PHP_EOL;
            foreach ($query->getFragments() as $fragment) {
                if ($fragment->hasGraphQL()) {
                    $graphQL .= $fragment->getGraphQL() . PHP_EOL;
                    continue;
                }

                // Build fragment
                $graphQL .= sprintf('fragment %s on %s', $fragment->name, $fragment->object) . ' {' . PHP_EOL;
                $graphQL .= $fragment->fragment . PHP_EOL;
                $graphQL .= '}' . PHP_EOL;
            }
        }

        return $graphQL;
    }

    /**
     * Return a prepared request
     *
     * Request is not run since no data is accessed (Symfony HttpClient lazy runs requests when you access data)
     * If response is returned from cache then full response data is returned by this method
     *
     * @param Query $query
     * @return CacheableResponse
     */
    public function prepareRequest(Query $query): CacheableResponse
    {
        // Build query
        if ($query->isSubRequest()) {
            $this->dataProvider->suppressErrors();
        } else {
            $this->dataProvider->suppressErrors(false);
        }
        if ($query->isCacheEnabled()) {
            $this->dataProvider->enableCache($query->getCacheLifetime());
        } else {
            $this->dataProvider->disableCache();
        }

        $graphQL = $this->buildGraphQL($query);

        // @todo this runs request immediately and doesn't support concurrent multiple queries, can we improve on this?
        return $this->dataProvider->query($graphQL, $query->getVariables());
    }

}