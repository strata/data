# Available transformers

## Transforming single values

Single value transformers are used to transform an individual value as it is accessed from data (array or object).

TODO

## Transforming all values

A value transformer loops through all data applying the transformation to each value.

### HtmlEntitiesDecode

Decode data that has been encoded with HTML entities.

### SetEmptyToNull

Normalize empty data by setting to null. Empty data is defined via the PHP [empty() function](https://www.php.net/empty).

### StripTags

Strip HTML tags.

### Trim

Trims whitespace from start and end of values.

## Transforming data

A data transformer acts on data as a whole.

### MapValues

Maps values for one specific data field from old to new values. For example, updating category values to match local values. 

The `MapValues` class takes two arguments: 
* `$propertyPath` - [property path](mapping.md#accessing-properties) to root item to map values for 
* `$mapping` - array of new value => old value/s 

Usage:

```php
use Strata\Data\Transform\Data\MapValues;

// New value => old values
$mapping = [
    'casual'    => ['T-Shirts', 'Jeans'],
    'outdoor'   => ['Scarfs', 'Coats'],
];

$transform = new MapValues('[item][category]', $mapping);

$data = [
    'item' => [
        'category' => 'T-Shirts'
    ]
];
$data = $transform->transform($data);

// Returns: casual
echo $data = ['item']['category'];
```

Supports the `NotTransformedInterface` interface, `getNotTransformed()` returns an array of any old values that cannot 
be mapped to a new value.

```php
if ($transform->hasNotTransformed()) {
    $oldValues = $transform->getNotTransformed();
}
```

### RenameFields

Renames data field names from old to new field names. This is very similar to [mapping](mapping.md), however instead of 
copying data values to a new array or object, renaming fields simply leaves the original data array as is and renames 
any specific fields.

The `RenameFields` class takes one argument:
* `$propertyPaths` - array of [property paths](mapping.md#accessing-properties) new field names to old field names

Usage:

```php
use Strata\Data\Transform\Data\RenameFields;

// New value => old value
$mapping = [
    '[name]'    => '[full_name]',
];

$transform = new MapValues('[item]', $mapping);

$data = [
    'full_name' => 'Simon Jones',
];
$data = $transform->transform($data);

// Returns an array with the key 'full_name' renamed to 'name'
```

Supports the `NotTransformedInterface` interface, `getNotTransformed()` returns an array of any new field names that 
cannot be renamed because the old fieldnames cannot be found.

```php
if ($transform->hasNotTransformed()) {
    $oldValues = $transform->getNotTransformed();
}
```
