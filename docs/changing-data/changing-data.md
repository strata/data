# Transforming and mapping data

You can change data once it is loaded from an external source, via transformers and mappers.

[Transformers](transformers.md) are used to change data values, for example converting a datetime string to a DateTime object.

[Mappers](mapping.md) are used to map data to a new data structure so it is more useful for processing, for example converting an array of raw data to a collection of objects.

You can use mappers and transformers together, it's usually more useful to transform data as it's being mapped to a more useful data structure.

Example use cases are:

* Prepare data values \(e.g. strip tags, decode HTML entities\)
* Rename data fields \(e.g. from "person\_name" to "name"\)
* Update data values to match your local values \(e.g. map the category "T-Shirts" to "casual"\)
* Map a single item to an object \(and optionally type set item fields, e.g. to an DateTime object\)
* Map a collection of items to a set of objects

We use Symfony's PropertyAccess component to help read and write data. See [how to write property paths](property-paths.md) for more details.

