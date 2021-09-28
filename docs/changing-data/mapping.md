# Mapping data

Mappers are used to map data to a new data structure so it is more useful for processing, for example converting an array of raw data to a collection of objects.

## An example

You can set up mapping by passing an array of new fields to old fields, using the property access syntax described above. In the below example `region` is set to an array of two possible old field options, this allows you to specify multiple possible old values to map data from.

Please note the new location and source location are written as [property paths](property-paths.md).

```php
use Strata\Data\Mapper\MapItem;

// Map new location from source location
$mapping = [
    '[name]'    => '[person_name]',
    '[age]'     => '[person_age]',
    '[region]'  => [
        '[person_region]', '[person_town]'
    ]
];
$mapper = new MapItem($mapping);
```

You can then map incoming data to the new structure. In the below example, we've set a local data array to make this example concise. In reality, you'd be retrieving data from an external data provider.

```php
$data = [
    'person_name' => 'Fred Bloggs',
    'person_town' => 'Norwich'
];
$item = $mapper->map($data);
```

This returns:

```text
$item = [
    'name'      => 'Fred Bloggs',
    'age'       => null,
    'region'    => 'Norwich'
];
```

As you can see any fields not found in the source data are set to null and the region is correctly mapped from the `person_town` source field.

### Specifying multiple source fields

Some APIs use multiple source field names for the same data, you can pass multiple source property paths in an array and the mapper will take the first match it finds.

```php
$data = [
    'title'     => ['title', 'post_title'],
    'urlSlug'   => '[slug]',
];
$item = $mapper->map($data);
```

### Transforming individual values when mapping

You can transform data values when mapping by using a single value transformer. Single value transformers take the property path as the first argument. Some transformers accept further arguments to customise the transformer.

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

* [Boolean](available-transformers.md#booleanvalue)
* [DateTime](available-transformers.md#datetimevalue)
* [Float](available-transformers.md#floatvalue)
* [Integer](available-transformers.md#integervalue)

### Using a callback to map data

You can also write PHP code to help map more complex data from source to your destination by using callables.

#### CallableValue

To transform one value using a callback use `CallableValue`, this takes two arguments: the property path of the source data and the [callable](https://www.php.net/language.types.callable) to run to return the transformed value.

When called via the mapper the callable is passed the following arguments:

* `$value` Source value \(read from the source property path\)

For example, to use the built-in `strtoupper()` function:

```php
$mapping = [
    '[title]'  => '[title]',
    '[name]'   => new CallableValue('[first_name]', 'strtoupper'),
;
$mapper = new MapItem($mapping);

$data = [
    'first_name' => 'fred'
];
$item = $mapper->map($data);

// Returns: Fred
echo $item['name'];
```

#### CallableData

For more complex operations, you can use `CallableData` which allows you to define custom mapping rules in code via a 
callable function or class method.

When instantiating the class it has one required method: the [callable](https://www.php.net/language.types.callable) to 
run to return transformed data.

By default, the entire data object is passed to the callable. 

The callable function must return the data to write to the destination property path in your destination item 
(array or object). If the return data cannot be calculated, then return `null`.

For example, this mapping strategy uses a callback for the `$item['name']` field:

```php
// return content to map to source item
$callableFunction = function(array $data) {
    return ucfirst($data['first_name']) . ' ' . ucfirst($data['last_name']);
};

$mapping = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData($callableFunction),
];
$mapper = new MapItem($mapping);

$data = [
    'first_name' => 'fred',
    'last_name'  => 'jones',
];
$item = $mapper->map($data);

// Returns: Fred Jones
echo $item['name'];
```

Optionally, you can specify a list of property paths to filter the data passed to the callable. In this instance, only named properties are passed to the 
callable. If a data property cannot be found in the source data, a `null` value is passed instead.

This can help make your callable function a little clearer. Using the same example as above:

```php
// return content to map to source item
$callableFunction = function($firstName, $lastName) {
    if (empty($firstName) && empty($lastName)) {
        return null;
    }
    return ucfirst($firstName) . ' ' . ucfirst($lastName);
};

$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData($callableFunction, '[first_name]', '[last_name]'),
];
$item = $mapper->map($data);
```

It is recommended to use classes for callables, e.g.

```php
$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData([$object, 'methodName'])
];
```

Or for static methods:

```php
$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData(['MyNamespace\ClassName', 'methodName'])
];
```

### Using the property accessor in your own classes

If you have a class and you want to use the [property accessor](https://symfony.com/doc/current/components/property_access.html) simply use the `PropertyAccessorTrait` trait and you'll have access to methods such as `getPropertyAccessor()`. Additionally, if your class implements `PropertyAccessorInterface` then an instance of the property accessor will be automatically passed to your class from the mapper.

```text
use Strata\Data\Transform\PropertyAccessorInterface;
use Strata\Data\Transform\PropertyAccessorTrait;

class MyClass implements PropertyAccessorInterface {
    use PropertyAccessorTrait;
}
```

### Mapping from a different root property

If your item data cannot be found in the root of the data array then you can specify the root property path to use as the second argument to the `map()` method. The following examples sets the root to `$data['item']`:

```php
$item = $mapper->map($data, '[item]');
```

### Mapping array data

If your source data contains an array which you want to map, you can do this via the `MapArray` class. This allows you 
to define child mapping for an array of data. 

The `MapArray` class takes two arguments:
* The property path to the data array you want to map (this must be an array, otherwise it is ignored)
* The mapping strategy to use to map (e.g. an array)

For example: 

```php
// Match url from source data: child_link or uri
$childrenMapping = [
    '[title]' => '[child_title]',
    '[url]'   => ['[child_link]','[uri]'],
];
$mapping = [
    '[name]'     => '[person_name]',
    '[children]' => new MapArray('[children]', $childrenMapping)
];
$mapper = new MapItem($mapping);

$data = [
    'person_name' => 'Fred Bloggs',
    'links'    => [
        ['title' => 'Test 1', 'link' => 'https://example/1'],
        ['title' => 'Test 2', 'uri' => '/my-link'],
        ['title' => 'Test 3', 'link' => 'https://example/3'],
    ]
];

$item = $mapper->map($data);

// $item['links'] now contains an array with each item containing ['title', 'url']
```

The `MapArray` object has the same mapping rules as for `MapItem`. It basically runs `MapItem::map` to map the child array
to your destination data.

### Mapping to an object

You can map data to an object, by calling the `toObject()` method and passing the class name. You also need to update the mapping to set data to object properties \(using the dot notation instead of index notation\):

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

The neat thing about Symfony's PropertyAccess component is it can use a variety of ways to populate an object. The following is supported:

* Public properties
* Setters \(e.g. `setName()`\)
* Magic `__set()` method

### Adding transformers

Once data is mapped to the new object or array, you can apply transformers to change the data.

When you create the mapper, you need to pass an instance of `MappingStrategy` class as the second argument. Via this class you can set any number of transformers which apply to mapped data on your new array or object. Please note if you want to transform data before it is mapped, you need to use the transformer on the data object directly.

`MappingStrategy` takes two arguments: the first is the array of property paths to map data to, the second is an array of transformers you wish to use \(transformers must implement `TransformInterface`\).

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
map all data fields that are available in the source data, with a few exceptions that you specify. 

Wildcard mapping works by automatically mapping all fields, but you specify fields to ignore and you can set up 
specific mapping for fields you want to change.  

Create a new wildcard mapping strategy via:

```php
$wildcard = new WildcardMappingStrategy();
```

Add fields to be ignored (not mapped) via  `addIgnore($fieldName)` passing the field name:

```php
$wildcard->addIgnore('Field_to_ignore');
```

Please note there is no need to use property paths here, just field names for the array key. You can pass either a string 
field name or an array of stri# Mapping data

Mappers are used to map data to a new data structure so it is more useful for processing, for example converting an array of raw data to a collection of objects.

## An example

You can set up mapping by passing an array of new fields to old fields, using the property access syntax described above. In the below example `region` is set to an array of two possible old field options, this allows you to specify multiple possible old values to map data from.

Please note the new location and source location are written as [property paths](property-paths.md).

```php
use Strata\Data\Mapper\MapItem;

// Map new location from source location
$mapping = [
    '[name]'    => '[person_name]',
    '[age]'     => '[person_age]',
    '[region]'  => [
        '[person_region]', '[person_town]'
    ]
];
$mapper = new MapItem($mapping);
```

You can then map incoming data to the new structure. In the below example, we've set a local data array to make this example concise. In reality, you'd be retrieving data from an external data provider.

```php
$data = [
    'person_name' => 'Fred Bloggs',
    'person_town' => 'Norwich'
];
$item = $mapper->map($data);
```

This returns:

```text
$item = [
    'name'      => 'Fred Bloggs',
    'age'       => null,
    'region'    => 'Norwich'
];
```

As you can see any fields not found in the source data are set to null and the region is correctly mapped from the `person_town` source field.

### Specifying multiple source fields

Some APIs use multiple source field names for the same data, you can pass multiple source property paths in an array and the mapper will take the first match it finds.

```php
$data = [
    'title'     => ['title', 'post_title'],
    'urlSlug'   => '[slug]',
];
$item = $mapper->map($data);
```

### Transforming individual values when mapping

You can transform data values when mapping by using a single value transformer. Single value transformers take the property path as the first argument. Some transformers accept further arguments to customise the transformer.

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

* [Boolean](available-transformers.md#booleanvalue)
* [DateTime](available-transformers.md#datetimevalue)
* [Float](available-transformers.md#floatvalue)
* [Integer](available-transformers.md#integervalue)

### Using a callback to map data

You can also write PHP code to help map more complex data from source to your destination by using callables.

#### CallableValue

To transform one value using a callback use `CallableValue`, this takes two arguments: the property path of the source data and the [callable](https://www.php.net/language.types.callable) to run to return the transformed value.

When called via the mapper the callable is passed the following arguments:

* `$value` Source value \(read from the source property path\)

For example, to use the built-in `strtoupper()` function:

```php
$mapping = [
    '[title]'  => '[title]',
    '[name]'   => new CallableValue('[first_name]', 'strtoupper'),
;
$mapper = new MapItem($mapping);

$data = [
    'first_name' => 'fred'
];
$item = $mapper->map($data);

// Returns: Fred
echo $item['name'];
```

#### CallableData

For more complex operations, you can use `CallableData` which allows you to define custom mapping rules in code via a
callable function or class method.

When instantiating the class it has one required method: the [callable](https://www.php.net/language.types.callable) to
run to return transformed data.

By default, the entire data object is passed to the callable.

The callable function must return the data to write to the destination property path in your destination item
(array or object). If the return data cannot be calculated, then return `null`.

For example, this mapping strategy uses a callback for the `$item['name']` field:

```php
// return content to map to source item
$callableFunction = function(array $data) {
    return ucfirst($data['first_name']) . ' ' . ucfirst($data['last_name']);
};

$mapping = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData($callableFunction),
];
$mapper = new MapItem($mapping);

$data = [
    'first_name' => 'fred',
    'last_name'  => 'jones',
];
$item = $mapper->map($data);

// Returns: Fred Jones
echo $item['name'];
```

Optionally, you can specify a list of property paths to filter the data passed to the callable. In this instance, only named properties are passed to the
callable. If a data property cannot be found in the source data, a `null` value is passed instead.

This can help make your callable function a little clearer. Using the same example as above:

```php
// return content to map to source item
$callableFunction = function($firstName, $lastName) {
    if (empty($firstName) && empty($lastName)) {
        return null;
    }
    return ucfirst($firstName) . ' ' . ucfirst($lastName);
};

$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData($callableFunction, '[first_name]', '[last_name]'),
];
$item = $mapper->map($data);
```

It is recommended to use classes for callables, e.g.

```php
$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData([$object, 'methodName'])
];
```

Or for static methods:

```php
$data = [
    '[title]'  => '[title]',
    '[name]'   => new CallableData(['MyNamespace\ClassName', 'methodName'])
];
```

### Using the property accessor in your own classes

If you have a class and you want to use the [property accessor](https://symfony.com/doc/current/components/property_access.html) simply use the `PropertyAccessorTrait` trait and you'll have access to methods such as `getPropertyAccessor()`. Additionally, if your class implements `PropertyAccessorInterface` then an instance of the property accessor will be automatically passed to your class from the mapper.

```text
use Strata\Data\Transform\PropertyAccessorInterface;
use Strata\Data\Transform\PropertyAccessorTrait;

class MyClass implements PropertyAccessorInterface {
    use PropertyAccessorTrait;
}
```

### Mapping from a different root property

If your item data cannot be found in the root of the data array then you can specify the root property path to use as the second argument to the `map()` method. The following examples sets the root to `$data['item']`:

```php
$item = $mapper->map($data, '[item]');
```

### Mapping array data

If your source data contains an array which you want to map, you can do this via the `MapArray` class. This allows you
to define child mapping for an array of data.

The `MapArray` class takes two arguments:
* The property path to the data array you want to map (this must be an array, otherwise it is ignored)
* The mapping strategy to use to map (e.g. an array)

For example:

```php
// Match url from source data: child_link or uri
$childrenMapping = [
    '[title]' => '[child_title]',
    '[url]'   => ['[child_link]','[uri]'],
];
$mapping = [
    '[name]'     => '[person_name]',
    '[children]' => new MapArray('[children]', $childrenMapping)
];
$mapper = new MapItem($mapping);

$data = [
    'person_name' => 'Fred Bloggs',
    'links'    => [
        ['title' => 'Test 1', 'link' => 'https://example/1'],
        ['title' => 'Test 2', 'uri' => '/my-link'],
        ['title' => 'Test 3', 'link' => 'https://example/3'],
    ]
];

$item = $mapper->map($data);

// $item['links'] now contains an array with each item containing ['title', 'url']
```

The `MapArray` object has the same mapping rules as for `MapItem`. It basically runs `MapItem::map` to map the child array
to your destination data.

### Mapping to an object

You can map data to an object, by calling the `toObject()` method and passing the class name. You also need to update the mapping to set data to object properties \(using the dot notation instead of index notation\):

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

The neat thing about Symfony's PropertyAccess component is it can use a variety of ways to populate an object. The following is supported:

* Public properties
* Setters \(e.g. `setName()`\)
* Magic `__set()` method

### Adding transformers

Once data is mapped to the new object or array, you can apply transformers to change the data.

When you create the mapper, you need to pass an instance of `MappingStrategy` class as the second argument. Via this class you can set any number of transformers which apply to mapped data on your new array or object. Please note if you want to transform data before it is mapped, you need to use the transformer on the data object directly.

`MappingStrategy` takes two arguments: the first is the array of property paths to map data to, the second is an array of transformers you wish to use \(transformers must implement `TransformInterface`\).

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
map all data fields that are available in the source data, with a few exceptions that you specify.

Wildcard mapping works by automatically mapping all fields, but you specify fields to ignore and you can set up
specific mapping for fields you want to change.

Create a new wildcard mapping strategy via:

```php
$wildcard = new WildcardMappingStrategy();
```

Add fields to be ignored (not mapped) via  `addIgnore($fieldName)` passing the field name:

```php
$wildcard->addIgnore('Field_to_ignore');
```

The field name should be a root field, this doesn't work for child fields.

Please note there is no need to use property paths here, just field names for the array key. You can pass either a single 
field name or an array of field names to this method.

You can add explicit mapping for a field via `addMapping($fieldName, $mapping)` passing the field name and the normal
mapping array:

```php
$wildcard->addMapping('name', [
    '[full_name]' => '[name]'
]);
```

You can add multiple mapping, this is useful if the root field has children - in this instance you need to set up 
explicit mapping to retrieve child data.

This mapping strategy works in the following way:
* Any fields noted in the ignore list are not mapped
* Any fields noted in the mapping list are mapped according to the rules you specify
* Any remaining fields are mapped as-is
* Transformers are applied to mapped data at the end

For example:

```php
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\WildcardMappingStrategy;

$wildcard = new WildcardMappingStrategy();
$wildcard->addIgnore('Field_to_ignore');
$wildcard->addMapping('full_name', [
    '[name]' => '[full_name]'
]);
$mapper = new MapItem($wildcard);

$data = [
    'full_name' => 'Joe Bloggs',
    'Field_to_ignore' => '123',
    'category' => 'fishing' 
];
$item = $mapper->map($data);
```

The above returns an array with two values:

```text
$item = [
    'name' => 'Joe Bloggs',
    'category' => 'fishing' 
];
```

Any transformers are applied as detailed above. Wildcard mappers can map data to an array or object.

## Mapping collections

You can also map data to a collection of array items or objects. This automatically sets pagination to make it easier to output pagination information or run subsequent requests.

### Setting pagination

To automatically generate pagination we need to pass data about the total results, results per page and current page. You can call the following methods to set the property path to the appropriate data field, or pass the actual value as an integer.

Pagination property paths are relative to the original data root, this is not affected by passing a `$rootProperty` argument to the `map()` method.

* `setTotalResults()`
* `setResultsPerPage()`
* `setCurrentPage()`

Values for all three fields must be set in order to create a valid pagination object, with the exception of `currentPage()` which defaults to `1` if not set.

These methods return a fluent interface so you can chain these methods together for convenience:

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->setCurrentPage(1);
```

### Setting pagination data from another data source

Some data providers set pagination information in a secondary location, for example response headers. To use this method, simply pass the secondary array along with property paths to point to the required pagination fields.

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[X-WP-Total]')
       ->setResultsPerPage(20)
       ->setCurrentPage(1)
       ->fromPaginationData($headers);
```

### Returning a collection of arrays

When you run the `map()` method a `Collection` object is returned that you can iterate through. Within the collection, by default each item is an array. You can access pagination via `$collection->getPagination()`.

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->currentPage(1);

$collection = $mapper->map($data);
```

The collection object that is returned can be iterated over and accessed like a normal array. It implements `SeekableIterator`, `Countable`, and `ArrayAccess`.

### Returning a collection of objects

You can map results in a collection to an object via the `toObject()` method:

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->setCurrentPage(1)
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
    'id'     => new IntegerValue('[id]'),
];
$mapper = new MapCollection($mapping)
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage('[meta_data][per_page]')
       ->toObject('App\Item');

$data = [
    'items' => [
        0 => [
            'item_name' => 'Apple',
            'id' => '1'
        ],
        1 => [
            'item_name' => 'Banana',
            'id' => '2'
        ],
        2 => [
            'item_name' => 'Orange',
            'id' => '3'
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

ngs to this method.

You can add explicit mapping for a field via `addMapping($fieldName, $mapping)` passing the field name and the normal 
mapping array:

```php
$wildcard->addMapping('name', [
    '[full_name]' => '[name]'
]);
```

Please note when using `addIgnore()` or `addMapping()` you need to pass the root array property as the field name. 

If the field noted in `addMapping()` has any child values, you need to add explicit mapping for all fields you want mapped
otherwise they will be skipped.

This mapping strategy works in the following way:
* Any array fields noted in the ignore list are not mapped
* Any array fields noted in the mapping list are mapped according to the rules you specify
* Any remaining array fields are mapped as-is
* Transformers are applied to mapped data at the end

For example:

```php
use Strata\Data\Mapper\MapItem;
use Strata\Data\Mapper\WildcardMappingStrategy;

$mapping = [
    '[full_name]' => '[name]'
];
$ignore = ['Field_to_ignore'];

$wildcard = new WildcardMappingStrategy();
$wildcard->addIgnore('Field_to_ignore');
$wildcard->addMapping('name', [
    '[full_name]' => '[name]'
]);
$mapper = new MapItem($wildcard);

$data = [
    'name' => 'Joe Bloggs',
    'Field_to_ignore' => '123',
    'category' => 'fishing' 
];
$item = $mapper->map($data);
```

The above returns an array with two values:

```text
$item = [
    'full_name' => 'Joe Bloggs',
    'category' => 'fishing' 
];
```

Any transformers are applied as detailed above. Wildcard mappers can map data to an array or object.

## Mapping collections

You can also map data to a collection of array items or objects. This automatically sets pagination to make it easier to output pagination information or run subsequent requests.

### Setting pagination

To automatically generate pagination we need to pass data about the total results, results per page and current page. You can call the following methods to set the property path to the appropriate data field, or pass the actual value as an integer.

Pagination property paths are relative to the original data root, this is not affected by passing a `$rootProperty` argument to the `map()` method.

* `setTotalResults()`
* `setResultsPerPage()`
* `setCurrentPage()` 

Values for all three fields must be set in order to create a valid pagination object, with the exception of `currentPage()` which defaults to `1` if not set.

These methods return a fluent interface so you can chain these methods together for convenience:

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->setCurrentPage(1);
```

### Setting pagination data from another data source

Some data providers set pagination information in a secondary location, for example response headers. To use this method, simply pass the secondary array along with property paths to point to the required pagination fields.

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[X-WP-Total]')
       ->setResultsPerPage(20)
       ->setCurrentPage(1)
       ->fromPaginationData($headers);
```

### Returning a collection of arrays

When you run the `map()` method a `Collection` object is returned that you can iterate through. Within the collection, by default each item is an array. You can access pagination via `$collection->getPagination()`.

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->currentPage(1);

$collection = $mapper->map($data);
```

The collection object that is returned can be iterated over and accessed like a normal array. It implements `SeekableIterator`, `Countable`, and `ArrayAccess`.

### Returning a collection of objects

You can map results in a collection to an object via the `toObject()` method:

```php
$mapper = new MapCollection($mapping);
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage(3)
       ->setCurrentPage(1)
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
    'id'     => new IntegerValue('[id]'),
];
$mapper = new MapCollection($mapping)
$mapper->setTotalResults('[meta_data][total]')
       ->setResultsPerPage('[meta_data][per_page]')
       ->toObject('App\Item');

$data = [
    'items' => [
        0 => [
            'item_name' => 'Apple',
            'id' => '1'
        ],
        1 => [
            'item_name' => 'Banana',
            'id' => '2'
        ],
        2 => [
            'item_name' => 'Orange',
            'id' => '3'
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

