# Caching

When making requests to external data providers you may want to cache data responses so you can reduce the number of 
outgoing HTTP requests. 

Features include:

* Supports PSR-6 caching
* Tag based cache invalidation
* Auto-pruning of expired cache entries
* [Data history](data-history.md) to detect whether new content has changed or is new

## Setup

Set a PSR-6 compatible cache to initialise the cache. It's recommended to use a cache that supports tagging. 

Please note, setting a cache lifetime on the cache adapter has no effect since this is overwritten in the DataCache class. See 
below on how to [alter the cache lifetime](#cache-lifetime).  

```php
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;

/** @var \Strata\Data\DataInterface */
$cache = new FilesystemTagAwareAdapter('cache', 0, __DIR__ . '/path/to/cache/folder');
$data->setCache($cache);
```

## Using the cache

To cache data simply enable the cache and then make your data request:

```php
$data->enableCache();
$result = $data->get('my-data');
```

This automatically saves items to the cache via the data provider. 

For example these requests all return exactly the same data when caching is enabled. Only the first request is actually
sent to the server.

TODO DOES NOT WORK! Test this now!

```php
$data = new RestApi('http://httpbin.org/');
$data->setCache(new FilesystemTagAwareAdapter('cache', 0, __DIR__ . '/path/to/cache/folder');
$data->enableCache();

// This returns a random UUID from httpbin.org
echo $data->get('uuid')->toArray();
echo $data->get('uuid')->toArray();  
```

To disable the cache use:

```php
$data->disableCache();
```

This allows more fine-grained caching rules, where you may want to cache some data requests and not others.

### Cache lifetime

By default the Data Cache caches data for up to one hour. You can set a custom cache lifetime when enabling the cache, by 
passing the number of seconds to store data in the cache:

```php
$data->enableCache(300);
```

You can also use the `CacheLifetime` class, which has a set of convenience constants to set cache lifetime in seconds: 
`CacheLifetime::MINUTE`, `CacheLifetime::HOUR`, `CacheLifetime::DAY`, `CacheLifetime::WEEK`, `CacheLifetime::MONTH`, 
`CacheLifetime::YEAR`.

```php
use Strata\Data\Cache\CacheLifetime;
$data->enableCache(CacheLifetime::HOUR * 2);
```

### Adding tags

If your cache adapter supports tags, you can set tags to be saved against all future data requests. If your cache adapter 
does not support tags this will throw a `CacheException` exception.

Pass an array of tags to save to cache items:

```php
$data->setCacheTags(['my-tag', 'second-tag']);
```

These tags are then set for all future cached data via the Data Cache.

To stop tags being saved against cache items, simply call the method without any arguments. This empties any previously 
set cache tags and disables tagging for future data requests.

```php
$data->setCacheTags();
```

### Accessing the cache

Convenience methods exist in data providers to save items in the cache, all other functionality must be accessed on the 
cache object itself. To directly access the DataCache use:

```php
/** @var DataCache $cache */
$cache = $data->getCache();
```

## Invalidating the cache

### Expiration based invalidation

By default, all data stored by `DataCache` has a cache lifetime and cache items are removed after this lifetime has expired. 

### Key invalidation

You can remove individual cache items via:

```php
$data->getCache()->deleteItem($key);
```

Or to delete multiple items:

```php
$data->getCache()->deleteItems([$key1, $key2]);
```

### Tag-based invalidation

You can remove all cache items stored against a tag via:

```php
$data->getCache()->invalidateTags(['custom-tag']);
```

### Delete everything

To delete everything from the cache:

```php
$data->getCache()->clear();
```

### Pruning old cache items

Some cache pools do not have automated mechanisms for pruning expired caches which under certain circumstances can cause 
diskpsace or memory usage issues. The `FilesystemAdapter` does not remove expired cache items until an individual item 
is explicitly requested and determined to be expired.

This can be worked around by purging the cache on a regular basis. The DataCache can be purged via:

```php
$data->getCache()->purge();
```

By default, this runs a purge request on all items in a cache. To help increase performance, you can choose to only run 
a purge request a certain percentage of times. This helps if you want to call purge frequently but only run it every so 
often.

To do this, pass the `$probability` argument which represents a number between 0 (never runs) to 1 (always runs).  

For example, to run 1 time in 10:

```php
$data->getCache()->purge(0.1);
```
