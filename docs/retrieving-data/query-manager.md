# Query manager

Query managers are used to help run queries to retrieve data. 

You create queries using a `Query` class which are then added to the query manager. Multiple queries can be added and these 
are only actually run when you access data. 

Multiple queries are run concurrently to aid performance. At present this only happens for queries from the same API, but 
we plan to review whether its possible to get queries across different APIs to run concurrently in the future.

You can then return either the decoded data (one item) or a collection of items (with pagination) from the query manager 
for each query you've setup.

TODO

```php
$manager = new QueryManager();
$manager->addDataProvider('craft', new GraphQL());
$manager->add('craft', new Query(), 'landing');
$item = $manager->getItem('craft', 'landing');

$manager->dataProvider('craft')->enableCache();
// or
$manager->enableCache();

$item = $manager->getCollection('craft', 'news');
```

## Query

The default query class uses the REST data provider, however, you can switch this.

You first build a query via the `Query` object, each query is then added to the `QueryManager` object which takes 
responsibility for sending these queries to the API. You can create multiple queries and add these to the query manager, 
however, you need to ensure each query has a unique name.

Data is retrieved via the `getItem()` or `getCollection()` methods which either return a single item or a collection of 
items. Queries are only run when you access data. Although each query generally requires a separate HTTP request these are 
sent concurrently which is a lot faster.

### Example usage

```php
use Strata\Data\Query\Query;
use Strata\Data\Query\QueryManagerOld;

$manager = new QueryManagerOld();

$query = (new Query())
        ->setUri('posts')
        ->addParam('page', 2);
$manager->add('posts', $query);

$query = (new Query()) 
        ->setUri('globals');
$manager->add('globals', $query);

$posts = $manager->getCollection('posts');
$globals = $manager->getItem('globals');
```

## Writing your own query managers

The real power comes in creating your own queries and query managers to make it easier to retrieve data from a specific 
API / data source.

Things you can set up in your own query classes:
* The query URI (REST)
* Default parameters
* Name and defined variables (GraphQL)
* Default fields to return in query (useful for GraphQL)

Things you can set up in your own query manager classes:

* Default data provider
* Define common properties for pagination or how fields are requested
* Define how collections are built from response data

To create your own query simply extend `Query` or `GraphQLQuery`. 

To create your own query manager extend `QueryManager` 
or `GraphQLQueryManager`.