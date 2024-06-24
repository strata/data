# Helpers

A number of helper classes exist in Strata Data.

## ContentHasher

### is

The static method `is()` tests whether a value is one of the passed types. 
Valid types are: array, callable, bool, float, int, string, iterable, object, or classname. You can check for any number 
of types.

```
// Check $value is a string or an array
if (UnionTypes::is($value, 'string', 'array')) {
    // do something
}
```

