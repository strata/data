# Using GraphQL queries

The GraphQL query manager has some changes to reflect how GraphQL works. The default data provider is `GraphQL`, you build
queries using `GraphQLQuery`. Each query must have a name which is used to define the GraphQL query.

Since GraphQL supports sending multiple queries at once, you can create multiple queries and these are sent as one HTTP
request when data is requested. Building the correct GraphQL query string happens behind the scenes, but it's also possible
to pass complex GraphQL queries via `$query->setGraphQL()` or load these via files.

Variables can be used in GraphQL queries. First, a variable must be defined via `$query->defineVariable()`, you can then
add the actual variables via `$query->addVariable()`. These are automatically added to the GraphQL query that is sent to
the API.

### Example usage

```php
use Strata\Data\Query\GraphQLQuery;
use Strata\Data\Query\GraphQLQueryManagerOld;

$manager = new GraphQLQueryManagerOld();

$query = new GraphQLQuery('entries');
$query->setParams(['section' => 'news', 'page' => 2]);
$query->setFields(['title', 'excerpt', 'datePublished', 'author']);
$manager->add('posts', $query);

$query = new GraphQLQuery();
$manager->add('globalSets', $query);

$posts = $manager->getCollection('posts');
$globals = $manager->getItem('globals');
```

### Loading GraphQL from file
You can alternatively specify a raw GraphQL query.

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
$query = new GraphQLQuery('query.graphql');
$query->setVariable('slug', '/about');
```

### Fragments

You can also use fragments:

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


