<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Exception\GraphQLQueryException;
use Strata\Data\Query\GraphQL\Fragment;
use Strata\Data\Query\GraphQL\GraphQLTrait;

/**
 * Class to help craft a GraphQL API query
 *
 * Uses following properties from parent Query object:
 * name = query name
 * params = parameters to pass to query
 * fields = fields to return
 *
 * @todo Expand fields to support:
 *
 * fieldname, e.g language
 * aliases for fieldnames, e.g. code: language
 *
 * objects, e.g. localized { title }
 * inline fragments, e.g. ... on pages_landingPage_Entry { fields }
 */
class GraphQLQuery extends Query
{
    use GraphQLTrait;

    private ?string $alias = null;
    private array $definedVariables = [];
    private array $variables = [];
    private array $fragments = [];

    /**
     * Constructor
     * @param string|null $name Query name
     * @param string|null $filename Filename to load raw GraphQL query
     */
    public function __construct(?string $name = null, ?string $filename = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }
        if ($filename !== null) {
            $this->setGraphQLFromFile($filename);
        }
    }

    /**
     * Set alias
     * @param string $alias
     * @return $this Fluent interface
     */
    public function setAlias(string $alias): GraphQLQuery
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Whether alias is set
     * @return bool
     */
    public function hasAlias(): bool
    {
        return (!empty($this->alias));
    }

    /**
     * Return alias
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }


    /**
     * Define a GraphQL variable that can be set for this query
     * @param string $name
     * @param string $type
     * @return GraphQLQuery Fluent interface
     */
    public function defineVariable(string $name, string $type): GraphQLQuery
    {
        $this->definedVariables[$name] = $type;
        return $this;
    }

    /**
     * Does this variable exist for this query?
     * @param string $name
     * @return bool
     */
    public function isVariableDefined(string $name): bool
    {
        return (isset($this->definedVariables[$name]));
    }

    /**
     * Return variable type, or null if variable not available
     * @param string $name
     * @return string|null
     */
    public function getVariableType(string $name): ?string
    {
        if ($this->isVariableDefined($name)) {
            return $this->definedVariables[$name];
        }
        return null;
    }

    /**
     * Add a variable for query
     * @param string $name
     * @param $value
     * @return GraphQLQuery Fluent interface
     * @throws GraphQLQueryException
     */
    public function addVariable(string $name, $value, ?string $type = null): GraphQLQuery
    {
        if ($type !== null) {
            $this->defineVariable($name, $type);
        }
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * Set variables for query (name => value pairs)
     * @param array $variables
     * @return GraphQLQuery Fluent interface
     */
    public function setVariables(array $variables): GraphQLQuery
    {
        foreach ($variables as $key => $value) {
            $this->addVariable($key, $value);
        }
        return $this;
    }

    /**
     * Return GraphQL variables
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Add a fragment for use in the GraphQL query
     *
     * E.g. for:
     * fragment comparisonFields on Character {
     *   name
     *   appearsIn
     *   friends {
     *     name
     *   }
     * }
     *
     * Add the fragment via:
     * $fragment =
     * 'name
     *   appearsIn
     *   friends {
     *     name
     *   }';
     * $query->addFragment('comparisonFields', 'Character', $fragment);
     *
     * And use it in your query:
     * $query->addField('...comparisonFields');
     *
     * @param string $name Fragment name
     * @param string $object Object fragment acts on
     * @param string $fragmentGraphQL GraphQL to use within the fragment
     * @return GraphQLQuery Fluent interface
     */
    public function addFragment(string $name, string $object, string $fragmentGraphQL): GraphQLQuery
    {
        $fragment = new Fragment();
        $fragment->name = $name;
        $fragment->object = $object;
        $fragment->fragment = $fragmentGraphQL;
        $this->fragments[] = $fragment;
        return $this;
    }

    /**
     * Add fragment from a file
     * @param string $filename
     * @return GraphQLQuery
     * @throws GraphQLQueryException
     */
    public function addFragmentFromFile(string $filename): GraphQLQuery
    {
        $fragment = new Fragment();
        $fragment->setGraphQLFromFile($filename);
        $this->fragments[] = $fragment;
    }

    /**
     * Whether the query has fragments defined
     * @return bool
     */
    public function hasFragments(): bool
    {
        return (!empty($this->fragments));
    }

    /**
     * Return fragments
     * @return Fragment[]
     */
    public function getFragments(): array
    {
        return $this->fragments;
    }

}