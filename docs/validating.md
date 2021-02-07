# Validating data

You can validate incoming data based on a set of rules. One example validator is supplied, the [Validation rules validator](#validation-rules). 

You can create a custom validator by implementing the `Strata\Data\Validate\ValidatorInterface` interface. This requires
a `isValid($item)` method to test whether a data item is valid and a `getErrorMessage()` method to return the error message 
for the last validation attempt.

The basic flow is:

* Instantiate the validator object
* Call the `isValid($item)` method, passing the data item object (`Strata\Data\Model\Item`) to this methodd
* Return any error message via `getErrorMessage()`  
* Take any action you wish based on the validation result

```php
$validator = new CustomValidator();

if ($validator->isValid($item)) {
    // do something
}
```

## Validation rules

A simple validation rules system exists, inspired by [Laravel's Validation](https://laravel.com/docs/validation).

Simply create a new instance of `Strata\Data\Validate\ValidationRules` and pass in the validation requirements in the 
constructor. This is an array made up of the data property to validate (with nested array elements separated by a dot)
and the validation rule.

Rule format is:

```
'data property name' => 'rule|another rule:values separated by commas'
```

E.g.
```php
$rules = [
    'total'         => 'required|integer', 
    'data.title'    => 'required',
    'data.type'     => 'required|in:1,2,3',
];
```

In this example:
* `$item['total']` must exist and be an integer.
* `$item['data']['title']` must exist.
* `$item['data']['type']` must exist and have a value of: 1, 2, 3.

A complete example:

```php
$validator = new ValidationRules([
    'total'         => 'required|integer',
    'data.title'    => 'required',
    'data.type'     => 'required|in:1,2,3',
];

if ($validator->isValid($item)) {
    // do something
}
```

Available validation rules:
* [array](#array)
* [boolean](#boolean)
* [email](#email)
* [image](#image)
* [in](#in)
* [number](#number)
* [required](#required)
* [url](#url)

### array

Tests whether the property is an array.

### boolean

Tests whether the property is "1", "true", "on" and "yes". Returns false otherwise.

### email

Tests whether the property is a valid email address.

### image

Tests whether the property is an image filename (jpg, jpeg, png, bmp, gif, svg, or webp). 

### in

Tests whether the property value is one of the allowed passed values. For example, a rule of `in:1,2,3` tests for whether 
the property value is 1, 2, or 3. 

### number

Tests whether the property is a number, using PHP's [is_numeric()](https://www.php.net/is-numeric) rules.

### required

Tests whether the property exists and is non-empty. Empty is defined as null, an empty array or an empty string. 

### url

Tests whether the property is a valid URL.

### Custom validation rules

You can also define your own custom validation rule by creating a class that implements the `Strata\Data\Validate\RuleInterface`
interface. It is recommended to extend `Strata\Data\Validate\Rule\RuleAbstract` which makes building custom rules easier.

To use a custom validation rule pass an instance of the class as the rule:  

```php
$validator = new ValidationRules([
    'total'         => 'required|integer',
    'data.title'    => new CustomRuleValidation(),
];
```