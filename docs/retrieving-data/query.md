# Using queries

You first build a query via the `Query` object, each query is then added to the `QueryManager` object which takes
responsibility for sending these queries to the API. You can create multiple queries and add these to the query manager,
however, you need to ensure each query has a unique name.

Data is retrieved via the `getItem()` or `getCollection()` methods which either return a single item or a collection of
items. Queries are only run when you access data. Although each query generally requires a separate HTTP request these are
sent concurrently which is a lot faster.

## Creating a query

TODO 

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

## Retrieving data from a query

Query responses are lazy loaded for performance. If the cache is enabled, query responses are immediately fetched. If a live request 
is required then queries are executed when you access data.

### getItem

You can return a single item of data using `getItem()`. This automatically retrieves and decodes the query response and 
returns an array of data.

```php
$data = $manager->getItem('queryName');
```

### getCollection

You can return a collection of items of data using `getCollection()`.  This automatically retrieves and decodes the 
query response and maps the returned data to a collection object.

If the query object has pagination properties set, then a pagination object is automatically created for you. This 
is available via `$collection->getPagination()`

```php
$collection = $manager->getCollection('queryName');
```

### getResponse

You can also get access to the response object itself via the `getResponse()` method. This returns a `CacheableResponse` 
object. This is identical to a standard Symfony `HttpClient` response with the addition of
the `isHit()` method to indicate whether the response came from the cache or was requested live.

You can use standard `HttpClient` methods to convert data to an array:

```php
$response = $manager->getResponse('queryName');
$data = $response->toArray();
```

Or you can grab the data provider for this query and use the decode method.

```php
$response = $manager->getResponse('queryName');
$data = $dataProvider->decode($response);
```

If you need to return the data provider associated with a query you can use:

```php
$dataProvider = $manager->getDataProviderForQuery('queryName');
```