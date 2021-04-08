# Http data provider

## Setting up your data connection

Setup the Http data provider, you need to set a base URI as the first argument:

```php
use Strata\Data\Http\Http;

$api = new Http('https://example.com/');
```

This then runs all future requests relative to this base URI. For example:

```php
// Makes a request to https://example.com/my-url
$response = $api->get('my-url');
```

To change the base URI just use:

```php
$api->setBaseUri('https://new.com/');
```

## Configuration
You can configure the data provider with default HTTP options in a number of ways. 

You can pass `$options` array when creating a new `Http` object:

```php
$api = new Http('https://example.com/', $options);
```

You can also pass `$options` array when setting the current base URI: 

```php
$api->setBaseUri('https://example.com/', $options);
```

Please note this overwrites any previously set default HTTP options.

See [Symfony HttpClient configuration](https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client) for a reference
for all valid options. Common options appear below.

### Authentication

Set basic authentication:

```php
$api = new Http('https://example.com/', [
    'auth_basic' => 'the-username:the-password'
]);
```

Set a bearer authentication token:

```php
$api = new Http('https://example.com/', [
    'auth_bearer' => 'my-token'
]);
```

See [Symfony HTTP authentication](https://symfony.com/doc/current/http_client.html#authentication).

It's also common for APIs to also set API tokens via query string parameters (see below).

### Query String Parameters

You can set any query string params to send with all requests, e.g. an auth token, via the `query` associative array:

```php
$api = new Http('https://example.com/', [
    'query' => [
        'auth_token' => 'ABC123'
    ]
]);
```

### Headers

You can set any headers to send with all requests, e.g. the user agent, via the `headers` associative array:

```php
$api = new Http('https://example.com/', [
    'headers' => [
        'User-Agent' => 'my-custom-application'
    ]
]);
```

## Symfony HttpClient

The `Http` data provider uses [Symfony HttpClient](https://symfony.com/doc/current/http_client.html) to make requests.
This is automatically created when it's needed. If you need to, you can set this up yourself and set via `setHttpClient()`.

An example usage would be if you want to set up a [scoping client](https://symfony.com/doc/current/http_client.html#scoping-client)
to setup default options for different URL patterns:

```php
$client = HttpClient::create();
$client = new ScopingHttpClient($client, [
    // the options defined as values apply only to the URLs matching
    // the regular expressions defined as keys
    'https://api\.github\.com/' => [
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'token ' . $githubToken,
        ],
    ],
    // ...
]);
$api->setHttpClient($client);
```

## Making requests

### get

Run a GET request.

* Parameters
    * `string $uri` URI relative to base URI
    * `array $queryParams` Array of query params to send with GET request
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns an object of type `Strata\Data\Http\Response\CacheableResponse`

### post

Run a POST request.

* Parameters
    * `string $uri` URI relative to base URI
    * `array $postData` Array of data to send with POST request
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns an object of type `Strata\Data\Http\Response\CacheableResponse`

### head

Run a HEAD request.

* Parameters
    * `string $uri` URI relative to base URI
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns an object of type `Strata\Data\Http\Response\CacheableResponse`

### Cacheable responses

Most requests return responses as objects of type `CacheableResponse`. These are identical to the standard Symfony response
object with one additional method `isHit()` which lets you know whether this response was returned from the cache or run
live.

```php
$response = $api->get('url-path');

if ($response->isHit()) {
    echo "HIT";
} else {
    echo "MISS";
}
```

See [caching](../caching.md) for more.

### exists

You can use the `exists()` method to simply test a URL endpoint returns a 200 successful status code.

```php
$api = new Http('https://http2.akamai.com/demo/');
$result = $api->exists('tile-101.png');
```

* Parameters
    * `string $uri` URI relative to base URI
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns a boolean

### getRss

You can use the `getRss()` method to retrieve and decode an RSS feed. 

```php
$http = new Http('https://example.com/');

$feed = $http->getRss('feed.rss');

foreach ($feed as $item) {
    $title = $item->getTitle();
    $link = $item->getLink();
}
```
* Parameters
    * `string $uri` URI relative to base URI
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns an iterable object of type `Laminas\Feed\Reader\Feed\FeedInterface`

This uses [Laminas Feed](https://docs.laminas.dev/laminas-feed/reader/) to decode the RSS feed, this supports RSS and
Atom feeds of any version, including RDF/RSS 1.0, RSS 2.0, Atom 0.3, and Atom 1.0.

See docs on [retrieving feed information](https://docs.laminas.dev/laminas-feed/reader/#retrieving-feed-information) 
and [retrieving entry item information](https://docs.laminas.dev/laminas-feed/reader/#retrieving-entryitem-information).

## Concurrent requests

You can run a bulk set of GET requests quickly and efficiently by passing an array of URIs to the `getConcurrent()` method.
This returns a [generator](https://www.php.net/generators.overview) which can be looped over with `foreach`.

```php
/** @var ResponseInterface $response */
foreach ($api->getConcurrent($uris) as $response) {
    // ... 
}
```

### Manually running concurrent requests

You can also manually run concurrent requests by making a request in two steps: first prepare the request, then run it.

Using the example in the [Symfony docs for concurrent requests](https://symfony.com/doc/current/http_client.html#concurrent-requests)
this can be done as so:

```php
$api = new Http('https://http2.akamai.com/demo/');
$responses = [];
for ($i = 0; $i < 379; ++$i) {
    $uri = "tile-$i.png";
    $responses[] = $api->prepareRequest('GET', $uri);
}

foreach ($responses as $response) {
    $response = $api->runRequest($response);
    // ...
}
```

The `runRequest()` method checks the status code to ensure the request has run successfully.

## Running requests manually

All requests run the `prepareRequest()` and `runRequest()`, this is to help support [concurrent requests](#concurrent-requests).
You can use these methods directly, but it's recommended to use a helper method above such as `get()`.

### prepareRequest

Prepare request (but do not run it).

* Parameters
    * `string $method` HTTP method
    * `string $uri` URI relative to base URI
    * `array $options` HTTP options to use for this request (these override default HTTP options)
* Returns an object of type `Strata\Data\Http\Response\CacheableResponse`

### runRequest

Run a request (note Symfony HttpClient is lazy loading so this still won't actually run a HTTP request until content is accessed).

* Parameters
    * `CacheableResponse $response` Response to run
* Returns an object of type `Strata\Data\Http\Response\CacheableResponse`

## Suppress exceptions on error
The default behaviour of is to throw exceptions on HTTP or JSON decoding errors (e.g. if a request does not return a 200 status),
though this can be suppressed. It can be useful to do this for sub-requests which you don't want to stop the master HTTP request.

You can do this via:

```php
$data->suppressErrors();
```

Which disables exceptions for `TransportExceptionInterface`, `HttpExceptionInterface` and `FailedRequestException` exceptions.

You can turn off this error suppression via:

```php
$data->suppressErrors(false);
```