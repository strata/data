# Making requests with Strata Data

## Setting up your data connection

Instantiate a data class (currently RestApi or GraphQL) by passing the base URL.

```php
use Strata\Data\Http\RestApi;

$data = new RestApi('https://example.com/api/');
```

## Requests

To make a request use a concrete method from an available Data class. RestApi supports things like get and post, GraphQL 
has a query method. Full details on available methods appear below.

```php
$response = $data->get('posts');
```

HTTP requests only run once you access data. For example: 

```php
$item = $response->getItem();
```

At this point the HTTP request is made and if an error occurs an exception is thrown. 

### Suppress exceptions on error
The default behaviour is to throw exceptions on HTTP or JSON decoding errors (e.g. if a RestApi get request does not return a 200 status), 
though this can be suppressed. It can be useful to do this for sub-requests which you don't want to stop the master HTTP request.

You can do this via:

```php
$data->suppressErrors();
```

Which disables exceptions for TransportExceptionInterface, HttpExceptionInterface and FailedRequestException exceptions.

You can switch back to the default behaviour via:

```php
// Reset back to last value
$data->resetSuppressErrors();

// Or set it explicitly:
$data->suppressErrors(false);
```

## Running concurrent requests

You can run a bulk set of GET requests quickly and efficiently by passing an array of URIs to the `getConcurrent()` method. 
This returns a generator which can be looped over with `foreach`.
 
```php
/** @var ResponseInterface $response */
foreach ($data->getConcurrent($uris) as $response) {
    // ... 
}
```

You can also manually run concurrent requests by making a request in two steps: first prepare the request, then run it.

Using the example in the [Symfony docs for concurrent requests](https://symfony.com/doc/current/http_client.html#concurrent-requests) 
this can be done as so:

```php
$data = new RestApi('https://http2.akamai.com/demo/');
$responses = [];
for ($i = 0; $i < 379; ++$i) {
    $uri = "tile-$i.png";
    $responses[] = $data->prepareRequest('GET', $uri);
}

foreach ($responses as $response) {
    $response = $data->runRequest($response);
    // ...
}
```

The `runRequest()` method checks the status code to ensure the request has run successfully. 

## Testing a URL exists

You can use the `exists()` method to simply test a URL endpoint returns a 200 successful status code.

```php
$api = new RestApi('https://http2.akamai.com/demo/');
$result = $api->exists('tile-101.png');
```

## OLD STUFF FROM HERE ##

### Base URI
You optionally pass a base URI when instantiating the API object. All subsequent API calls are then made relative to the 
base URI.

```php
$data = new RestApi('https://example.com/api/'); 
```

### get
Run a GET query.

#### Parameters

* `$uri (string)` API endpoint to query 
* `$queryParams (array)` Array of query params to send with GET request
* `$options (array)` - Array of options

See [HttpClientInterface](https://github.com/symfony/symfony/blob/5.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php) 
for a full list of options.

Please note if you set any query string parameters in `$options['query']` these will override any parameters with the 
same name in `$queryParams`.

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
* `$header (string)` - Header to return (converted to lower case)

#### Usage

```php
$header = $data->getHeader($response, 'X-WP-Total');
```

## Throwing exceptions on a failed request

By default the API class throws an exception on failed HTTP requests, this is defined as a request that 
returns the expected HTTP status code (by default 200).

If the status code is in the 4xx range a `NotFoundException` is thrown, otherwise a `FailedRequestException`.

### Changing expected status code

You can change the expected status code via:

```php
$data = new RestApi('https://example.com/');
$data->expectedResponseCode(202);
```

### Do not throw exceptions

You can change the default behaviour to throw exceptions and you can check whether a request is successful via 
`isSuccess()`.

```php
$data->throwOnFailedRequest(false);

$response = $data->get('posts');
$success = $data->isSuccess();
```

## Changing permissions

By default APIs are read-only, this is by design to ensure you don't accidentally write data unwittingly. If you want to 
write or delete data you need to set this up with you instantiate the API class.

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



