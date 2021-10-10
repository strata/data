# Query manager

A query manager is used to help run multiple queries to retrieve data. You can share Http client and settings across different queries.

## Data providers

### addDataProvider
First create a new `QueryManager` instance and add data providers to it via the `addDataProvider()` method. This takes 
two arguments: the data provider name (which must be unique), and the data provider object itself.

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

### setHttpClient

You can override the Http client used by all data providers. This is useful in testing, so you can pass a `MockHttpClient`.

Please note this only sets the Http client for all existing data providers, if you set any future data providers they will use
their own Http client unless this method is called again - or you use `shareHttpClient()`.

* Params:
    * `HttpClientInterface $httpClient` Symfony HttpClient object

### shareHttpClient

By calling this method all data providers in the query manager that use a `Http` compatible data provider are set to share 
the same data provider. This helps performance, by ensuring multiple queries are run concurrently.

This is not default behaviour, so needs to be called to enabled.

## How queries are run

There are two strategies for loading data in queries when using the query manager.

### Concurrent requests

By default queries in the query manager are designed to be run concurrently when you first request data. This works in the following way:

1. When you add queries, each query is added to the queries array in the query manager. 
2. If a query can be cached, the cache is checked and if a valid cached result exists the query is populated when you add it.
3. If a query is not populated by the cache, then a live query is run the first time any data is requested from the query manager.
4. When you request any data from the query manager all un-run queries are run. Queries are run concurrently to help performance (please note this is only possible if the Http client is shared between data managers).
5. If you add any new queries after requesting data, then these are not automatically run until data is requested again.

E.g.

```php
$query = new Query();
$query->setUri('posts');
$manager->add('posts', $query);

$query = new Query();
$query->setUri('news')
      ->setParam('limit', 25);
$manager->add('news', $query);

// This runs both queries concurrently and returns data for the posts query
$data = $manager->get('posts');
```

### Individual requests

Concurernt requests works great for retrieving data. However, it's not great for  all data requests.

For any requests you want to run individually you can do this by setting `false` on the `concurrent()` flag for a query object. Non-concurrent queries are then only run when you access data for that query and never when any other query data is accessed.

```php
$query = new Query();
$query->setUri('blog-comments')
      ->concurrent(false);
$manager->addStandalone('comments', $query);

// This runs only the comment comment query
$data = $manager->get('comments');
```

## Adding queries

Add a query add via the `add()` method. 

* Params:
  * `string $queryName` - Query name, must be unique
  * `QueryInterface $query` - Query object
  * `?string $dataProviderName` - Optional data provider name, if not set uses the first compatible data provider in the query manager

This prepares the query and if the cache is enabled returns the object from the cache.

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

You can set a cache in a query manager and it is shared across all data providers. 

Also see [caching](../usage/caching.md).

### setCache

Set a cache to use for all queries in the query manager and enable the cache. This shares the same cache adapater with all data providers in 
the query manager.  

* Parameters
  * `CacheInterface $cache` Cache that implements `Symfony\Contracts\Cache\CacheInterface`
  * `?int $defaultLifetime = null` Default cache lifetime (if not set defaults to one hour)

See Symfony documentation on [compatible cache adapters](https://symfony.com/doc/current/components/cache/cache_pools.html) 
you can use. If you want to use cache tags, please ensure you use a cache adapter that implements `Symfony\Component\Cache\Adapter\TagAwareAdapterInterface`.

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

Whether the cache is available for use in the query manager. For this to be true you need to have added a cache adapter
via `setCache()`, the cache needs to be enabled, and the `skipCache` flag is not enabled.

* Return:
    * bool
