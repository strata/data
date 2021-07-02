# Making a request

## Setting up your data connection

Instantiate a [data provider](../retrieving-data/data-providers.md), for example using the generic Http data provider:

```php
use Strata\Data\Http\Http;

$api = new Http('https://example.com/api/');
```

If you need to setup any See authentication

## Requests

To make a request use a concrete method from the data provider, these are different for different types of providers. See [data providers](../retrieving-data/data-providers.md) for documentation.

The generic [Http data provider](../retrieving-data/http.md) supports request methods such as `get()`, `post()` and `exists()`.

The [Rest data provider](../retrieving-data/rest.md) automatically decodes data as JSON.

The [GraphQL data provider]() supports request methods such as `ping()` and `query()`.

an available Data class. RestApi supports things like get and post, GraphQL has a query method. Full details on available methods appear below.

```php
$response = $api->get('posts');
```

HTTP requests only run once you access data. For example:

```php
$item = $response->getContents();
```

At this point the HTTP request is made and if an error occurs an exception is thrown.

## OLD STUFF FROM HERE \#\#

### Base URI

You optionally pass a base URI when instantiating the API object. All subsequent API calls are then made relative to the base URI.

```php
$data = new RestApi('https://example.com/api/');
```

### get

Run a GET query.

#### Parameters

* `$uri (string)` API endpoint to query 
* `$queryParams (array)` Array of query params to send with GET request
* `$options (array)` - Array of options

See [HttpClientInterface](https://github.com/symfony/symfony/blob/5.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php) for a full list of options.

Please note if you set any query string parameters in `$options['query']` these will override any parameters with the same name in `$queryParams`.

#### Usage

```php
$response = $data->get('posts', ['page' => 2]);
```

### getOne

Get one item. Returns a `ResponseInterface` object.

#### Parameters

* `$uri (string)` API endpoint to query 
* `$id (string|int)` Identifier of item to return

#### Usage

```php
$response = $data->getOne('posts', 24);
```

#### How the URI is built

### list

Get a list of items. TODO

### post

Run a POST query.

#### Parameters

* `$uri (string)` API endpoint to query 
* `$postData (array)` API endpoint to query
* `$options (array)` - Array of options

Please note if you set any POST body data in `$options['body']` this will override $postData.

#### Usage

```php
$response = $data->get('posts', ['page' => 2]);
```

### head

Run a HEAD query.

#### Parameters

* `$uri (string)` API endpoint to query 
* `$options (array)` - Array of options

#### Usage

```php
$response = $data->head('posts');
```

### Other types of requests

You can run any other type of query via the `request` method. Returns a `ResponseInterface` object.

#### Parameters

* `$method (string)` - Method to run
* `$uri (string)` - URI to send request to, relative to base URI
* `$options (array)` - Array of options

#### Usage

```php
$response = $data->request('PUT', 'posts', ['body' => ['name' => 'Jean-Luc Picard']]);
```

## Responses

Responses are returned as an object of type [ResponseInterface](https://symfony.com/doc/current/components/http_client.html#processing-responses).

### Returning JSON data

Use default HttpClient functionality to return an array of JSON data. Throws a `JsonException` on invalid JSON data.

```php
$content = $response->toArray();
```

### getHeader

Return one single header value, or an array of values if there are more than one.

#### Parameters

* `$response (ResponseInterface)` - Response to extract header from
* `$header (string)` - Header to return \(converted to lower case\)

#### Usage

```php
$header = $data->getHeader($response, 'X-WP-Total');
```

## Throwing exceptions on a failed request

By default the API class throws an exception on failed HTTP requests, this is defined as a request that returns the expected HTTP status code \(by default 200\).

If the status code is in the 4xx range a `NotFoundException` is thrown, otherwise a `FailedRequestException`.

### Changing expected status code

You can change the expected status code via:

```php
$data = new RestApi('https://example.com/');
$data->expectedResponseCode(202);
```

### Do not throw exceptions

You can change the default behaviour to throw exceptions and you can check whether a request is successful via `isSuccess()`.

```php
$data->throwOnFailedRequest(false);

$response = $data->get('posts');
$success = $data->isSuccess();
```

## Changing permissions

By default APIs are read-only, this is by design to ensure you don't accidentally write data unwittingly. If you want to write or delete data you need to set this up with you instantiate the API class.

The following permissions exist:

* `Permissions::READ`
* `Permissions::WRITE`
* `Permissions::DELETE`

Example setting permissions to read and write, but not delete:

```php
use Strata\Data\Provider\RestApi;
use Strata\Data\Permissions;

$data = new RestApi('https://example.com/', Permissions::READ | Permissions::WRITE);
```

Or via:

```php
$data->setPermissions(new Permissions(Permissions::READ | Permissions::WRITE));
```

