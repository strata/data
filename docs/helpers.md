# Helpers

A number of helper classes exist in Strata Data.

## ContentHasher



## UnionTypes

Allow type checking of union types for function arguments for PHP 7.4.  

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

### assert

The static method `assert()` tests whether a value is one of the passed types and if not throws an `InvalidArgumentException` exception.
Valid types are: array, callable, bool, float, int, string, iterable, object, or classname. You can check for any number
of types.

```
// Throw an exception is $value is not either a string or an array
UnionTypes::assert('propertyName', $value, 'string', 'array');

// Throw an exception is $value is not either a string or a DateTime object
UnionTypes::assert($value, 'string', 'DateTime');
```
