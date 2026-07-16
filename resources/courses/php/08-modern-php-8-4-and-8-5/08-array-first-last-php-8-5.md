---
title: "array_first() and array_last() in PHP 8.5"
slug: array-first-last
seo_title: "PHP 8.5 array_first() and array_last() - Simple Guide"
seo_description: "Learn PHP 8.5's array_first() and array_last() functions to get the first and last value of an array cleanly, even with non-numeric keys."
---

Getting the **first** or **last** value of an array sounds trivial, but in PHP it used to be surprisingly awkward. **PHP 8.5** finally adds two obvious functions: `array_first()` and `array_last()`. This short lesson shows what they do and why they're a nice cleanup.

## Why getting the first array element was awkward before

If your array has plain numeric keys starting at 0, you can grab the first item with `$array[0]`. But that breaks the moment your keys are different - say, after filtering an array, or when the keys are strings:

```php
<?php
$users = ['u10' => 'Ann', 'u20' => 'Bob', 'u30' => 'Cara'];

echo $users[0]; // Warning: undefined key 0 - there is no key "0"
```

There's no key `0` here, so `$users[0]` fails. To reliably get the first value regardless of the keys, people used tricks like `reset()`, `array_key_first()`, or `array_values($users)[0]` - all a bit clumsy and easy to get wrong.

## The new way: array_first() and array_last()

PHP 8.5 gives you two clear functions. `array_first()` returns the **first value** in the array, and `array_last()` returns the **last value** - no matter what the keys look like:

```php
<?php
$users = ['u10' => 'Ann', 'u20' => 'Bob', 'u30' => 'Cara'];

echo array_first($users); // Ann
echo array_last($users);  // Cara
```

They return the **value**, not the key. The keys can be numbers, strings, or a mix - it doesn't matter. The functions simply hand you the value at each end.

## They return null on an empty array

If the array is empty, both functions return `null` instead of causing an error:

```php
<?php
$empty = [];

var_dump(array_first($empty)); // NULL
var_dump(array_last($empty));  // NULL
```

That makes them safe to call without checking the length first. Just remember to handle the `null` case if an empty array is possible:

```php
<?php
$scores = [];

$best = array_last($scores) ?? 'no scores yet';
echo $best; // no scores yet
```

Here the `??` operator (the null coalescing operator) supplies a fallback when `array_last()` returns `null`.

## A practical example

Say you're reading a log where each entry was added in order, and you want the most recent one:

```php
<?php
$log = [
    'boot'    => 'System started',
    'login'   => 'User signed in',
    'error'   => 'Payment failed',
];

echo 'Latest event: ' . array_last($log); // Latest event: Payment failed
echo 'First event: '  . array_first($log); // First event: System started
```

Clear and readable - the function names say exactly what you get.

## Summary

- PHP 8.5 adds `array_first()` and `array_last()`.
- They return the **first** and **last value** of an array, whatever the keys are.
- They return `null` on an empty array, so they're safe to call without a length check.
- They replace clumsy workarounds like `array_values($array)[0]` or `reset()`.

## FAQ

### What do `array_first()` and `array_last()` return?

They return the first and last **value** of the array (not the key). If the array is empty, they return `null`.

### Do they work with string keys?

Yes. That's the main point - they return the value at each end regardless of whether the keys are numbers, strings, or mixed, unlike `$array[0]` which needs a numeric key.

### What happens on an empty array?

Both return `null` rather than throwing an error, so you can safely pair them with the `??` operator to provide a default value.
