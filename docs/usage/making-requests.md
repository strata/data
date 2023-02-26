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
