# Bulk querying records from an API

A common use data to download data is to bulk download all records in paginated recordset.

Strategy

1. Get field from resultset, use this as a param in next query, until field is empty
2. Get resultset, pass page param, when no results end of resultset 
3. Get total results from meta data, query until reach end (concurrent)

```php
$api = new BulkStrategy();

bulk()->total('[result]['totalResults']')->setPage('page')

```


Default param 'page'