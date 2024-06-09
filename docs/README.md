# Introduction

A simple way to manage data retrieval from APIs and other sources. This package is built using Symfony components and can 
be used with any PHP application, Symfony, Laravel or plain PHP.

You can use this with the [frontend](https://github.com/strata/frontend) package to help you build a frontend website.

You can:

* Read data from REST and GraphQL APIs
* Authenticate with APIs
* Handle errors consistently
* Cache requests to increase performance
* Decode data from a variety of formats (e.g. JSON, Markdown)
* Transform data (e.g. map a category name)
* Work out if data has changed since the last request

## Changelog

All notable changes to strata/data are documented on [GitHub](https://github.com/strata/data/blob/main/CHANGELOG.md).

## Retrieving data

Strata Data has a lightweight architecture.

Data is retrieved via a **[Data provider](retrieving-data/data-providers.md)** . This could be a REST API, GraphQL API, or other source.
Data providers wrap up data reading functionality along with support for **[caching](advanced-usage/caching.md)**, decoding raw data, error handling and helpers to make development easier.

You use **[queries](retrieving-data/query.md)** to make running a data request easier.
A **[query manager](retrieving-data/query-manager.md)** can be used to manage multiple queries.

Single data is returned as either an object or array.

A collection of data is returned as a collection object, containing either objects or arrays.

## Changing data

Returned data can be modified via **[transformers](changing-data/transformers.md)** or **[mappers](changing-data/mapping.md)**. Transformers change data, while mappers map data to an object or array.

**[Pagination](changing-data/mapping#setting-pagination)** can be automated when you return a collection of results.

## Advanced usage

You can [validate data](advanced-usage/validating.md) to check it is valid. This is useful if you need to check data before you use it (e.g. a data import). 

[Data history](data-history.md) can be used to help determine if retrieved data has changed since last access.

