---
title: "Sorting Arrays in PHP: sort, asort, ksort"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "Sorting Arrays in PHP: sort, asort, ksort"
seo_description: "Learn how to sort arrays in PHP using sort, asort, and ksort. Understand flags, stability, best practices, and common pitfalls with practical code examples."
slug: sorting-arrays-php-sort-asort-ksort
---


Sorting arrays is one of the most common operations in PHP programming. It lets you order data by values or keys, which is essential in reports, product lists, rankings, filtering results, or preparing data for display in a user interface. In this lesson, you’ll learn how sorting works with PHP functions: **sort**, **asort**, and **ksort**. You’ll see practical code examples, best practices, and the most common mistakes to avoid.

---

## Basics of Array Sorting in PHP

### What you need to know first

* **Indexed arrays** have numeric indexes (0, 1, 2, ...).
* **Associative arrays** have string keys (e.g., "name" => "Alice").
* PHP provides different sorting functions:

    * **sort** – sorts by values but reindexes the array (loses original keys).
    * **asort** – sorts by values while preserving keys (good for associative arrays).
    * **ksort** – sorts by keys (alphabetically or numerically).

All these functions:

* Work **in place** (modify the original array).
* Return a boolean (true on success).
* Can take additional sorting flags such as **SORT_NUMERIC**, **SORT_STRING**, **SORT_NATURAL**, **SORT_FLAG_CASE**.

*Note:* Since PHP 8, built-in sorting is stable (equal elements retain original order). This can matter when sorting where some values are the same.

---

## PHP Code Examples

### sort — sorting by values with reindexing

```php
<?php
$numbers = [5, 2, 9, 1, 2];

sort($numbers); // sorts ascending and reindexes
print_r($numbers); // [1, 2, 2, 5, 9]
```

Sorting strings vs numbers:

```php
<?php
$data = ["10", "2", "Ananas", "banana"];

// Default sorting (SORT_REGULAR):
sort($data);
print_r($data);
// "10" and "2" are strings, compared lexicographically ("10" < "2").

// Force numeric sorting:
$data = ["10", "2", "30"];
sort($data, SORT_NUMERIC);
print_r($data); // ["2", "10", "30"]

// Force string sorting (case-insensitive):
$fruits = ["banana", "Ananas", "plum"];
sort($fruits, SORT_STRING | SORT_FLAG_CASE);
print_r($fruits); // ["Ananas", "banana", "plum"]
```

Natural sorting (e.g., "file2" before "file10"):

```php
<?php
$files = ["file10.txt", "file2.txt", "file1.txt"];
natsort($files); // natural value sorting, preserves keys
print_r($files);
```

Reverse sorting:

* sort descending: `rsort($array)`
* asort descending: `arsort($array)`
* ksort descending: `krsort($array)`

### asort — sorting by values while preserving keys

```php
<?php
$ages = [
    "Alice" => 25,
    "Olek"  => 19,
    "Zenek" => 19,
    "Bartek"=> 30,
];

asort($ages);
print_r($ages);
/*
Result:
[
    "Olek"  => 19,
    "Zenek" => 19,
    "Alice" => 25,
    "Bartek"=> 30,
]
*/
```

Descending:

```php
<?php
arsort($ages);
print_r($ages);
```

With numeric flag:

```php
<?php
$scores = ["Alice" => "10", "Olek" => "2", "Bartek" => "30"];
asort($scores, SORT_NUMERIC);
print_r($scores);
```

### ksort — sorting by keys

```php
<?php
$settings = [
    "db_host" => "localhost",
    "app_env" => "prod",
    "cache"   => true,
];

ksort($settings);
print_r($settings);
```

String keys that look like numbers:

```php
<?php
$data = ["10" => "X", "2" => "Y", "1" => "Z"];

ksort($data);
print_r($data); // "1","10","2" (lexicographic)

ksort($data, SORT_NUMERIC);
print_r($data); // 1,2,10
```

Reverse key sort:

```php
<?php
krsort($settings);
print_r($settings);
```

---

## Best Practices and Common Mistakes

### Best Practices

* Decide what you are sorting:

    * By values, keys don’t matter → **sort/rsort**.
    * By values, keep keys → **asort/arsort**.
    * By keys → **ksort/krsort**.
* Pick the right sorting flag:

    * **SORT_NUMERIC** for numbers or numeric-like strings.
    * **SORT_STRING** for explicit text sorting.
    * **SORT_NATURAL** (+ **SORT_FLAG_CASE**) for values with numbers in text.
* Preserve the original if needed:

    * Sorting functions modify in place. Copy first if you need both versions.
* Remember PHP 8+: sorting is stable (equal values keep original order).
* For proper language rules (like Polish characters), use intl’s Collator::sort/asort.

### Common Mistakes

* Losing keys with sort/rsort (use asort/arsort instead).
* Wrong comparison mode: "10" vs "2" as strings → lexicographic order.
* Mixing value types: numbers + strings may behave unexpectedly.
* Assuming sort returns a sorted array (it returns bool, modifies in place).
* Overcomplicating with usort when flags suffice.

---

## Additional Examples

Natural sort, case-insensitive:

```php
<?php
$files = ["File10.txt", "file2.txt", "file1.txt"];
natcasesort($files);
print_r($files);
```

Sort keys numerically:

```php
<?php
$map = ["2" => "B", "10" => "C", "1" => "A"];
ksort($map, SORT_NUMERIC);
print_r($map);
```

Multi-stage sorting (thanks to stability):

```php
<?php
$products = [
    ["name" => "Bananas", "price" => 5],
    ["name" => "Ananas",  "price" => 5],
    ["name" => "Plums",   "price" => 7],
];

usort($products, fn($a,$b) => $a["name"] <=> $b["name"]);
usort($products, fn($a,$b) => $a["price"] <=> $b["price"]);
print_r($products);
```

---

## Summary

* **sort** – sorts by values, reindexes (loses keys).
* **asort** – sorts by values, keeps keys (best for associative arrays).
* **ksort** – sorts by keys.
* Use correct flags: **SORT_NUMERIC**, **SORT_STRING**, **SORT_NATURAL**.
* Sorting modifies arrays in place, returns bool.
* Beware mixed types, key preservation, and misunderstanding return values.
* For locale-aware sorting: use intl’s Collator.

---

## Mini Quiz

1. Which function sorts associative array by values and keeps keys? → asort
2. Which flag for numeric-like strings? → SORT_NUMERIC
3. What does ksort do? → Sorts by keys, preserves values
4. How to sort \["file2","file10","file1"] naturally? → natsort
5. Does sort() return the sorted array? → No (returns bool, sorts in place)
6. How to sort associative array by keys descending? → krsort

---

Now you know the essentials of sorting arrays in PHP. Practice with different flags and data types to gain confidence using sort, asort, and ksort effectively.
