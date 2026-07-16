---
title: "New Array Functions in PHP 8.4: array_find, array_any, array_all"
slug: new-array-functions
seo_title: "PHP 8.4 array_find, array_any, array_all, array_find_key Guide"
seo_description: "Learn PHP 8.4's new array functions: array_find, array_find_key, array_any, and array_all - cleaner ways to search arrays with a callback."
---

In [the array functions lesson](/course/php/array/php-array-functions-map-filter-reduce-merge) you met `array_map`, `array_filter`, and friends. **PHP 8.4** adds four small but handy functions for **searching** an array with a callback: `array_find`, `array_find_key`, `array_any`, and `array_all`. They replace common loops you've probably written many times.

All four take the array first and a **callback** second. The callback receives each value (and optionally its key) and returns `true` or `false`. If callbacks are still new to you, revisit [anonymous functions](/course/php/function/php-anonymous-functions-guide).

We'll use this sample array throughout the lesson:

```php
<?php
$users = [
    ['name' => 'Ann',  'age' => 17],
    ['name' => 'Bob',  'age' => 25],
    ['name' => 'Cara', 'age' => 31],
];
```

## array_find: get the first matching element

`array_find()` returns the **first element** for which the callback returns `true`. If nothing matches, it returns `null`.

```php
<?php
$firstAdult = array_find($users, fn($user) => $user['age'] >= 18);

// ['name' => 'Bob', 'age' => 25]
print_r($firstAdult);
```

Before 8.4, you'd write a `foreach` with a `break` to do this:

```php
<?php
// The old way
$firstAdult = null;
foreach ($users as $user) {
    if ($user['age'] >= 18) {
        $firstAdult = $user;
        break;
    }
}
```

`array_find` is that whole block in one line.

## array_find_key: get the key instead of the value

`array_find_key()` works the same way but returns the **key** of the first match rather than the value. It returns `null` if nothing matches.

```php
<?php
$key = array_find_key($users, fn($user) => $user['age'] >= 18);

echo $key; // 1  (Bob is at index 1)
```

This is useful when you need to know *where* the match is - for example, to update or remove that element afterward.

## array_any: is there at least one match?

`array_any()` returns `true` if **at least one** element passes the callback, and `false` otherwise. It stops as soon as it finds a match.

```php
<?php
$hasMinor = array_any($users, fn($user) => $user['age'] < 18);

var_dump($hasMinor); // bool(true) - Ann is 17
```

Read it out loud: "is any user a minor?" The function name matches the question.

## array_all: do they all match?

`array_all()` returns `true` only if **every** element passes the callback. It stops at the first failure.

```php
<?php
$allAdults = array_all($users, fn($user) => $user['age'] >= 18);

var_dump($allAdults); // bool(false) - Ann is only 17
```

"Are all users adults?" No, because Ann isn't. If you removed Ann, this would return `true`.

## The callback can also receive the key

Like `array_filter` with its flag, these functions can pass the **key** as a second argument to your callback. That's handy when the key carries meaning:

```php
<?php
$prices = ['apple' => 3, 'banana' => 2, 'cherry' => 8];

$hasExpensiveFruit = array_any(
    $prices,
    fn($price, $name) => $price > 5
);

var_dump($hasExpensiveFruit); // bool(true) - cherry is 8
```

## Choosing the right one

- Need the **matching element**? Use `array_find`.
- Need its **key/position**? Use `array_find_key`.
- Just need a **yes/no "is there one?"**? Use `array_any`.
- Need a **yes/no "are they all?"**? Use `array_all`.

## Summary

- PHP 8.4 adds `array_find`, `array_find_key`, `array_any`, and `array_all`.
- All take the array first and a callback second; the callback returns `true`/`false`.
- `array_find` returns the first matching value (or `null`); `array_find_key` returns its key.
- `array_any` returns `true` if at least one matches; `array_all` returns `true` only if all match.
- They replace common `foreach` + `break` loops with a single, readable line.

## FAQ

### What does `array_find` return if nothing matches?

It returns `null`. `array_find_key` also returns `null` when there is no match, so check for `null` before using the result.

### What's the difference between `array_any` and `array_all`?

`array_any` returns `true` if **at least one** element passes the test. `array_all` returns `true` only if **every** element passes. Both return a boolean, not the elements themselves.

### Can I use the array key in the callback?

Yes. The callback receives the value as the first argument and the key as the second, so you can write `fn($value, $key) => ...` when the key matters.
