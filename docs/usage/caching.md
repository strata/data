# Caching

When making requests to external data providers you may want to cache data responses so you can reduce the number of outgoing HTTP requests.

Features include:

* Automatic caching for data requests
* Hydrates cached HTTP responses back into a response object  
* `isHit()` method on HTTP responses to help detect when cache is used
* Enable and disable cache for different types of data requests
* Set custom cache lifetime and tags for different data requests
* For concurrent requests saves cache via the [persist queue](https://symfony.com/doc/current/components/cache/cache_pools.html#saving-cache-items) to increase performance
* Probability-based pruning of expired cache entries to increase performance

Also see [Data history](../advanced-usage/data-history.md), which also uses the cache as a storage engine, to detect whether new content has changed or is new.

## Setup

Pass a [PSR-6 compatible cache adapter](https://symfony.com/doc/current/components/cache/cache_pools.html#creating-cache-pools) to the `setCache()` method to enable caching. It's recommended to use a cache that supports tagging.

```php
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;

$api->setCache(new FilesystemTagAwareAdapter());
```

This sets and enables the cache for all data requests.

Please note, setting a cache lifetime on the cache adapter has no effect since this is overwritten in the `DataCache` class. See how to [alter the cache lifetime](caching.md#cache-lifetime).

You can also pass further arguments to customise the cache, for example setting the cache namespace and cache path:

```php
$cache = new FilesystemTagAwareAdapter('cache', 0, __DIR__ . '/path/to/cache/folder');
$api->setCache($cache);
```

## Using the cache

The data cache automatically caches data requests if the request is cacheable. For HTTP data requests this is determined as:

* Cache is enabled
* GET or HEAD requests

For GraphQL queries this is determiend by:

* Cache is enabled
* GET, HEAD or POST requests

To cache data simply set the cache and then make your data request:

```php
$result = $api->get('my-data');
```

This automatically saves items to the cache via the data provider with a default cache lifetime of one hour.

For example, these requests all return exactly the same data when caching is enabled. Only the first request is actually sent to httpbin.org

```php
$api = new RestApi('http://httpbin.org/');
$api->setCache(new FilesystemTagAwareAdapter());

// This returns a random UUID from httpbin.org
echo PHP_EOL . $api->get('uuid')->toArray()['uuid'];
echo PHP_EOL . $api->get('uuid')->toArray()['uuid'];
```

You can also disable the cache for future data requests:

```php
$api->disableCache();
```

And re-enable it again when you want to use it:

```php
$api->enableCache();
```

This allows more fine-grained caching rules, where you may want to cache some data requests and not others.

If you wish you can also reset the cache to it's last set value. This is useful if you want to enable the cache for one
query only but don't want to keep track of whether the cache is currently enabled or disabled.

```php
$api->enableCache();

// Run query...

$api->resetEnableCache();
```

### Working out if an HTTP response has been cached

All HTTP data requests return `CacheableResponse` response objects. This decorates the standard HTTP response and adds the method `isHit()` to a response so you can work out if response data was returned from the cache or was requested fresh from the origin.

```php
$response = $api->get('uuid');

if ($response->isHit()) {
    echo "HIT";
} else {
    echo "MISS";
}
```

### Caching HTTP responses

When an HTTP response is returned from the cache this is hydrated via the Symfony `MockResponse` class and a valid HTTP response object is returned. This restores the following data from a response:

* Status code
* Response headers
* Body content

### Cache lifetime

By default the Data Cache caches data for up to one hour. You can set a custom cache lifetime when enabling the cache, by passing the number of seconds to store data in the cache:

```php
$api->enableCache(300);
```

You can also use the `CacheLifetime` class, which has a set of convenience constants to set cache lifetime in seconds: `CacheLifetime::MINUTE`, `CacheLifetime::HOUR`, `CacheLifetime::DAY`, `CacheLifetime::WEEK`, `CacheLifetime::MONTH`, `CacheLifetime::YEAR`.

```php
use Strata\Data\Cache\CacheLifetime;
$api->enableCache(CacheLifetime::MINUTE * 5);
```

You can also set the cache lifetime via the cache directly:

```php
$api->getCache()->setLifetime(CacheLifetime::MINUTE * 5);
```

### Adding tags

If your cache adapter supports tags, you can set tags to be saved against all future data requests. If your cache adapter does not support tags this will throw a `CacheException`.

Pass an array of tags to save to cache items:

```php
$data->setCacheTags(['my-tag', 'second-tag']);
```

These tags are then set for all future cached data via the Data Cache.

To stop tags being saved against cache items, simply call the method without any arguments. This empties any previously set cache tags and disables tagging for future data requests.

```php
$data->setCacheTags();
```

## Invalidating the cache

### Using the Data Cache directly

Convenience methods exist in data providers to save items in the cache, all other functionality must be accessed via the cache object itself. To directly access the DataCache use:

```php
/** @var Strata\Data\Cache\DataCache $cache */
$cache = $data->getCache();
```

### Expiration based invalidation

By default, all data stored by `DataCache` has a cache lifetime and cache items are removed after this lifetime has expired. However, some cache adapters \(e.g. filesystem\) only expire cache items when they are requested, see [pruning old cache items](caching.md#pruning-old-cache-items) on how to solve this problem.

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

Some cache pools do not have automated mechanisms for pruning expired caches which under certain circumstances can cause diskpsace or memory usage issues. The `FilesystemAdapter` does not remove expired cache items until an individual item is explicitly requested and determined to be expired.

This can be worked around by purging the cache on a regular basis. The DataCache can be purged via:

```php
$data->getCache()->purge();
```

By default, this runs a purge request on all items in a cache. To help increase performance, you can choose to only run a purge request a certain percentage of times. This helps if you want to call purge frequently but only run it every so often.

To do this, pass the `$probability` argument which represents a number between 0 \(never runs\) to 1 \(always runs\).

For example, to run 1 time in 10:

```php
$data->getCache()->purge(0.1);
```

The following cache adapters support purge requests:

* [Filesystem Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html)
* [PDO & Doctrine DBAL Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/pdo_doctrine_dbal_adapter.html)
* [PHP Array Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/php_array_cache_adapter.html)
* [PHP Files Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/php_files_adapter.html)

