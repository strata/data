# Writing custom query classes

You can also write your own custom query classes to encapsulate common functionality for your API, making building queries easier.

Things you can set up in your own query classes:
* The query URI (REST)
* Default parameters
* Query name and defined variables (GraphQL)
* Default fields to return in query (useful for GraphQL)

### Create a custom query class 

Create your own class, extending one of the available query classes: 
* `Strata\Data\Query\Query`
* `Strata\Data\Query\GraphQLQuery`

You can set up the query in the constructor. This example sets the URI, root property path to return data from and where 
to read the total results data to help build pagination.

```php
use Strata\Data\Query\Query;

class MyQuery extends Query {

    public function __construct()
    {
        $this->setUri('news')
            ->setRootPropertyPath('[data]')
            ->setTotalResults('[meta][total_results]')
        ;
    }
    
}
```

Running queries on this query class now look like:

```php
// Add query
$query = new NewsQuery();
$query->addParam('page', $page);

// Run query and return a collection of results
$collection = $query->getCollection();
```

### Custom mapping

By default when each data item is returned it is mapped as-is, with all values returned from the source data.

You can change this functionality by overring the `getMapping()` method which returns a mapping strategy to use when 
mapping data. This can either be an `array` or an instance of `MappingStrategyInterface`.

Here's an example that maps some fields as they are (e.g. `title`, `introText`) but has custom callback methods used to 
alter how other fields are returned (e.g. `titleLink`, `children`). The class methods `transformTitleLink` and `transformChildLink` 
are omitted from this example but can contain any business logic you require to return the correct value.

```php
use Strata\Data\Mapper\MapArray;
use Strata\Data\Transform\Data\CallableData;

/**
 * Return mapping strategy to use to map a single item
 *
 * @return MappingStrategyInterface|array
 */
public function getMapping(): array
{
    return [
        '[title]'      => '[title]',
        '[titleLink]'  => new CallableData([$this, 'transformTitleLink'], '[isTitleLinkInternal]', '[titleInternalLink][0][uri]', '[titleExternalLink]'),
        '[introText]'  => '[introText]',
        '[children]'   => new MapArray('[children]', [
            '[title]' => '[title]',
            '[url]'   => new CallableData([$this, 'transformChildLink'], '[url]', '[internalLink][0][uri]'),
        ]),
    ];
}
```

This will transform complex source data such as:

```
[
    'title'
    'isTitleLinkInternal'
    'titleInternalLink' => [
        [
            'uri'
        ]
    ]
    'titleExternalLink'
    'introText'
    'children' => [ 
        'title'
        'internalLink' => [
            [
                'uri'
            ]
        ]
        'url'
    ]
]
```

Into the format:

```
[
    'title'
    'titleLink'
    'introText'
    'children' => [ 
        'title'
        'url
    ]
]
```

With any non-matching values returned as `null`.

You can find out more about [mapping](../changing-data/mapping.md), using [callables to map data](../changing-data/mapping.md#callabledata), 
and [mapping arrays](../changing-data/mapping.md#mapping-array-data).

### Creating custom retrieval methods

The standard retrieval methods to return data from a query are `get()` and `getCollection()`. You can return any data
from `get()`, whereas you must return a `Strata\Data\Collection` object from `getCollection()`.

You can setup custom transformers or mapper in either method to structure data before it's returned from the query object. 

See [changing data](../changing-data/README.md)