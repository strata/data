# Mapping items

Mappers are used to map data to a new data structure so it is more useful for processing, for example
converting an array of raw data to a collection of objects.

## An example

You can setup mapping by passing an array of new fields to old fields, using the property access syntax
described above. In the below example `region` is set to an array of two possible old field options, this allows 
you to specify multiple possible old values to map data from.

```php
use Strata\Data\Mapper\MapItem;

$mapping = [
    '[name]'    => '[person_name]',
    '[age]'     => '[person_age]',
    '[region]'  => [
        '[person_region]', '[person_town]'
    ]
];
$mapper = new MapItem($mapping);
```

You can then map incoming data to the new structure. In the below example, we've set a local data array to make this
example concise. In reality, you'd be retrieving data from an external data provider.

```php
$data = [
    'person_name' => 'Fred Bloggs',
    'person_town' => 'Norwich'
];
$item = $mapper->map($data);
```

This returns:

```
$item = [
    'name'      => 'Fred Bloggs',
    'age'       => null,
    'region'    => 'Norwich'
];
```

As you can see any fields not found in the source data are set to null and the region is correctly mapped from the 
`person_town` source field.

### Transforming individual values when mapping

You can transform data values when mapping by using a single value transformer. Single value transformers take the 
property path as the first argument. Some transformers accept further arguments to customise the transformer. 

For example, to cast a date of birth to a `\DateTime` object, use:

```php
use Strata\Data\Mapper\MapItem;
use Strata\Data\Transform\Value\DateTimeValue;

$mapping = [
    '[name]'          => '[person_name]',
    '[date_of_birth]' => new DateTimeValue('[dob]'),
];
$mapper = new MapItem($mapping);
```

The following data value transformers are available:

* DateTime

### Mapping from a different root property

If your item data cannot be found in the root of the data array then you can specify the root property
path to use as the second argument to the `map()` method. The following examples sets the root to `$data['item']`:

```php
$item = $mapper->map($data, '[item]');
```

### Mapping to an object

You can map data to an object, by calling the `toObject()` method and passing the class name. You also need to 
update the mapping to set data to object properties (using the dot notation instead of index notation):

```php
$mapping = [
    'name'    => '[person_name]',
    'age'     => '[person_age]',
    'region'  => [
        '[person_region]', '[person_town]'
    ]
];
$mapper = new MapItem($mapping);
$mapper->toObject('App\Person');

$person = $mapper->map($data);
```

Given the example class:

```php
namespace App;

class Person {
    public string $name;
    public int $age;
    public string $region;
}
```

This returns:

```php
// returns: Fred Bloggs
echo $person->name;

// returns: Norwich
echo $person->region;
```

The neat thing about Symfony's PropertyAccess component is it can use a variety of ways to populate an object. The 
following is supported:

* Public properties
* Setters (e.g. `setName()`)
* Magic `__set()` method

### Adding transformers

Once data is mapped to the new object or array, you can apply transformers to change the data.

When you create the mapper, you need to pass an instance of `MappingStrategy` class as the second argument. Via this class
you can set any number of transformers which apply to mapped data on your new array or object. Please note if you want to transform 
data before it is mapped, you need to use the transformer on the data object directly.

`MappingStrategy` takes two arguments: the first is the array of property paths to map data to, the second is an array of 
transformers you wish to use (transformers must implement `TransformInterface`). 

```php
$strategy = new MappingStrategy($mapping, [
    new SetEmptyToNull(),
    new MapValues('[region]', ['Norwich' => 'East of England']),
]);
$mapper = new MapItemToObject($strategy);
```

By using the above mapper means that:

```php
$person = $mapper->map($data);
```

Returns:

```php
// returns: East of England
echo $person->region;
```

### Wildcard mappers

The above examples are useful when you know the data fields you want to map to a new item. An alternative strategy is to
map all data fields, except any you wish to ignore. You can do this with the `WildcardMappingStrategy`. This class takes
two arguments: an array of fields to ignore (not map), and an array of transformers to apply to the data.

E.g.

```php
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\WildcardMappingStrategy;
use Strata\Data\Transform\Value\DateTimeValue;

$ignore = ['field_to_ignore'];
$mapper = new MapItem(new WildcardMappingStrategy($ignore));

$data = [
    'name' => 'Joe Bloggs',
    'Field_to_ignore' => '123',
    'category' => 'fishing' 
];
$item = $mapper->map($data);
```

Ignored fields are case-insensitive. The above returns an array with two values:

```
$item = [
    'name' => 'Joe Bloggs',
    'category' => 'fishing' 
];
```

Any transformers are applied as detailed above. Wildcard mappers can map data to an array or object.

## Mapping collections

You can also map data to a collection of array items or objects. This automatically sets pagination to make it easier to 
output pagination information or run subsequent requests.

### Setting pagination 
To automatically generate pagination we need to pass data about the total results, results per page and current page. 
You can call the following methods to set the property path to the appropriate data field, or pass the actual value as an integer.

Pagination property paths are relative to the original data root, this is not affected by passing a `$rootProperty` 
argument to the `map()` method.

* `totalResults()`
* `resultsPerPage()`
* `currentPage()` 

Values for all three fields must be set in order to create a valid pagination object, with the exception of `currentPage()`
which defaults to `1` if not set.

These methods return a fluent interface so you can chain these methods together for convenience: 

```php
$mapper = new MapCollection($mapping);
$mapper->totalResults('[meta_data][total]')
       ->resultsPerPage(3)
       ->currentPage(1);
```

### Setting pagination data from another data source

Some data providers set pagination information in a secondary location, for example response headers. To use this method,
simply pass the secondary array along with property paths to point to the required pagination fields.


```php
$mapper = new MapCollection($mapping);
$mapper->totalResults('[X-WP-Total]')
       ->resultsPerPage(20)
       ->currentPage(1)
       ->fromPaginationData($headers);
```

### Returning a collection of arrays

When you run the `map()` method a `Collection` object is returned that you can iterate through. Within the collection, 
by default each item is an array. You can access pagination via `$collection->getPagination()`.

```php
$mapper = new MapCollection($mapping);
$mapper->totalResults('[meta_data][total]')
       ->resultsPerPage(3)
       ->currentPage(1);

$collection = $mapper->map($data);
```

The collection object that is returned can be iterated over and accessed like a normal array. It implements 
`SeekableIterator`, `Countable`, and `ArrayAccess`. 

### Returning a collection of objects

You can map results in a collection to an object via the `toObject()` method:

```php
$mapper = new MapCollection($mapping);
$mapper->totalResults('[meta_data][total]')
       ->resultsPerPage(3)
       ->currentPage(1)
       ->toObject('App\MyObject');

$collection = $mapper->map($data);
```

### A complete example

```php
namespace App;

class Item {
    public string $name;
    public int $id;
}

$mapping = [
    'name'   => '[item_name]',
    'id'     => '[id]',
];
$mapper = new MapCollection($mapping)
$mapper->totalResults('[meta_data][total]')
       ->resultsPerPage('[meta_data][per_page]')
       ->toObject('App\Item');

$data = [
    'items' => [
        0 => [
            'item_name' => 'Apple',
            'id' => 1
        ],
        1 => [
            'item_name' => 'Banana',
            'id' => 2
        ],
        2 => [
            'item_name' => 'Orange',
            'id' => 3
        ]
    ]
    'meta_data' => [
        'total' => 10,
        'page' => 1,
        'per_page' => 3
    ]
];

$collection = $mapper->map($data, '[items]');
```
