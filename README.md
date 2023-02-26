# Data

The purpose of this package is to help read data from APIs and other external sources.

Features:

* Download data via HTTP APIs
* Throw exceptions on failed requests
* Supports REST and GraphQL
* Decode data from a variety of formats (e.g. JSON, Markdown)
* Automated debug information (logging and time profiling)
* Cache requests to increase performance
* Tools to help detect whether data has changed since last request 
* Transform data (e.g. convert source category to match local category name)

Planned for the future:

* Validate data items to see whether they contain required data properties
* Efficient bulk API queries via concurrent requests 
* Download data via local, FTP, S3 filesystem (via Flysystem)

## Status
Please note this software is in development, usage may change before the 1.0 release.

## Requirements

* PHP 7.4+
* [Composer](https://getcomposer.org/)

## Installation

Install via Composer:

```
composer require strata/data:^0.8
```

## Documentation

See [docs](docs/README.md) - these are currently being cleaned up and we plan to publish better docs for v0.10.

## Thanks to

https://developer.happyr.com/http-client-and-caching
