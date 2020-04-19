# Testing API requests

When testing HTTP requests you need to create mock responses based on what would actually be returned from a real HTTP 
request. Symfony's HTTPClient has support for [testing HTTP requests](https://symfony.com/doc/current/components/http_client.html#testing-http-clients-and-responses).

## MockResponseFromFile

Allows you to load a mock request from file.

### Parameters

* `$filename (string)` File to load mock response from, automatically adds .response.json for body file 

### Description 

Body file is loaded from `{$filename}.response.json`

The optional info file is loaded from `{$filename}.info.php` and must contain the `$info` variable (array). By default 
mock responses return a 200 status code which you can change by setting the `$info` array.

See [ResponseInterface::getInfo()](https://github.com/symfony/symfony/blob/master/src/Symfony/Contracts/HttpClient/ResponseInterface.php) 
for possible info, the most common are:

* `http_code (int)` - the last HTTP response code
* `response_headers (array)` - an array of response headers

### Usage

The following code loads `./responses/api-test.response.json` and if it exists `./responses/api-test.info.php` to create 
a mock response.

```php
use Strata\Data\Api\MockResponseFromFile;

$responses = [
    new MockResponseFromFile(__DIR__ . '/responses/api-test'),
];
 
$api = new RestApi('https://example.com/');
$api->setClient(new MockHttpClient($responses, 'https://example.com/'));

$response = $api->get('test');

// Outputs:404
echo $response->getStatusCode();

// Outputs: JSON response content
echo $response->getContent();

// Outputs: 0
$headers = $response->getHeaders();
echo $headers['x-total-results'][0];
```

./responses/api-test.response.json

```json
{
  "message": "PAGE NOT FOUND"
}
```

./responses/api-test.info.php

```php
<?php
$info = [
    'http_code' => 404,
    'response_headers' => [
        'X-Total-Results' => '0'
    ]
];

```