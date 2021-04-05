## Transformers

Basic concepts of transformers are explained below. Transformers are often used with [mapping](mapping.md), instructions 
for independant usage appears below. Also see full details on all [available transformers](available-transformers.md).

Transformers come in three types: 

* [Single values](#transforming-single-values)
* [All values](#transforming-all-values)
* [Data](#transforming-data)

### Transforming single values

Single value transformers are used to transform an individual value as it is accessed from data (array or object).
This transformer can be used when setting up [mapping fields](mapping.md#transforming-individual-values-when-mapping). 

You need to pass the property path to the property you want to retrieve via a value transformer (e.g. `'[date]'`). 
See [accessing properties](accessing-properties.md) for details on how to define a property path.

For example, this returns a DateTime object from `$data['date']`:

```php
use Strata\Data\Transform\Value\DateTimeValue;

$data = [
    'date' => '2021-04-05T11:09:15+00:00'
];

$valueTransformer = new DateTimeValue('[date]');

if ($valueTransformer->isReadable($data)) {
    /** @var \DateTime $date */
    $date = $valueTransformer->getValue($data);
}
```

See available [single value transformers](available-transformers.md#transforming-single-values).

### Transforming all values

A value transformer loops through all data applying the transformation to each value.

For example this sets all empty values to null to help return predictable data:

```php
use Strata\Data\Transform\AllValues\SetEmptyToNull;

$transform = new SetEmptyToNull();
$data = $transform->transform($data);
```

See available [all value transformers](available-transformers.md#transforming-all-values).

### Transforming data

A data transformer acts on data as a whole.

For example this maps data values stored in `$data['item']['category']` to local values:

```php
use Strata\Data\Transform\Data\MapValues;

// New value => old values
$mapping = [
    'casual'    => ['T-Shirts', 'Jeans'],
    'outdoor'   => ['Scarfs', 'Coats'],
];

$transform = new MapValues('[item][category]', $mapping);
$data = $transform->transform($data);
```

See available [data transformers](available-transformers.md#transforming-data).

### What happens if data cannot be transformed?

Single value transformers have the `isReadable()` method to test whether that value can be accessed. If a single value 
transformer cannot be transformed it returns `null`.

If all values or data transformers cannot transform data they skip over it. These transformers contain a
`canTransform()` method which is used to check the data value is of the correct type to be transformed. This helps avoid
unwanted errors.

If a data transformers implements the `NotTransformedInterface` interface then you have access to two methods to help 
determine what data was not transformed. This can be useful, for example, if you map values and want to access any old 
values that did not map to new values.

```php
if ($transform->hasNotTransformed()) {
    $notTransformed = $transform->getNotTransformed();
}
```