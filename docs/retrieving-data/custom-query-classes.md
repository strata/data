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

### Creating custom retrieval methods

The standard retrieval methods to return data from a query are `get()` and `getCollection()`. You can return any data
from `get()`, whereas you must return a `Strata\Data\Collection` object from `getCollection()`.

You can setup custom transformers or mapper in either method to structure data before it's returned from the query object. 

See [changing data](../changing-data/README.md)