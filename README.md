# Data

The purpose of this package is to help read data from APIs and other external sources.

Features:

* Download data via HTTP APIs
* Throw exceptions on failed requests
* Supports REST and GraphQL
* Decode data from a variety of formats (e.g. JSON, Markdown)
* Automated debug information (logging and time profiling)
* Cache requests to increase performance
* Tools to help detect whether data has changed since last request 

Planned for the future:

* Filter data for security (e.g. strip tags)
* Validate data items to see whether they contain required data properties
* Efficient bulk API queries via concurrent requests
* Transform data (e.g. convert source category to match local category name) 
* Download data via local, FTP, S3 filesystem (via Flysystem)

## Documentation

See [docs](docs/README.md) or the docs site at: [https://docs.strata.dev/data/](https://docs.strata.dev/data/)

* [0.8 branch docs](https://docs.strata.dev/data/v/release%2F0.8.0/)

## Expected usage

```php
use Stratas\Data\Http\RestApi;

// Use concrete classes to access data
$api = new RestApi('example.com');

// Add functionality via events
$api->addSubscriber(new Logger('/path/to/log'));
$api->addSubscriber(new SymfonyProfiler(new Stopwatch()));

// Use concrete class methods to return response data, this is a GET request
$response = $api->get('uri', $parameters);

// E.g. for GraphQL this looks like:
$response = $api->query($query, $variables);

// Use Data manager to validate and transform data
$manager = new DataManager($api);

// Add validator
$rules = new ValidationRules([
    'data.entries' => 'array',
    'data.entries.title' => 'required',
];
$manager->addValidator($rules);

/** @var array $data */
$data = $api->decode($response);

if ($manager->validate($data)) {
    // do something
}

// Transform data
$manager->addTransformers([
    new StripTags(),
    new Trim(),
    new RenameFields([
        '[item_category]' => '[category]'
    ]),
    new MapValues('[category]', [
        'origin_name' => 'local_name'
    ]),
]);
$data = $manager->transform($data);

// Return an item
$manager->setupItem('ItemName', 'rootField');
$item = $manager->getItem('ItemName', $data);

// Return a collection
$manager->setupCollection('CollectionName', new CollectionStrategy()); // ???
$collection = $manager->getCollection('CollectionName', $data);

// Map to an object
$manager->addMapper('ClassName', [
    'data_field_1' => 'object_property_1',
    'data_field_2' => 'object_property_2'
]);
$object = $manager->mapToObject('ClassName');

// Returns an object of type ClassName
$newObject = new Mapper($response);
```


GraphQL

```php
$graphQl = new GraphQL($endpoint);

// Single record
$query = <<<'EOD'
query {
  entry(slug: "my-first-news-item") {
    postDate
    status
    title
  }
}
EOD;

try {
    $response = $graphQl->query($query);
    $data = $response->getContent()->data[];
     
    // @todo update to this:
    $item = $graphQl->query($query);
    $data = $item->getData();
    
} catch (\Strata\Data\Exception\NotFoundException) {
    // @todo No results found for query
    
} catch (\Strata\Data\Exception\FailedRequestException $e) {
    // HTTP request error
    $response = $response->getErrorMessage();
    $errorData = $response->getErrorData();
}

// News items
$query = <<<EOD
query {
  totalResults: entryCount(section: "news")
  entries(section: "news", limit: 2, offset: 0) {
    postDate
    title
  }
}
EOD;

try {
    $response = $graphQl->query($query);
    $items = $response->getList('entries', ['total' => 'totalResults']);
    
} catch (FailedRequestException $e) {
    $response = $response->getErrorMessage();
    $errorData = $response->getErrorData();
}


```

```php

// Return post ID 123
$data = new RestApi('https://domain.com/api/');

$item = $data->get('posts', 123)
             ->transform(Json);

// Return list of posts
$items = $data->list('posts', ['limit' => 5])
              ->transform(Json);

// Map results to an object
$mapper = new Mapper(MyObject::class);
$mapper->map('classProperty', 'apiProperty');

// Filter data before it is mapped to the object
$mapper->addFilter('classProperty', new Filter());

// or
$mapper->map('classProperty', 'apiProperty', new Filter());

// Get one post as an object
$data->setMapper('posts', $mapper);
$object = $data->getOne('posts', 123);

// Get a list of posts

// hi you: Do I need to make some form of query object here?

// Loop
while ($data->hasResults()) {
    $items = $data->list('posts');
    foreach ($items as $item) {
        $object = $mapper->map($item);
    }
}

// Get multiple, concurrent requests
$urls = ['url1','url2'];
$api->addRequests($urls);

/* addRequests does this internally:
foreach ($urls as $url) {
    $requests[] = $api->get($url);
}
*/

// Get data
foreach ($api->getRequests as $request) {
    $data = $request->getContent();
}

// Check if data is changed? (requires local cache of content hashes)
$data->isNew();
$data->isChanged();
$data->isDeleted();

// Support different types of APIs

// PaginationBuilder http://stateless.co/hal_specification.html
$api = new HalApi();

// JSON-LD https://json-ld.org/
$api = new JsonLdApi();

// Support custom APIs
$api = new WordPressApi();

// Markdown files
$data = new MarkdownData('path/to/folder');
$item = $data->getOne('./', 'filename.md');
foreach ($data->list() as $item)) {
  
} 

// CSV data
$data = new CsvData('path/to/file.csv');
$item = $data->getOne(1);
foreach ($data->list() as $item) {
  
}

```

Thanks to

https://developer.happyr.com/http-client-and-caching