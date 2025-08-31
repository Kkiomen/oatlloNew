---
title: "Additional Array Functions in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP Array Functions: map, filter, reduce, merge, and search"
seo_description: "Master advanced PHP array functions including map, filter, reduce, merge, and search. Learn powerful array manipulation techniques with examples."
slug: php-array-functions-map-filter-reduce-merge
---

Arrays are one of the most frequently used data types in PHP. Beyond the basics (adding/removing elements, iteration, sorting), PHP offers dozens of **built-in array functions**, which allow you to:

* transform data (mapping, filtering),
* combine and compare sets,
* extract columns from multidimensional arrays,
* work with keys and values,
* generate and fill arrays,
* randomly select elements.

Knowing these functions speeds up **PHP programming**, simplifies code, and helps you write **cleaner, more efficient, and secure** applications.

Related earlier lessons: Sorting Arrays in PHP, Iterating Arrays, Basic Array Operations, Indexed/Associative/Multidimensional Arrays.

---

## Basic Explanation

Many array functions in PHP follow similar patterns:

* Some functions return a **new array** (e.g., array_map, array_filter, array_merge) — they do not modify the original.
* Others **modify the array in place** (e.g., shuffle, array_splice) — changes apply to the original.
* Some functions work with a **callback** (anonymous function or function name), which PHP calls for each element.
* Many functions support **strict comparison modes**, helping avoid subtle bugs.

In this lesson, you’ll see practical examples of using additional array functions, understand their use cases, and avoid pitfalls.

---

## PHP Code Examples

### Transforming: array_map, array_walk, array_walk_recursive

```php
<?php
// array_map — creates a NEW array based on transforming values
$prices = [10, 20.5, 30];
$withVat = array_map(fn($p) => $p * 1.23, $prices);
// $withVat: [12.3, 25.215, 36.9]; $prices unchanged

// array_walk — iterates and allows modifying in place
$names = ['jan', 'ANNA', 'KaSpEr'];
array_walk($names, function (&$value) {
    $value = ucfirst(strtolower($value)); // Jan, Anna, Kasper
});

// array_walk_recursive — also works for nested arrays
$data = [
    'user' => ['NAME' => 'ALICJA', 'CITY' => 'WARSZAWA'],
    'meta' => ['ROLE' => 'ADMIN']
];
array_walk_recursive($data, function (&$v, $k) {
    $v = is_string($v) ? strtolower($v) : $v;
});
// All strings in $data are now lowercase
```

### Filtering: array_filter, array_unique

```php
<?php
$numbers = [0, 1, 2, 3, 4, 5];
$even = array_filter($numbers, fn($n) => $n % 2 === 0);
// Keeps original keys: [0 => 0, 2 => 2, 4 => 4]

// Use keys in callback:
$assoc = ['a' => 1, 'b' => 2, 'c' => 1];
$onlyValue1 = array_filter($assoc, fn($v, $k) => $v === 1, ARRAY_FILTER_USE_BOTH);

// array_unique — removes duplicate values (keeps first occurrence)
$vals = [10, 10, '10', 20];
$uniqueLoose = array_unique($vals);              // [10, '10', 20]
$uniqueStrict = array_unique($vals, SORT_STRING); // treats numbers as strings
```

### Reduction and Aggregation: array_reduce, array_sum, array_product

```php
<?php
$orders = [
    ['id' => 1, 'total' => 99.99],
    ['id' => 2, 'total' => 149.50],
    ['id' => 3, 'total' => 10.00],
];

$total = array_reduce($orders, fn($carry, $o) => $carry + $o['total'], 0.0);
// $total: 259.49

$nums = [2, 3, 4];
echo array_sum($nums);     // 9
echo array_product($nums); // 24
```

### Columns and Restructuring: array_column, array_combine, array_flip, array_change_key_case

```php
<?php
$users = [
    ['id' => 10, 'name' => 'Ala', 'email' => 'ala@example.com'],
    ['id' => 11, 'name' => 'Ola', 'email' => 'ola@example.com'],
];

$emails = array_column($users, 'email');
$byId = array_column($users, 'email', 'id');

$keys = ['pl', 'en', 'de'];
$values = ['Witaj', 'Hello', 'Hallo'];
$map = array_combine($keys, $values);

$flipped = array_flip(['a' => 1, 'b' => 2]); // [1 => 'a', 2 => 'b']

$raw = ['Name' => 'Ada', 'EMAIL' => 'ada@example.com'];
$lower = array_change_key_case($raw, CASE_LOWER);
```

### Keys and Values: array_keys, array_values, array_key_first/last, array_key_exists

```php
<?php
$assoc = ['a' => 10, 'b' => 20, 'c' => 10];

$keysAll = array_keys($assoc);
$keysFor10 = array_keys($assoc, 10, true);

$vals = array_values($assoc);

$firstKey = array_key_first($assoc);
$lastKey = array_key_last($assoc);

var_dump(array_key_exists('a', $assoc)); // true
```

### Set Comparisons: array_diff, array_diff_assoc, array_intersect, array_intersect_key

```php
<?php
$a = ['a' => 1, 'b' => 2, 'c' => 3];
$b = ['a' => 1, 'b' => 99, 'd' => 4];

var_dump(array_diff($a, $b));
var_dump(array_diff_assoc($a, $b));
var_dump(array_intersect($a, $b));
var_dump(array_intersect_key($a, $b));
```

### Combining and Replacing: array_merge, array_merge_recursive, array_replace, operator +

```php
<?php
$one = ['a' => 1, 'b' => 2];
$two = ['b' => 20, 'c' => 3];

var_dump(array_merge($one, $two));
var_dump(array_merge_recursive($one, $two));
var_dump(array_replace($one, $two));
var_dump($one + $two);
```

### Generating and Filling: range, array_fill, array_fill_keys, array_pad

```php
<?php
$nums = range(1, 5);
$letters = range('a', 'e');

$filled = array_fill(0, 3, 'X');

$keys = ['id', 'name', 'email'];
$empty = array_fill_keys($keys, null);

$base = [1, 2];
$padded = array_pad($base, 5, 0);
$leftPad = array_pad($base, -5, 0);
```

### Randomness and Shuffling: shuffle, array_rand

```php
<?php
$items = ['a','b','c','d'];
shuffle($items);

$colors = ['red' => '#f00', 'green' => '#0f0', 'blue' => '#00f'];
$oneKey = array_rand($colors);
$twoKeys = array_rand($colors, 2);
```

### Reversing: array_reverse

```php
<?php
$nums = [1, 2, 3];
$reversed = array_reverse($nums);

$assoc = ['a'=>1,'b'=>2,'c'=>3];
$revAssoc = array_reverse($assoc, true);
```

### Searching: in_array, array_search

```php
<?php
$vals = [0, '0', false, null, 1];

var_dump(in_array('0', $vals));
var_dump(in_array('0', $vals, true));
var_dump(in_array(0, $vals, true));
var_dump(in_array('', $vals, true));

$key = array_search(false, $vals, true);
if ($key !== false) {
    // Found safely
}
```

### Utilities: array_is_list, is_array, compact, extract (use carefully)

```php
<?php
var_dump(array_is_list([10,20,30])); // true
var_dump(array_is_list(['a'=>1, 0=>2, 1=>3])); // false

$name = 'Ada';
$age = 30;
$user = compact('name', 'age');

$data = ['token' => 'abc', 'id' => 1];
extract($data); // creates $token, $id
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **strict comparisons** where possible: in_array(\$v, \$arr, true), array_search(\$v, \$arr, true).
* Distinguish between functions returning a new array vs modifying in place.
* For associative arrays, prefer **array_replace** over array_merge_recursive when you need overwrite behavior.
* Use **array_column** to extract data from records/objects.
* After filtering with array_filter, use **array_values** to reindex if continuous keys are needed.
* Validate input sizes: **array_combine** requires same number of keys and values.
* Use **array_is_list** (PHP 8.1+) to validate list-like arrays.

### Common Mistakes and Pitfalls

* Confusing loose vs strict comparisons in array_search (0 vs false).
* array_merge rebuilds numeric keys, operator + preserves left keys.
* array_flip loses data with duplicate values.
* array_merge_recursive may create nested arrays unexpectedly.
* Memory usage: array_map/filter create new arrays — consider foreach for huge datasets.
* extract can overwrite variables and pose security risks — use sparingly.
* array_rand returns int for 1 element, array for multiple — handle both cases.

---

## Summary

* PHP provides a rich set of array functions: map, filter, reduce, column extraction, set comparisons, merging, generating, randomness, reversing, searching, and more.
* Choose functions that make code shorter, clearer, and less error-prone.
* Remember differences: return vs modify in place, strict comparisons, key/index handling.
* PHP 8.1+ adds array_is_list for validating list arrays.

---

## Mini Quiz

1. Which function extracts emails from a list of users? → array_column
2. Which function merges arrays and overwrites conflicting string keys? → array_merge
3. What does array_search return if not found? → false
4. How to reindex after array_filter? → array_values
5. Difference between array_merge and + for associative arrays? → array_merge overwrites, + keeps left values
6. Function to check if array has sequential 0..n keys? (PHP 8.1+) → array_is_list
7. True/False: array_flip is safe with duplicate values → False
8. Sum numbers in array? → array_sum (or array_reduce)

---

Practice each function with your own data. In real-world PHP projects, combining several array functions (map → filter → column) creates concise and professional code.
