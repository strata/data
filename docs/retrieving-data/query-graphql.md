# Using GraphQL queries

The GraphQL query manager has some changes to reflect how GraphQL works. The required data provider is `GraphQL` and you 
build queries using `GraphQLQuery`. 

GraphQL queries support variables, aliases, fragments.

### Example usage (build query)

You can build your GraphQL query by setting up the query object. This currently only works well for simple GraphQL queries.

You must set a name via `setName()` (which is used as the query name), you can set an alias if required via `setAlias()`. 

Params are set as [arguments](https://graphql.org/learn/queries/#arguments) for the main query, and are set via `addParam()` 
or `setParams()`.

You can set the fields to return via `setFields()`, if arguments are required for fields simply add them in the field string.

You can use variables in your GraphQL query via `addVariable()`. You must pass the variable type when adding a variable 
or have already defined it via `defineVariable()`.

You can also use [fragments](#addfragment) of GraphQL query code and include this in your queries.

```php
use Strata\Data\Query\GraphQLQuery;

$query = (new GraphQLQuery())
    ->setName('entries')
    ->setParams(['section' => 'news', 'page' => 2])
    ->setFields(['title', 'excerpt', 'datePublished', 'author']);

$posts = $query->getCollection();
```

This constructs a GraphQL query:

```graphql
query EntriesQuery {
  entries(section: "news", page: 2) { 
    title
    excerpt
    datePublished
    author
  }
}
```

### Example usage (GraphQL query in file)

You can set the raw GraphQL query from file by passing the first argument in the constructor or via `setGraphQLFromFile()`. 
You can also use `setGraphQL()` to set the query from a string.

When setting the main GraphQL query from file the following query methods are still available to use:
* [addFragment](#addfragment)
* [addFragmentFromFile](#addfragmentfromfile)
* [addVariable](#addvariable)

```php
use Strata\Data\Query\GraphQLQuery;

$query = (new GraphQLQuery('news_entries.graphql'))
    ->addVariable('page', 2);

$posts = $query->getCollection();
```

## Setting up a query

### setDataProvider

Set the data provider to use with the query. This must be an object the `Strata\Data\Http\GraphQL` class.

* Parameters
    * `DataProviderInterface $dataProvider` 

### setName

Set the query name to use with GraphQL. Unless you set your own raw GraphQL a name must be set.

You can also set an alias, which is used as the returned data field name in place of the query name. 

* Parameters
    * `string $name` Query name
    * `?string $alias` Alias to use for query
    
### setAlias

Set the alias. See [aliases in GraphQL](https://graphql.org/learn/queries/#aliases).

* Parameters
    * `string $alias`

### addParam

Add a single parameter to send with this query. This is sent as argument to the GraphQL query. See [arguments in GraphQL](https://graphql.org/learn/queries/#arguments).

* Parameters
    * `string $key` Argument name
    * `mixed $value` Argument value

### setParams

Set array of parameters to send with this query. These are sent as arguments to the GraphQL query.

* Parameters
    * `array $params`


### setGraphQL

Instead of setting parameters to build a query you can pass a string to set the raw GraphQL query.

* Parameters
    * `?string $graphQL`

### setGraphQLFromFile

Instead of setting parameters to build a query you can pass a string to set the raw GraphQL query. You can also pass the 
GraphQL filename as the 1st argument in the constructor, or you can use this method.

* Parameters
    * `string $filename`

Example usage:

File: `query.graphql`

```graphql
query MyQuery ($slug: String) {
  entry(slug: $slug) { 
    id
    ... on pages_landingPage_Entry {
      title
      pageLead
      landingFlexibleComponents(orderBy: "sortOrder") {
        ... on landingFlexibleComponents_textComponent_BlockType {
          typeHandle
          contentField
          sortOrder
          enabled
        }
      }
    }
  }
}
```

```php
$query = (new GraphQLQuery('query.graphql'))
    ->setVariable('slug', '/about');

// or
$query = (new GraphQLQuery())
    ->setGraphQLFromFile('query.graphql')
    ->setVariable('slug', '/about');

```

### addFragment

You can add fragments which can be used to store more complex or repetitive GraphQL. These can then be injected into your 
GraphQL via `...fragmentName`. See [fragments in GraphQL](https://graphql.org/learn/queries/#fragments).

* Parameters
    * `string $name`
    * `string $object`
    * `string $fragmentGraphQL`

Example usage:

```php
$fragment = <<<EOD
  name
  appearsIn
  friends {
    name
  }
EOD;
 
$query->addFragment('comparisonFields', 'Character', $fragment);
```

This constructs a GraphQL fragment:

```graphql
fragment comparisonFields on Character {
  name
  appearsIn
    friends {
      name
  }
}
```

### addFragmentFromFile

If you've written your own GraphQL fragments you can add these directly.

* Parameters
    * `string $filename`

Example usage: 

File: `query.graphql`

```graphql
query MyQuery ($slug: String) {
  entry(slug: $slug) { 
    id
    ... on pages_landingPage_Entry {
      title
      pageLead
      landingFlexibleComponents(orderBy: "sortOrder") {
          ...landingComponents     
      }
    }
  }
}
```

File: `landingComponentsFragment.graphql`

```graphql
fragment landingComponents on landingFlexibleComponents_MatrixField {
    ... on landingFlexibleComponents_textComponent_BlockType {
      typeHandle
      contentField
      sortOrder
      enabled
    }
}
```

```php
$query = new GraphQLQuery('query.graphql');
$query->addFragmentFromFile('landingComponentsFragment.graphql')
      ->setVariable('slug', '/about');
```

### defineVariable

Variables can be used in GraphQL queries. 

If you are building your GraphQL from the query object you need to define the variable and its type via `defineVariable()`
or by passing a 3rd argument to `addVariable()`. If you are building your GraphQL from file or string then there is no 
need to define variables, since this should already be done in your manual GraphQL query.

You can then add the actual variables via `addVariable()`. These are automatically added to the GraphQL query that is sent to
the API. See [variables in GraphQL](https://graphql.org/learn/queries/#variables).

* Parameters
    * `string $name` Variable name
    * `string $type` Variable type (GraphQL type)

### addVariable

Add a variable to be used in GraphQL query.

* Parameters
    * `string $name` Variable name
    * `mixed $value` Value
    * `?string $type = null` Optional variable type (GraphQL type)

### setVariables

Set an array of variables to be used in GraphQL query.

* Parameters
    * `array $variables` Array of name => value  variable pairs