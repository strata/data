# Query manager

A query manager is used to help run multiple queries to retrieve data. 

Queries are prepared when added to the query manager and are only run when data is accessed. Where possible, 
multiple HTTP requests are run concurrently for better performance. 

You can then return either the decoded data (one item) or a collection (multiple items with pagination) from the query manager 
for each query you've set up.

Query managers support caching, so you can do things like set cache tags across multiple queries.

## Adding data providers

First create a new `QueryManager` instance and add data providers to it via the `addDataProvider()` method. This takes 
two arguments: the data provider name, and the data provider object itself.

```php
use Strata\Data\Query\QueryManager;
use Strata\Data\Http\GraphQL;
use Strata\Data\Http\Rest;

$manager = new QueryManager();

// The first argument is the data provider name
$manager->addDataProvider('internal_api', new Rest("https://example1.com/api/"));

// Add a second data provider
$manager->addDataProvider('cms', new GraphQL("https://example2.com/api/"));
```

## Adding queries

Add a query add via the `QueryManager::add` method. You need to pass the query name as the 1st argument, and the query 
object as the 2nd argument. You can optionally set the data provider name as the 3rd argument. The default functionality 
is to use the first compatible data provider in the query manager.

### Example REST API query

```php
use Strata\Data\Query\Query;

$query = new Query();
$query->setUri('news')
      ->setParams(['page' => 2]);

// Pass query name as 1st argument, query object as 2nd argument
// This adds the query to the first compatible data provider in the query manager
$manager->add('news', $query);

// Optionally, you can state the data provider as the 3rd argument
$manager->add('news', $query, 'internal_api');
```

The above creates a GET query which will be sent to `https://example.com/api/news?page=2`

You can build more complex [REST-based API queries](query.md).

### Example GraphQL query

```php
use Strata\Data\Query\GraphQLQuery;

$query = new GraphQLQuery();
$query->setName('entries')
      ->setParams(['section' => 'news', 'page' => 2])
      ->setFields(['title', 'excerpt', 'datePublished', 'author']);

$manager->add('entries', $query);
```

The above automatically creates a GraphQL query that looks like:

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

You can build more complex [GraphQL queries](graphql.md), including setting GraphQL queries from files.

## Returning data

Data is returned on request by the query manager. You can retrieve query data via:

* `QueryManager::get` - returns a single item
* `QueryManager::getCollection` - returns a collection of items
* `QueryManager::getResponse` - returns the HTTP response object

When any of these retrieval methods are run all unprocessed queries in the query manager are run. 

```php
// Returning data via the Query Manager
$data = $manager->get('news');
$collection = $manager->getCollection('news');
$response = $manager->getResponse('news');
```

If you don't want to run all unprocessed queries, once a query is added to the query manager you can run queries 
individually if you wish.

```php
// Or on the query object directly
$data = $query->get();
$collection = $query->getCollection();
$response = $query->getResponse();
```

If you need to retrieve a query from the query manager use `getQuery()`:

```php
$query = $manager->getQuery('news');
```

You can also use `hasQuery()` to test whether a query exists in the query manager.

## Caching

Also see [caching](../usage/caching.md).

### setCache

Set a cache to use for all queries in the query manager. See Symfony documentation on [compatible cache adapters](https://symfony.com/doc/current/components/cache/cache_pools.html) 
you can use. If you want to use cache tags, please ensure you use a cache adapter that implements `Symfony\Component\Cache\Adapter\TagAwareAdapterInterface`.

* Parameters
    * `CacheInterface $cache` Cache that implements `Symfony\Contracts\Cache\CacheInterface`
    * `?int $defaultLifetime = null`
    
### enableCache

Enables the cache for this request. This requires a cache to be set to the data provider to work.

* Parameters
    * `?int $lifetime = null` Lifetime in seconds for the cache, if not set defaults to one hour

### disableCache

Disables the cache for this request. If no cache is set on the data provider then this does nothing.

### setCacheTags

Set cache tags to apply to all queries in the query manager. If you do not pass any arguments this method removes
all cache tags.

* Parameters
    * `array $tags = []` Array of tags

### getCacheTags

Returns current set cache tags. If the cache adapter set to the query manager does not support tags this does nothing. 

* Return:
    * array
    
### hasCache

Whether the query manager has a cache set.

* Return:
    * bool

### isCacheEnabled

Whether the cache is enabled for all queries in the query manager. Please note you can enable the cache on the query level, so it
only affects individual queries.

* Return:
    * bool
