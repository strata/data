# Getting started

## Requirements

* PHP 8.1+
* [Composer](https://getcomposer.org/)

## Installation

Install via Composer:

```
composer require strata/data:^0.9
```

## Access data from a REST API

Set up your API connection, caching all responses for 1 hour (the default lifetime):

```php
use Strata\Data\Http\Rest;

$api = new Rest('https://httpbin.org/');
$api->enableCache();
```

Run a GET query on the `/anything` endpoint and return the JSON decoded response as an array.
The HTTP query is only run when data is accessed. If there's an error an exception is thrown.

```php
$response = $api->get('anything', ['my-name' => 'Thomas Anderson']);
$data = $api->decode($response);
```

Get the raw response content as a string:

```php
$content = $response->getContent();
```

Find out if the API request was successful:

```php
if ($response->isSuccessful()) {
    echo 'Request was successful';
}
```

Find out if the API response was cached:

```php
if ($response->isHit()) {
    echo sprintf('Data has been cached for %d seconds', $response->getAge());
}
```

## Access data from a GraphQL API

Set up your API connection, caching all responses for 1 hour:

```php
use Strata\Data\Http\GraphQL;

$api = new GraphQL('https://www.example.com/api/');
$api->enableCache();
```

Run a GraphQL query and return data as an array. If there's an error an exception is thrown. 

```php
$query = <<<'EOD'
query Page($uri: [String], $site: [String]) {
    entry(uri: $uri, site: $site) {
        id
        typeHandle
        status
        uri
        title
        language
        postDate
        content
}
EOD;
$variables = [
    'site' => 1,
    'uri' => '/about-us',   
];
$response = $api->query($query, $variables);
$data = $api->decode($response);
```

## Build API requests with queries

It can be easier to build a REST API query via a query object. 

```php

It is easier to use a query object to run API requests. 

```php
use Strata\Data\Query\Query;

$page = 2;
$query = Query::uri('posts')
        ->addParam('page', $page)
        ->setCurrentPage($page)
        ->setTotalResults('[meta][total_results]')
        ->setRootPropertyPath('[data]')
        ->setDataProvider($api);
```

Return a collection of results, along with pagination. Responses are automatically decoded (each item is returned as an array).

```php
$posts = $query->getCollection();

$pagination = $collection->getPagination();
$totalResults = $pagination->getTotalResults();
```

## Run multiple API requests via the Data Manager

You can use a Data Manager to run multiple queries.

```php
use Strata\Data\Query\QueryManager;
use Strata\Data\Http\Rest;

$manager = new QueryManager();

// The first argument is the data provider name
$manager->addDataProvider('internal_api', new Rest("https://example1.com/api/"));

// Add a second data provider
$manager->addDataProvider('cms', new Rest("https://example2.com/api/"));

// Add your first query to run against internal API
$query = Query::uri('content')
         ->setParam('id', 24);
$manager->add('content', $query);

// Add a second query to run against CMS API
$query = Query::uri('news')
         ->setParam('limit', 25);
$manager->add('news', $query, 'cms');

// This runs both queries concurrently and returns data for the content query
$data = $manager->get('content');

// Return data for the news query
$data = $manager->getCollection('news');
$pagination = $data->getPagination();
```

## More information

Find out more:
* TODO
* 