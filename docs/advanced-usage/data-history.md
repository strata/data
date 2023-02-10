# Data History

You can make use of Data History to determine if data you are fetching from an external data provider has changed since the last time you fetched it. This is helpful to avoid unnecessary processing for data import jobs.

## How it works

As you read in data from a data provider, add this to the history log with a key representing a unique identifier along with the data representing the raw data/content.

You can then run tests to check whether:

* Is the data new \(there are no previous history logs\)
* Is the data changed \(the current fetched data is different to the last item in the history log\)

You can also store additional metadata in the history log which can be retrieved.

The Data History is stored in a PSR-6 compatible cache. Rather than store raw data, the system stores a unique hash of the raw data/content. This is used to efficiently check whether content has changed.

## Setup

```php
use Strata\Data\Cache\DataHistory;

$history = new DataHistory($cache, $cacheLifetime);
```

Pass a PSR-6 compatible cache adapater, see [supported adapters](https://symfony.com/doc/current/components/cache/cache_pools.html). The example below uses the filesystem adapter. Please note, setting a cache lifetime on the adapter has no effect since this is overwritten in the DataHistory class.

```php
$history = new DataHistory(new FilesystemAdapter('history', 0, __DIR__ . '/path/to/cache/folder'));
```

### Cache lifetime

You can pass a second argument to set the cache lifetime of history log items \(one log item is stored per unique identifier\). E.g.

```php
$history = new DataHistory(new FilesystemAdapter('history', 0, __DIR__ . '/path/to/cache/folder'), CacheLifetime::YEAR);
```

The system stores one cache entry per unique identifier. The cache system itself stores files for up to the cache lifetime, which by default is set to two months. It's important the cache lifetime is higher than the max history days.

You can also set the cache lifetime via `setCacheLifetime($lifetime)`:

```php
$history->setCacheLifetime(CacheLifetime::YEAR);
```

The `CacheLifetime` class has a set of convenience constants to set cache lifetime in seconds: `CacheLifetime::MINUTE`, `CacheLifetime::HOUR`, `CacheLifetime::DAY`, `CacheLifetime::WEEK`, `CacheLifetime::MONTH`, `CacheLifetime::YEAR`.

### Max history days

For each unique piece of data multiple history logs are kept, representing the last time a piece of data was retrieved from a external data source. These logs are pruned every so often, to keep storage efficient. By default up to 30 days are kept, which is OK for most purposes.

If your schedule to import data is longer than every 30 days you'll need to set a longer max history via `setMaxHistoryDays()`. For example, to set the max history days to 60:

```php
$history->setMaxHistoryDays(60);
```

## Testing your data is new or changed

First fetch your data from your external data provider. Then you can test whether this is new via:

```php
if ($history->isNew('unique_key', 'raw data')) {
    // do something
}
```

This returns true if no history log items exist and this is considered new data.

You can test whether your data is changed via:

```php
if ($history->isChanged('unique_key', 'raw data')) {
    // do something
}
```

This returns true if the data is different to the last entry in the data history log, or if no history items exist.

You can also use this reverse test, to check whether data is identical to the last entry in the history log:

```php
if ($history->isIdentical('unique_key', 'raw data')) {
    // do something
}
```

## Adding a new history log

To add items to the history log you first need to add it:

```php
$history->add('unique_key', 'raw data');
```

For performance, this does not immediately save to the cache since it's likely you'll be processing lots of data. At the end of your data processing make sure you save the Data History to the cache via:

```php
$history->commit();
```

If you forget to run this command, then the Data History items are not saved.

### Only save a new log item when the data has changed

It's important to only save new history log items if data changes, otherwise the `isChanged()` test will fail on future requests.

### Complete example

A complete example of checking and storing history log data:

```php
if ($history->isChanged('unique_key', 'raw data')) {
    // Process new data
    // ...

    $history->add('unique_key', 'raw data');
}
```

Or an alternative pattern, where you skip data that has not changed:

```php
foreach ($lotsOfData as $item) {
    if ($history->isIdentical($item['id'], $item['data'])) {
        // skip data, the example here is a continue to move to the next item in a loop
        continue;
    }

    // Process new data
    // ...
    $history->add('unique_key', 'raw data');
}
```

And remember to save the history cache at the end of data processing!

```php
$history->commit();
```

### Metadata

You can add metadata to the history log, which is an array of key, value pairs. For example:

```php
$history->add('unique_key', 'raw data', ['my_field' => 'my value']);
```

## Getting the last saved item

You can retrieve the last saved item in the history log via:

```php
$item = $history->getLastItem('unique_key');
```

This is an array containing three keys: `'updated'`, `'content_hash'`, `'metadata'`. If there are no results, `null` is returned.

You can return a specific field by passing this as the third argument. For example, to return metadata:

```php
$metadata = $history->getLastItem('unique_key', 'metadata');
```

### Get all items

If you wish, you can return an array of all history logs for an item via:

```php
$items = $history->getAll('unique_key');
```

## Efficient data processing

By using the two above tests you can improve performance of data importing. However, given we store Data History in a cache you should not depend on these tests always working. If you have a data import process where data only changes once every six months then with a max history days of 30 days, the data import process will think data is new or changed at least every 30 days.

It is recommended your data import process is robust and works whether data is changed or not. The Data History system should be used improve performance - rather than be a substitute for knowing absolutely if data is new or changed. If your data import system has a critical dependency on knowing if data is changed or new, you will need to test this by looking up data in your data storage system \(e.g. database\).

In this instance you can make use of the same hash system to store content hash representations of data, which are more efficient to compare than full raw data.

### Content hasher

To create a content hash, simply pass raw data \(array or string\):

```php
use Strata\Data\Helper\ContentHasher;

$hash = ContentHasher::hash($data);
```

To check if data has changed, pass the last content hash \(string\) and the new raw data \(array or string\):

```php
$changed = ContentHasher::hasContentChanged($lastContentHash, $newRawData);
```

