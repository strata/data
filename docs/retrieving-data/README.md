# Retrieving data

There are two techniques to retrieving data with Strata Data: using [data providers](data-providers.md) or [queries](query.md).

## Data providers

[Data providers](data-providers.md) are classes designed to integrate against a type of data provider. They provide 
the core functionality to send data requests, deal with errors, support for caching and supressing errors for sub-requests,
decode response data, and add custom events via the event listener.

You can run any type of request via data providers, so data providers are better for tasks such as saving new data to an API.

Requests look similar to a normal HTTP request:

```
// Setup
$api = new Rest('https://example.com/api/');

// Run a GET request 
$response = $api->get('news', ['limit' => 50, 'page' => 2]);

// Return a decoded array of data
$data = $api->decode($response);
```

You can find out more at:
* [Data providers](data-providers.md)
* [HTTP data provider](http.md)
* [GraphQL data provider](graphql.md)

## Queries

Query classes provide an object-orientated way to build queries and run them. They use data providers to run the actual requests
and use [mapping](../changing-data/mapping.md) to help parse data from HTTP responses. 

Queries are optimised for reading data, so are best used when you want to retrieve data. If you want to save data, then 
it's best to use a data provider.

Requests look like:

```php
// Setup
$api = new Rest('https://example.com/api/');

// Build query
$query = new Query();
$query->setUri('news')
      ->setParams(['page' => 2])
      ->setRootPropertyPath('[data]')
      ->setDataProvider($api);

// Return data as an array
$data = $query->get();

// Return collection of data (requires collection setup to be defined in query)
$collection = $query->getCollection();

// Or return the HTTP response
$response = $query->getResponse();
```

You can find out more at:
* [Running queries](query.md)
* [Running GraphQL queries](graphql.md)

### Fluent interface

Queries use a fluent interface to make building queries easier. This means methods that set data (and are used for setting 
up the query) can be chained since they return the current object. This allows you to do things like: 

```
$query = new Query();
$query->setUri('news')
      ->setParams(['page' => 2])
      ->setRootPropertyPath('[data]');
```

You can also surround the `new Query()` statement in brackets to chain everything:

```
$query = (new Query())
     ->setUri('news')
     ->setParams(['page' => 2])
     ->setRootPropertyPath('[data]');
```

### Query manager

[Query managers](query-manager.md) is a wrapper class that can be used to add data providers and run multiple queries 
at once. It supports running concurrent queries, you can apply cache settings to all queries at once (e.g. cache tags).
It provides a simpler way to run multiple queries at once.

Requests look like this:

```
// Setup
$manager = new QueryManager();
$manager->addDataProvider('example', new Rest('https://example.com/api/'));

// Add query
$query = new Query();
$query->setUri('news')
      ->setTotalResults('[total_results]')
      ->setParams(['limit' => 50, 'page' => 2]);
$manager->add('news', $query);

// Run query and return a collection of results
$collection = $manager->getCollection('news');
```

You can find out more at:
* [Using a Query Manager to run queries](query-manager.md)

### Custom query classes
You can also write your own custom query classes to encapsulate common functionality for your API, making building queries easier.

For example, a fictional `NewsQuery` class may set the URI and root property path. Your request will now look like:

```
// Add query
$query = new NewsQuery();
$query->addParam('page', $page);

// Run query and return a collection of results
$collection = $query->getCollection();
```

You can find out more at:
* [Writing custom query classes](custom-query-classes.md)
