<?php

declare(strict_types=1);

namespace Strata\Data\Query;

use Strata\Data\Collection;
use Strata\Data\CollectionInterface;
use Strata\Data\DataProviderInterface;
use Strata\Data\Exception\GraphQLQueryException;
use Strata\Data\Exception\QueryException;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Mapper\MapCollection;
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\MappingStrategyInterface;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Query\BuildQuery\BuildGraphQLQuery;
use Strata\Data\Query\GraphQL\Fragment;
use Strata\Data\Query\GraphQL\GraphQLTrait;
use Strata\Data\Traits\QueryStaticMethodsTrait;

/**
 * Class to help craft a GraphQL API query
 */
class GraphQLQuery extends QueryAbstract implements GraphQLQueryInterface
{
    use GraphQLTrait;
    use QueryStaticMethodsTrait;

    protected ?string $name = null;
    private ?string $alias = null;
    private array $definedVariables = [];
    private array $variables = [];
    private array $fragments = [];
    protected string $multipleValuesSeparator = ', ';

    /**
     * Data provider class required for use with this query
     * @return string
     */
    public function getRequiredDataProviderClass(): string
    {
        return GraphQL::class;
    }

    /**
     * Return data provider
     * @return GraphQL
     */
    public function getDataProvider(): GraphQL
    {
        if (!($this->dataProvider instanceof GraphQL)) {
            throw new \Exception('Data provider not set');
        }
        return $this->dataProvider;
    }

    /**
     * Return query name
     *
     * @param string $name
     * @param ?string $alias Alias to use for query
     * @return $this Fluent interface
     */
    public function setName(string $name, ?string $alias = null): self
    {
        $this->name = $name;

        if ($alias !== null) {
            $this->setAlias($alias);
        }

        return $this;
    }

    /**
     * Whether this query has a name
     * @return bool
     */
    public function hasName(): bool
    {
        return (!empty($this->name));
    }

    /**
     * Get query name
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
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
        return $this;
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

    /**
     * Prepare a query for running
     *
     * Prepares the response object but doesn't run it - unless data is returned by the cache
     *
     * If you don't run this, it's automatically run when you access the Query::run() method
     *
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function prepare()
    {
        if (!$this->hasDataProvider()) {
            throw new QueryException('Cannot prepare query since data provider not set (do this via Query::setDataProvider or via QueryManager::addDataProvider)');
        }
        $dataProvider = $this->getDataProvider();

        // Prepare request
        $buildQuery = new BuildGraphQLQuery($dataProvider);
        $this->response = $buildQuery->prepareRequest($this);
    }

    /**
     * Run a query
     *
     * Populates the response object
     *
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Strata\Data\Exception\HttpException
     * @throws \Strata\Data\Exception\HttpNotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function run()
    {
        $dataProvider = $this->getDataProvider();
        $response = $this->getResponse();

        if ($this->isSubRequest()) {
            $dataProvider->suppressErrors();
        }

        // Prepare response if not already done
        if (!($response instanceof CacheableResponse)) {
            $this->prepare();
            $response = $this->getResponse();
        }

        $this->response = $dataProvider->runRequest($response);

        // Reset suppress errors to previous values
        if ($this->isSubRequest()) {
            $this->dataProvider->resetSuppressErrors();
        }
    }

    /**
     * Return mapping strategy to use to map a single item
     *
     * You can override this in child classes
     *
     * @return MappingStrategyInterface|array
     */
    public function getMapping()
    {
        return new WildcardMappingStrategy();
    }

    /**
     * Return data from response
     * @return mixed
     * @throws \Strata\Data\Exception\MapperException
     */
    public function get()
    {
        // Run response, if not already run
        if (!$this->hasResponseRun()) {
            $this->run();
        }

        // Simple mapping from root property path
        $data = $this->dataProvider->decode($this->getResponse());
        $mapper = new MapItem($this->getMapping());
        return $mapper->map($data, $this->getRootPropertyPath());
    }

    /**
     * Return collection of data from a query response
     * @return CollectionInterface
     * @throws QueryException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Strata\Data\Exception\HttpException
     * @throws \Strata\Data\Exception\HttpNotFoundException
     * @throws \Strata\Data\Exception\MapperException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getCollection(): CollectionInterface
    {
        // Run response, if not already run
        if (!$this->hasResponseRun()) {
            $this->run();
        }

        // Simple mapping from root property path
        $response = $this->getResponse();
        $data = $this->dataProvider->decode($response);
        $mapper = new MapCollection($this->getMapping());

        // Populate pagination data if empty
        if (empty($this->getTotalResults())) {
            $this->setTotalResults(count($data));
        }
        if (empty($this->getResultsPerPage())) {
            $this->setResultsPerPage(count($data));
        }

        // Use pagination setup query
        $mapper->setTotalResults($this->getTotalResults())
            ->setResultsPerPage($this->getResultsPerPage())
            ->setCurrentPage($this->getCurrentPage());
        if ($this->isPaginationDataFromHeaders()) {
            $mapper->fromPaginationData($response->getHeaders());
        }

        return $mapper->map($data, $this->getRootPropertyPath());
    }
}
