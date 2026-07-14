---
title: "PHP - arrays: declarations, operations, sorting, and functions"
slug: array
description: "Learn about arrays in PHP: declarations, operations, iterations, sorting, and built-in functions. Practice working with simple, associative, and multidimensional arrays."
---

Arrays in PHP – a complete guide

Arrays in PHP are one of the most important data structures. They allow you to store multiple values in a single variable. In this chapter, you will learn how to declare arrays, perform operations, iterate, sort, and use PHP’s rich set of built-in functions.

## Declaring arrays

```php
<?php
// Indexed array
$fruits = ["apple", "banana", "orange"];

// Associative array
$user = [
    "name" => "John",
    "age" => 25,
    "active" => true
];

// Multidimensional array
$matrix = [
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9]
];
```

## Accessing elements

```php
echo $fruits[0]; // apple
echo $user["name"]; // John
```

## Basic operations

- `count($array)` – number of elements
- `array_push($array, $value)` – add element
- `array_pop($array)` – remove the last element
- `unset($array[$index])` – remove a selected element
- `in_array($value, $array)` – check if a value exists

## Iterating over arrays

```php
foreach ($fruits as $fruit) {
    echo $fruit . "\n";
}

foreach ($user as $key => $value) {
    echo "$key: $value\n";
}
```

## Sorting arrays

- `sort($array)` – sort ascending
- `rsort($array)` – sort descending
- `asort($array)` – sort associative array by values
- `ksort($array)` – sort associative array by keys

```php
$numbers = [3, 1, 4, 2];
sort($numbers);
print_r($numbers); // [1, 2, 3, 4]
```

## Useful array functions

- `array_merge()` – merging arrays
- `array_keys()` and `array_values()`
- `array_map()` – apply a function to each element
- `array_filter()` – filter an array
- `array_reduce()` – reduce to a single value

```php
$nums = [1, 2, 3, 4];
$sum = array_reduce($nums, fn($carry, $item) => $carry + $item, 0);
echo $sum; // 10
```

## Best practices

1. Use associative arrays instead of multiple variables with similar meaning.
2. When iterating over large arrays, prefer `foreach` – it’s clear and safe.
3. For filtering and mapping, use built-in functions instead of writing loops manually.

FAQWhat is the difference between an indexed array and an associative array?An indexed array uses numeric indexes, while an associative array uses named keys.Does PHP have lists or dictionaries like other languages?In PHP, all such structures are implemented with arrays – they can act as lists, maps, or dictionaries.Can I mix data types in a single array?Yes, PHP allows storing different types in one array, e.g., numbers, strings, and objects.
