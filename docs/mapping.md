# Transforming and mapping data

You can transform and map data, which allows you to change data once it is loaded from an external source (or cache). 
Example use cases are:

* Prepare data values (e.g. strip tags, decode HTML entities)
* Rename data fields (e.g. from "person_name" to "name")
* Update data values to match your local values (e.g. map the category "T-Shirts" to "casual")
* Map a single item to an object
* Map a collection of items to a set of objects

## Transformers

Most of the time you'll want to transform data as it's mapped to an item. You can also use transformers to directly 
transform your loaded data.

Basic concepts of transformers are explained below. See [transformers](transformers.md) for full details on all 
available transformers.

Transformers come in two types: value transformers and data transformers.

### Value transformers

A value transformer loops through all data applying the transformation to each value. 

For example this sets all empty values to null to help return predictable data:

```php
use Strata\Data\Transform\Values\SetEmptyToNull;

$transform = new SetEmptyToNull();
$data = $transform->transform($data);
```

### Data transformers

A data transformer acts on data as a whole. 

For example this maps data values stored in `$data['item']['category']` to local values: 

```php
use Strata\Data\Transform\Data\MapValues;

$mapping = [
    'T-Shirts'  => 'casual',
    'Jeans'     => 'casual',
    'Scarfs'    => 'outdoor',
    'Coats'     => 'outdoor',
];

$transform = new MapValues('[item][category]', $mapping);
$data = $transform->transform($data);
```

### What happens if data cannot be transformed?

If a transformer cannot transform data it skips over it. All transformers contain a 
`canTransform()` method which is used to check the data value is of the correct type to be transformed. This helps avoid 
unwanted errors.

### Throwing exceptions on data transform errors - IS THIS REQUIRED?

You can optionally throw a `TransformException` on data transformation errors. This may be useful if you want to force data 
to be transformed (e.g. mapping values) and you want to know if this has failed (since your data may not process properly).

To enable exceptions pass:

```php
$transform->enableExceptions();
```

If you subsequently want to disable this pass `false` to the method:

```php
$transform->enableExceptions(false);
```

The [transformers](transformers.md) documentation includes details on which transformers raise exceptions when this 
option is enabled.

## Accessing properties

We use Symfony's [PropertyAccess](https://symfony.com/doc/current/components/property_access.html) component to help 
read and write data.

### Array properties
To access array properties use the index notation, specifying array keys within square brackets. 

Access `$data['name']`: 

```
[name]
```

Access `$data['people']['name']`:

```
[people][name]
```

Access `$data['people']['categories'][0]`:

```
[people][categories][0]
```

### Object properties

To access object properties use the dot notation, specifying object properties separated by a dot character.

Access `$data->name`:

```
name
```

Access `$data->people->name`:

```
people.name
```

Access `$data->people->categories[0]`:

```
people.name.categories[0]
```

## Mapping items

## An example

You can setup mapping by passing an array of new fields to old fields, using the property access syntax
described above. In the below example `region` is set to an array of two possible old field options, this allows 
you to specify multiple possible old values to map data from.

```php
$mapping = [
    '[name]'    => '[person_name]',
    '[age]'     => '[person_age]',
    '[region]'  => [
        '[person_region]', '[person_town]'
    ]
];
$mapper = new MapItem('person', $mapping);
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

### Mapping to an object

You can map data to an object, by passing the object name as the second argument to the `map()` method.

```php
$person = $mapper->map($data, 'App\Person');
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
you can set any number of transformers which apply to mapped data on your new array or object. If you want to transform 
data before it is mapped, you need to use the transformer on the data object directly.

`MappingStrategy` takes two arguments: the first is the array of property paths to map data to, the second is an array of 
transformers you wish to use (transformers must implement `TransformInterface`). 

```php
$strategy = new MappingStrategy($mapping, [
    new SetEmptyToNull(),
    new MapValues('[region]', ['Norwich' => 'East of England']),
]);
$mapper = new MapItem('person', $strategy);
```

By using the above mapper means that:

```php
$person = $mapper->map($data, 'App\Person');
```

Returns:

```php
// returns: East of England
echo $person->region;
```

### Mapping from a different root property

If your item data cannot be found in the root of the data array then you can specify the root property 
path to use. The following examples sets the root to `$data['item']`.

```php
$mapper->setRootPropertyPath('[item]');
```

## Mapping collections

TODO
