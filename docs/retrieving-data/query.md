# Using queries

Query classes provide an object-orientated way to build queries and run them. They can also be used with a
[Query Manager](query-manager.md) to help run multiple queries.

## Example usage
Create a query by instantiating the `Query` class. This class takes no arguments when creating it, this is intentional so 
any child classes are free to use constructors to make building queries easier. 

Example usage:

```php
use Strata\Data\Query\Query;

$page = 2;
$query = (new Query())
        ->setUri('posts')
        ->addParam('page', $page)
        ->setCurrentPage($page)
        ->setTotalResults('[meta][total_results]')
        ->setRootPropertyPath('[data]')
;

$query->setDataProvider($api);
$posts = $query->getCollection();
```

## Setting up a query

A query has a number of methods to set it up. All setup methods return the `Query` object so you can use a fluent interface and chain methods.

Where methods set property paths, see [property paths](../property-paths.md) for more information on how to use these.

### setDataProvider

Set the data provider to use with the query. This must be an object the `Strata\Data\Http\Rest` class.

* Parameters
    * `DataProviderInterface $dataProvider` 

### setUri

Set the URI to use for this query.

* Parameters
    * `string $uri` URI to run the query, relative to the data provider base URL

### setSubRequest

Mark the query as a sub-request, this suppresses errors in the HTTP request. 

* Parameters
    * `bool $subRequest` Defaults to true

### addParam

Add a single parameter to send with this query. This is sent as a GET param with the request.

* Parameters
    * `string $key` Param name
    * `mixed $value` Param value

### setParams

Set array of parameters to send with this query. These are sent as GET parameters with the request.  

* Parameters
    * `array $params` 

### setFieldParameter

The parameter to use for any fields set with `setFields`. This defaults to "fields".

* Parameters
    * `string $fieldParameter` 

### setFields

Set fields to return for this query. To use this method you must first set the field parameter. 

* Parameters
    * `array $fields` 

Example usage:

```php
$query->setFields(['name', 'title', 'email']);
```

Default functionality is to set the GET param`fields=name,title,email` for the request.

### setRootPropertyPath

Set the root property path to retrieve data from, e.g. `'[data]'` to return data from the array key 'data' in the response.

* Parameters
    * `string $path` Property path to root data element

### setMultipleValuesSeparator

Set the character to separate multiple values, defaults to ','

* Parameters
    * `string $multipleValuesSeparator` 

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

Some API responses set pagination data in the headers rather than the response data. Use this method to tell the query to 
retrieve pagination data from headers.

* Parameters
    * `bool $paginationDataFromHeaders` Defaults to true

## Caching

Also see [caching](../usage/caching.md).

### enableCache

Enables the cache for this request. This requires a cache to be set to the data provider to work. 

* Parameters
    * `?int $lifetime = null` Lifetime in seconds for the cache, if not set defaults to one hour
    
### disableCache

Disables the cache for this request. If no cache is set to the data provider then this does nothing.

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