# Available transformers

## Transforming single values

Single value transformers are used to transform an individual value as it is accessed from data. This is useful to ensure data values are of an expected type.

If the value cannot be found or cannot be transformed, then null is returned.

### BooleanValue

Transform value to a boolean.

Usage:

```php
$transformer = new BooleanValue('[question]');
$value = $transformer->getValue($data);
```

By default, the following values are transformed to yes: 1, '1', 'true', 'yes', 'y'

And the following values are transformed to no: 0, '0', 'false', 'no', 'n'

Values are checked case-insensitively.

You can customise this by passing your own yes and no values to the constructor:

```php
$transformer = new BooleanValue('[question]', ['yes', 'true'], ['no', 'false']);
```

### DateTimeValue

Transform value to a DateTime object.

Usage:

```php
$transformer = new DateTimeValue('[date]');
$value = $transformer->getValue($data);
```

By default this transforms a datetime string using [supported date and time formats](https://www.php.net/datetime.formats).

You can pass a datetime format to transform the string from by passing the format to the constructor:

```php
$transformer = new DateTimeValue('[date]', 'YY-MM-DD');
```

### FloatValue

Transform value to a float.

Usage:

```php
$transformer = new FloatValue('[price]');
$value = $transformer->getValue($data);
```

### IntegerValue

Transform value to an integer.

Usage:

```php
$transformer = new IntegerValue('[id]');
$value = $transformer->getValue($data);
```

## Transforming all values

A value transformer loops through all data applying the transformation to each value.

### HtmlEntitiesDecode

Decode data that has been encoded with HTML entities.

### SetEmptyToNull

Normalize empty data by setting to null. Empty data is defined via the PHP [empty\(\) function](https://www.php.net/empty).

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
* `$mapping` - array of new value =&gt; old value/s 

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

Supports the `NotTransformedInterface` interface, `getNotTransformed()` returns an array of any old values that cannot be mapped to a new value.

```php
if ($transform->hasNotTransformed()) {
    $oldValues = $transform->getNotTransformed();
}
```

### RenameFields

Renames data field names from old to new field names. This is very similar to [mapping](mapping.md), however instead of copying data values to a new array or object, renaming fields simply leaves the original data array as is and renames any specific fields.

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

Supports the `NotTransformedInterface` interface, `getNotTransformed()` returns an array of any new field names that cannot be renamed because the old fieldnames cannot be found.

```php
if ($transform->hasNotTransformed()) {
    $oldValues = $transform->getNotTransformed();
}
```

