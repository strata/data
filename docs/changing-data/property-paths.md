# Accessing properties

We use Symfony's [PropertyAccess](https://symfony.com/doc/current/components/property_access.html) component to help read and write data.

## Array properties

To access array properties use the index notation, specifying array keys within square brackets.

Access `$data['name']`:

```text
[name]
```

Access `$data['people']['name']`:

```text
[people][name]
```

Access `$data['people']['categories'][0]`:

```text
[people][categories][0]
```

## Object properties

To access object properties use the dot notation, specifying object properties separated by a dot character.

Access `$data->name`:

```text
name
```

Access `$data->people->name`:

```text
people.name
```

Access `$data->people->categories[0]`:

```text
people.name.categories[0]
```

