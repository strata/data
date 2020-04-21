# Data

Read and write data from APIs in a standardised format. 

Features:

* Get data from API endpoint
* Map incoming data to an object
* Filter incoming data (to transform data before it is saved to object)
* Pagination 
* Work out whether content is new, changed or deleted
* Keep a history of API requests & status codes to help debugging & timing

See [Documentation](docs/README.md)

## Usage

```php
// Get one post as an array
$data = new RestApi('https://domain.com/api/');
$item = $data->getOne('posts', 123);

$data->from('posts')->getOne(123);
$data->from('posts')->getList();


$item = $data->getOne(123);

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

// HAL http://stateless.co/hal_specification.html
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
