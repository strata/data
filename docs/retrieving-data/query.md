# Using queries

Query classes provide an object-orientated way to build queries and run them. They can also be used with a
[Query Manager](query-manager.md) to help run multiple queries.

## Example usage
Create a query by instantiating the `Query` class. 

Example usage:

```php
use Strata\Data\Query\Query;

// Setup query
$page = 2;
$query = (new Query())
        ->setUri('posts')
        ->addParam('page', $page)
        ->setCurrentPage($page)
        ->setTotalResults('[meta][total_results]')
        ->setRootPropertyPath('[data]')
;
```

The above example does the following:

* Queries the URI: `/posts?page=$page`
* Sets a few fields to enable automated pagination
* Tells the query that data should be returned from the `data` element 


```php
// Run directly via a query
$query->setDataProvider($api);
$posts = $query->getCollection();

// Or run via a query manager
$manager->add('posts', $query);
$posts = $query->getCollection('posts');
```

This:

* Runs the query
* Returns a collection of results along with a pagination object

## Property paths

In the example above the total results data is set as `[meta][total_results]` which is a property path pointing to `$data['meta']['total_results']`

See [property paths](../property-paths.md) for more information on how to use these.

## Query classes

There are a number of base query classes you can use to construct queries. 
It's recommended to create your own child query classes where you can set up the query in your constructor. Base query classes do not set constructors to make creating your own query classes easier.

### Strata\Data\Query\Query

Intended for REST API queries. Requires a `Strata\Data\Http\Rest` data provider. Defaults to a GET query.

### Strata\Data\Query\GraphQLQuery

Intended for GraphQL queries. Requires a `Strata\Data\Http\GraphQL ` data provider.

Has object-orientated methods to help build GraphQL queries. You can also set complex GraphQL queries from files. See [GraphQL queries](query-graphql.md).

### Strata\Data\Query\GraphQLMutation

Intended for GraphQL mutation queries. Requires a `Strata\Data\Http\GraphQL ` data provider.

By default, mutation queries are set to not run concurrently and do not cache. 

## Setting up a query

A query has a number of methods to set it up. All setup methods return the `Query` object so you can use a fluent interface and chain methods together.

Each query must have a data provider and a URI in order to run.

### setDataProvider

Set the data provider to use with the query. This must be an object of the `Strata\Data\Http\Rest` class. 

* Parameters
    * `DataProviderInterface $dataProvider` 

You don't need to use this method if you are using the [query manager](query-manager.md), instead the query has a data provider assigned to it when you add the query to the query manager.

### setUri

Set the URI to use for this query.

* Parameters
    * `string $uri` URI to run the query, relative to the data provider base URL

## Optional query settings

There are a lot of optional settings you can apply to a query.

### addParam

Add a single parameter to send with this query. This is sent as a GET param with the request.

* Parameters
    * `string $key` Param name
    * `mixed $value` Param value

### setParams

Set array of parameters to send with this query. These are sent as GET parameters with the request.  

* Parameters
    * `array $params` 

### setOptions

Set options for the HTTP request for just this query.

* Parameters
    * `array $options`  

For example to set headers:

```php
$query->setOptions([
    'headers' => [
        'Content-Type' => 'text/plain'
    ]
]));
```

To set an auth bearer token:

```php
$query->setOptions([
    'auth_bearer' => 'ABC123'
]));
```

### concurrent

All queries default to running concurrently when used with a query manager, you can disable this by calling `concurrent(false)`. 

* Parameters
    * `bool $concurrent = true` 


### setSubRequest

Mark the query as a sub-request, this suppresses errors in the HTTP request. 

* Parameters
    * `bool $subRequest` Defaults to true

### setFields

Set fields to return for this query. To use this method you must first set the field parameter. 

* Parameters
    * `array $fields` 

Example usage:

```php
$query->setFields(['name', 'title', 'email']);
```

Default functionality is to set the GET param`fields=name,title,email` for the request.


### setFieldParameter

The parameter to set to define fields to return with `setFields`. This defaults to "fields".

* Parameters
    * `string $fieldParameter` 


### setRootPropertyPath

Set the root property path to retrieve data from, e.g. `'[data]'` to return data from the array key 'data' in the response. If this is not called then data is read from the root element.

* Parameters
    * `string $path` Property path to root data element

### setCurrentPage

Set pagination current page data.

* Parameters
    * `string|int $currentPage` Property path in returned data to current page, or the actual value
    
Example usage:

```php 
// Set page to the current page variable
$query->setCurrentPage($page);

// Or set to a data field returned by the query
$query->setCurrentPage('[meta][current_page]');
```

### setResultsPerPage

Set pagination results per page data.

* Parameters
    * `string|int $resultsPerPage` Property path in returned data to results per page, or the actual value

### setTotalResults

Set pagination total results data.

* Parameters
    * `string|int $totalResults` Property path in returned data to total results, or the actual value

### setPaginationDataFromHeaders

By default pagination data is read from the returned data. Some API responses set pagination data in the headers rather than the response data. Use this method to tell the query to retrieve pagination data from headers.

* Parameters
    * `bool $paginationDataFromHeaders` Defaults to true


### setMultipleValuesSeparator

When arrays are passed to params they are automatically converted into strings, separate by ','

You can alter the character used to separate multiple values with this method.

* Parameters
    * `string $multipleValuesSeparator` 

## Caching

By default, automatic caching for data requests happens if the data provider used by the query has caching enabled. This can also be controlled on the query level.

Also see more details on [caching](../usage/caching.md).

### cache

Manually set this query to cache. If no valid cache is set to the data provider this does nothing.

* Parameters
    * `?int $lifetime = null` Lifetime in seconds for the cache

### doNotCache

Manually set this query to not cache.

### cacheTags

Set the following cache tags when storing this query to the cache. These are added to any other cache tags already set.

* Parameters
    * `array $tags = []`

## Retrieving data

Query responses are lazy loaded for performance. If the cache is enabled, query responses are immediately fetched. If a 
live HTTP request is required then queries are executed when you access data.

You can also manually run a query via the `run()` method.

### get

You can return a single item of data using `get()`. This automatically retrieves and decodes the query response and
returns data. 

This normally returns an array, though the return type is not fixed so child query classes have flexibility on what data is returned.

### getCollection

Return a `Collection` object which contains an iterable collection of data and pagination information. 

You can set the [root property path](#setrootpropertypath) to select collection data from in the response. If you don't set this 
in your query then the root data element is used as the collection (this will throw an exception if it is not iterable).

To build pagination you can set the [current page](#setcurrentpage), [results per page](#setresultsperpage) and 
[total results](#settotalresults) fields respectively.

```php
$collection = $manager->getCollection('queryName');
$pagination = $collection->getPagination();
```

### Whether the data was returned by the cache

You can find out if the query response was returned from the cache via `$query->isHit()` which returns a boolean. 
This is a shortcut to the `CacheableResponse::isHit` method.

### getResponse

You can also get access to the response object itself via the `getResponse()` method. This returns a `CacheableResponse`
object. This is identical to a standard Symfony `HttpClient` response with the addition of
the `isHit()` method to indicate whether the response came from the cache or was requested live.

You can use standard `HttpClient` methods to convert data to an array:

```php
$response = $query->getResponse();
$data = $query->getDataProvider()->toArray();
```

Or you can grab the data provider for this query and use the decode method.

```php
$response = $query->getResponse();
$data = $query->getDataProvider()->decode($response);
```

### Re-running a query

By default, once a query is run it stores its response and does not re-run the live request again. 

If you want to force the query to re-run, simply call the `clearResponse()` method:

```php
$query->clearResponse();
```

Then any subsequent calls on `get()`, `getCollection()` or `getResponse()` will re-run the live query. Please note if 
caching is enabled for this query, then a cached response will still be returned if it exists.