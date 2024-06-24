# Data

A simple way to manage data retrieval from APIs and other sources. This package is built using Symfony components and can
be used with any PHP application, Symfony, Laravel or plain PHP. 

You can:

* Read data from REST and GraphQL APIs
* Authenticate with APIs
* Handle errors consistently
* Cache requests to increase performance
* Decode data from a variety of formats (e.g. JSON, Markdown)
* Transform data (e.g. map a category name)
* Work out if data has changed since the last request

See the [documentation](docs/README.md) for more.

You can use this with the [frontend](https://github.com/strata/frontend) package to help you build a frontend website.

## Status
Please note this software is in development, usage may change before the 1.0 release.

## Requirements

* PHP 8.1+
* [Composer](https://getcomposer.org/)

## Installation

Install via Composer:

```
composer require strata/data:^0.9
```

## Thanks to

* [Symfony](https://symfony.com/)
* https://developer.happyr.com/http-client-and-caching
