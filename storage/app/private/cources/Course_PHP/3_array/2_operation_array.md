---
title: "Basic operations on arrays in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 2
seo_title: "PHP Array Operations: Add, Remove, Search, Sort, Merge | Beginner’s Guide"
seo_description: "Learn the most common array operations in PHP: adding, removing, modifying, iterating, searching, sorting, merging, and transforming arrays. Complete with code examples and best practices."
slug: basic-operations-arrays-php
---

Arrays in PHP are one of the most important tools in daily programming. They allow you to store multiple values in a single variable and manage them conveniently. In this lesson, you’ll learn the most common operations on arrays: adding, reading, modifying, removing elements, iterating, searching, sorting, merging, and transforming data. With this knowledge, you’ll start writing cleaner and more efficient PHP code.

---

## Basics: what will we do with arrays?

From the previous lesson, you already know the types of arrays: indexed (numeric), associative (string keys), and multidimensional. Now let’s focus on the operations you’ll use most often:

* Adding and removing elements
* Reading and modifying values
* Iterating through arrays (loops)
* Searching for elements and checking if keys exist
* Sorting (by keys and values)
* Merging and splitting arrays
* Simple transformations (map, filter, reduce)

All examples are in pure PHP with comments for easy understanding.

---

## Creating and Reading Elements

### Creating arrays

```php
<?php
// Indexed array (numeric)
$numbers = [10, 20, 30];

// Associative array (string keys)
$person = [
    'name' => 'Ada',
    'age'  => 30,
];

// Multidimensional array (array of arrays)
$users = [
    ['id' => 1, 'name' => 'Ada'],
    ['id' => 2, 'name' => 'Jan'],
];
```

### Reading elements and basic info

```php
<?php
echo $numbers[0];        // 10
echo $person['name'];    // Ada

// Number of elements
echo count($numbers);    // 3
```

---

## Adding and Modifying Elements

### Adding elements to the end and beginning

```php
<?php
$fruits = ['apple', 'banana'];

// Easiest and fastest way to append at the end
$fruits[] = 'pear';   // ['apple', 'banana', 'pear']

// array_push also works, returns new length
array_push($fruits, 'plum', 'cherry');

// Add at the beginning
array_unshift($fruits, 'strawberry'); // ['strawberry', 'apple', ...]
```

### Adding and updating in associative arrays

```php
<?php
$person = ['name' => 'Ada', 'age' => 30];

// Add new key
$person['email'] = 'ada@example.com';

// Update existing key
$person['age'] = 31;
```

### Modifying nested elements

```php
<?php
$users = [
    ['id' => 1, 'name' => 'Ada'],
    ['id' => 2, 'name' => 'Jan'],
];

// Change user with index 1
$users[1]['name'] = 'John';
```

---

## Removing Elements

```php
<?php
$numbers = [10, 20, 30, 40];

// Remove last and return it
$last = array_pop($numbers);     // $last = 40, $numbers = [10, 20, 30]

// Remove first and return it
$first = array_shift($numbers);  // $first = 10, $numbers = [20, 30]

// Remove element by index/key
unset($numbers[0]);              // $numbers = [1 => 30] (creates a "hole")

// Reindex after unset (optional)
$numbers = array_values($numbers); // [30]
```

Cutting out fragments:

```php
<?php
$letters = ['a', 'b', 'c', 'd', 'e'];

// array_splice modifies original and returns cut part
$removed = array_splice($letters, 1, 2); // $removed = ['b','c'], $letters = ['a','d','e']
```

---

## Iterating Through Arrays

### foreach – easiest way

```php
<?php
$person = ['name' => 'Ada', 'age' => 31, 'email' => 'ada@example.com'];

foreach ($person as $key => $value) {
    echo "$key: $value\n";
}
```

### foreach by reference (modifying elements)

```php
<?php
$numbers = [1, 2, 3];

foreach ($numbers as &$n) {
    $n *= 2; // modifies original
}
unset($n); // IMPORTANT: break reference to avoid issues later

// $numbers = [2, 4, 6]
```

### for – good for indexed arrays

```php
<?php
$numbers = [10, 20, 30, 40];
$len = count($numbers); // avoid recalculating in each iteration

for ($i = 0; $i < $len; $i++) {
    echo $numbers[$i] . "\n";
}
```

---

## Searching and Checking Elements

### Check if value exists

```php
<?php
$fruits = ['apple', 'banana', 'pear'];

if (in_array('banana', $fruits, true)) {
    echo "Banana found!";
}
```

### Find index/key by value

```php
<?php
$pos = array_search('pear', $fruits, true); // index 2 or false if not found
```

### Key existence vs isset

```php
<?php
$data = ['a' => null];

// array_key_exists checks even if null
var_dump(array_key_exists('a', $data)); // true

// isset requires non-null
var_dump(isset($data['a']));            // false
```

### Searching in multidimensional arrays

```php
<?php
$users = [
    ['id' => 1, 'name' => 'Ada'],
    ['id' => 2, 'name' => 'Jan'],
    ['id' => 3, 'name' => 'Ola'],
];

$ids = array_column($users, 'id'); // [1, 2, 3]
$index = array_search(2, $ids);    // 1
$user = $index !== false ? $users[$index] : null; // ['id' => 2, 'name' => 'Jan']
```

---

## Sorting Arrays

### Sorting values (reindexes keys)

```php
<?php
$nums = [3, 1, 2];
sort($nums);  // [1, 2, 3]
rsort($nums); // [3, 2, 1]
```

### Sorting while preserving keys

```php
<?php
$ages = ['Ada' => 31, 'Jan' => 28, 'Ola' => 35];

asort($ages);  // sort by values ascending, keep keys
arsort($ages); // descending

ksort($ages);  // sort by keys ascending
krsort($ages); // descending
```

### Custom sorting

```php
<?php
$users = [
    ['name' => 'Ada', 'age' => 31],
    ['name' => 'Jan', 'age' => 28],
    ['name' => 'Ola', 'age' => 35],
];

usort($users, fn($a, $b) => $a['age'] <=> $b['age']);
```

---

## Merging, Cutting, Splitting Arrays

### Merging arrays

```php
<?php
$a = [1, 2];
$b = [3, 4];
$merged = array_merge($a, $b); // [1,2,3,4]

// Associative: later values overwrite earlier ones
$a = ['a' => 1, 'b' => 2];
$b = ['b' => 20, 'c' => 3];
$merged = array_merge($a, $b); // ['a' => 1, 'b' => 20, 'c' => 3]

// Union (+): keeps left values on conflict
$union = $a + $b; // ['a' => 1, 'b' => 2, 'c' => 3]
```

### Cutting without modifying original

```php
<?php
$letters = ['a', 'b', 'c', 'd', 'e'];

$part = array_slice($letters, 1, 3); // ['b','c','d']
```

### Splitting into chunks

```php
<?php
$nums = [1, 2, 3, 4, 5];
$chunks = array_chunk($nums, 2); // [[1,2],[3,4],[5]]
```

### Convert array ↔ text

```php
<?php
$fruits = ['apple', 'banana', 'pear'];

$csv = implode(',', $fruits);    // "apple,banana,pear"
$back = explode(',', $csv);      // ['apple','banana','pear']
```

---

## Transformations: map, filter, reduce

### array_map

```php
<?php
$prices = [10, 20, 30];
$withVat = array_map(fn($p) => $p * 1.23, $prices); // [12.3,24.6,36.9]
```

### array_filter

```php
<?php
$nums = [1,2,3,4,5,6];
$even = array_filter($nums, fn($n) => $n % 2 === 0); // [2,4,6]
```

### array_reduce

```php
<?php
$nums = [1,2,3,4];
$sum = array_reduce($nums, fn($acc, $n) => $acc + $n, 0); // 10
```

---

## Set-like Operations

```php
<?php
$letters = ['a','b','a','c'];

$unique = array_unique($letters); // ['a','b','c']

$a = [1,2,3];
$b = [2,3,4];
$common = array_intersect($a, $b); // [2,3]
$diff = array_diff($a, $b); // [1]
```

---

## Helper Functions

```php
<?php
$person = ['name' => 'Ada', 'age' => 31];

$keys = array_keys($person);     // ['name','age']
$values = array_values($person); // ['Ada',31]

$upper = array_change_key_case($person, CASE_UPPER); // ['NAME'=>'Ada','AGE'=>31]
```

---

## Best Practices and Common Mistakes

* Use the simplest tool:

    * Append with \$arr\[] instead of array_push in loops.
    * Iterate associative arrays with foreach (\$key => \$value).
* Be aware of reindexing:

    * sort/rsort reindex keys; use asort/ksort to preserve them.
    * unset leaves a gap; use array_values to rebuild.
* Check keys properly:

    * array_key_exists works even with null.
    * isset requires non-null.
* Use strict comparisons:

    * in_array/array_search with true flag.
* Beware foreach by reference:

    * Always unset(\$v) after foreach (&\$v).
* Merging:

    * array_merge overwrites same string keys with later values.
    * * keeps left values on conflict.
* Performance:

    * Save count(\$arr) before for loops.
    * Use map/filter/reduce for clean transformations.
* Validate optional keys with ?? to avoid notices.
* Document array structure in comments or PHPDoc.

---

## Summary

* Arrays in PHP are flexible and powerful.
* Common operations: add (\$arr\[]), remove (array_pop/shift/unset), iterate (foreach), search (in_array/array_search), sort (sort/asort/ksort), merge (array_merge/+), transform (map/filter/reduce).
* Key reminders: preserve keys when needed, know isset vs array_key_exists, use strict comparisons.
* Pick readable, consistent patterns.

---

## Mini Quiz

1. How to add an element to the end of an indexed array?

* a) array_unshift(\$arr, \$val)
* b) \$arr\[] = \$val
* c) array_merge(\$arr, \[\$val])

2. Which function checks if a key exists even if its value is null?

* a) isset(\$arr\['key'])
* b) in_array('key',\$arr)
* c) array_key_exists('key',\$arr)

3. Which function sorts an associative array by values while preserving keys?

* a) sort
* b) asort
* c) ksort

4. How to find index of 'Ola' strictly in \['Ada','Ola','Jan']?

* a) array_search('Ola',\$arr)
* b) array_search('Ola',\$arr,true)
* c) in_array('Ola',\$arr)

5. How to remove index 2 and shift others to avoid holes?

* a) unset(\$arr\[2])
* b) array_splice(\$arr,2,1)
* c) array_shift(\$arr)

6. Difference between array_merge and + with associative arrays?

* a) Same
* b) array_merge overwrites with later values, + keeps left
* c) Opposite

7. Which function transforms each element?

* a) array_filter
* b) array_map
* c) array_reduce

8. How to sort users by 'age' ascending?

* a) asort(\$users)
* b) ksort(\$users)
* c) usort(\$users, fn(\$a,\$b)=>\$a\['age']<=>\$b\['age'])

Answers: 1-b, 2-c, 3-b, 4-b, 5-b, 6-b, 7-b, 8-c
