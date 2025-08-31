---
title: "Indexed, Associative, and Multidimensional Arrays in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 1
seo_title: "PHP Arrays Tutorial: Indexed, Associative, Multidimensional"
seo_description: "Learn how to work with arrays in PHP. Master indexed, associative, and multidimensional arrays with practical examples and best practices."
slug: array-indexed-associative-multidimensional-php
---

## Introduction

Arrays in PHP are one of the most important data structures you’ll work with. They let you store multiple values in a single variable and manage them conveniently. With arrays you can:

* store lists (e.g., shopping cart items),
* map data by keys (e.g., user by email),
* build nested structures (e.g., API data, rows and columns).

In this lesson, you’ll learn from scratch what **indexed**, **associative**, and **multidimensional** arrays are in PHP, how to create, read, and modify them, as well as best practices and common pitfalls.

---

## Array Basics in PHP

### What is an array in PHP?

An array in PHP is an ordered collection of **key → value** pairs. In practice, each value (number, text, bool, another array, object) is assigned to a key (numeric or string).

* **Indexed arrays** use numeric keys (0, 1, 2, ...).
* **Associative arrays** use string keys (e.g., "email", "name").
* **Multidimensional arrays** are arrays containing other arrays.

PHP preserves the order of elements as they were added.

### Key and value types

* Key can be: **integer** or **string**.
* Note: strings that look like integers (e.g., "01", "1") are converted to integers ("01" → 1). This is a common source of bugs.
* Value can be any type (int, float, string, bool, array, object, null).

### How to create arrays?

The recommended modern syntax uses square brackets:

```php
<?php
$emptyArray = [];           // empty array
$numbers = [10, 20, 30];    // indexed array
$user = [                   // associative array
    'name' => 'Alex',
    'age' => 25,
];
```

The older `array()` syntax still works but prefer `[]` in new code.

---

## Types of Arrays in PHP

### Indexed arrays (numeric)

* Keys are integers: 0, 1, 2, ...
* PHP can assign indexes automatically.

Example:

```php
<?php
$fruits = ['apple', 'banana', 'peach']; // keys: 0,1,2
echo $fruits[1]; // outputs: banana
```

Adding:

```php
<?php
$fruits[] = 'strawberry'; // added at the end with the next index
```

### Associative arrays (maps, dictionaries)

* Use descriptive string keys instead of numbers.

Example:

```php
<?php
$user = [
    'name' => 'Kasia',
    'email' => 'kasia@example.com',
    'active' => true,
];

echo $user['email']; // kasia@example.com
```

### Multidimensional arrays (arrays of arrays)

* An element can be another array.

Example (user list):

```php
<?php
$users = [
    [
        'name' => 'Jan',
        'email' => 'jan@example.com',
    ],
    [
        'name' => 'Ola',
        'email' => 'ola@example.com',
    ],
];

echo $users[1]['email']; // ola@example.com
```

---

## PHP Code Examples

### Indexed arrays — create, read, modify

```php
<?php
$numbers = [5, 10, 15];

echo $numbers[0]; // 5

$numbers[] = 20;            // [5, 10, 15, 20]
array_push($numbers, 25);   // [5, 10, 15, 20, 25]

array_unshift($numbers, 0); // [0, 5, 10, 15, 20, 25]

$last = array_pop($numbers);    // removes 25
$first = array_shift($numbers); // removes 0

$numbers[1] = 999; // overwrite value

echo count($numbers);

for ($i = 0; $i < count($numbers); $i++) {
    echo $numbers[$i] . PHP_EOL;
}

foreach ($numbers as $value) {
    echo $value . PHP_EOL;
}

if (isset($numbers[10])) {
    // index exists
}
```

Note: in loops, assign `count($arr)` to a variable to avoid recalculating.

### Associative arrays — create, read, modify

```php
<?php
$product = [
    'id' => 101,
    'name' => 'Keyboard',
    'price' => 129.99,
    'available' => true,
];

echo $product['name']; // Keyboard

$product['vat'] = 23;       // add new key
$product['price'] = 119.99; // overwrite

if (array_key_exists('discounts', $product)) {
    // key exists
}

if (isset($product['vat'])) {
    // true if exists and not null
}

foreach ($product as $key => $value) {
    echo "$key: $value" . PHP_EOL;
}

$keys = array_keys($product);
$values = array_values($product);

asort($product); // sort by values, keep keys
ksort($product); // sort by keys
```

### Beware of numeric-looking string keys

```php
<?php
$a = [
    '01' => 'a',
    1    => 'b',
];

var_dump($a);
// Result: [1 => 'b'] because '01' was cast to int 1 and overwritten
```

Avoid string keys that look like numbers if format matters. Use prefixes (e.g., "k_01").

### Multidimensional arrays — complex data

```php
<?php
$orders = [
    [
        'id' => 1,
        'client' => ['name' => 'Aga', 'email' => 'aga@example.com'],
        'items' => [
            ['sku' => 'KLA-01', 'name' => 'Keyboard', 'price' => 129.99, 'qty' => 1],
            ['sku' => 'MYS-02', 'name' => 'Mouse', 'price' => 59.99, 'qty' => 2],
        ],
    ],
    [
        'id' => 2,
        'client' => ['name' => 'Tomek', 'email' => 'tomek@example.com'],
        'items' => [
            ['sku' => 'SLU-01', 'name' => 'Headphones', 'price' => 199.00, 'qty' => 1],
        ],
    ],
];

echo $orders[0]['client']['email']; // aga@example.com
echo $orders[0]['items'][1]['name']; // Mouse

foreach ($orders as $order) {
    $sum = 0.0;
    foreach ($order['items'] as $item) {
        $sum += $item['price'] * $item['qty'];
    }
    echo "Order #{$order['id']} total: " . number_format($sum, 2) . PHP_EOL;
}

$phone = $orders[0]['client']['phone'] ?? 'none';
```

### Useful array functions

```php
<?php
$numbers = [1, 2, 3, 4, 5, 6];

$even = array_filter($numbers, fn($n) => $n % 2 === 0);
$squares = array_map(fn($n) => $n * $n, $numbers);
$sum = array_reduce($numbers, fn($acc, $n) => $acc + $n, 0);

$a = [1, 2];
$b = [3, 4];
$merged = array_merge($a, $b); // [1,2,3,4]

$all = [0, ...$a, 99, ...$b];

$hasThree = in_array(3, $merged, true);
$indexThree = array_search(3, $merged, true);
```

### Removing elements and reindexing

```php
<?php
$arr = [10, 20, 30];
unset($arr[1]);
var_dump($arr); // [0 => 10, 2 => 30]
$arr = array_values($arr); // [10, 30]
```

Note: after `unset`, new `$arr[] = ...` uses the next max index, not filling the gap.

---

## Best Practices and Common Mistakes

### Best Practices

* Use modern `[]` syntax.
* Choose the right type:

    * **indexed** for lists,
    * **associative** for records/maps,
    * **multidimensional** for complex structures.
* Use clear keys (e.g., 'email', 'price').
* Use `foreach` for iteration.
* Check key existence:

    * `array_key_exists` detects null values.
    * `isset` returns false if value is null.
* Use strict comparisons in in_array/array_search.
* Reindex with array_values after deletions if order matters.
* Keep consistent structure for multidimensional arrays.
* Document types with PHPDoc.

### Common Mistakes

* Mixing key/value types unnecessarily.
* Using numeric-like string keys ("01") → cast to int.
* Assuming automatic reindexing after unset.
* Modifying array during foreach without caution.
* Misusing isset with null values.
* Calling count() repeatedly in loop conditions.
* Leaving reference active after foreach by reference.
* Assuming array_merge keeps numeric keys (it doesn’t).

---

## Summary

* Arrays in PHP = key → value collections with preserved order.
* **Indexed**: for lists.
* **Associative**: for records/maps.
* **Multidimensional**: for complex structures.
* You know basics: create, read, add, delete, iterate, use functions.
* Watch out for pitfalls: key conversion, unset gaps, isset vs array_key_exists.

---

## Mini Quiz

1. What does this print?

```php
$a = ['x', 'y'];
$a[5] = 'z';
echo count($a);
```

➡️ 3

2. Which is true?

* A: Associative arrays can have numeric keys.
* B: Indexed arrays can have string keys.
* C: In PHP, there’s no difference between associative and indexed — just usage.
  ➡️ C

3. Result?

```php
$a = ['01' => 'a'];
$a[1] = 'b';
var_dump($a);
```

➡️ \[1 => 'b']

4. How to check if key exists even if null?
   ➡️ array_key_exists

5. How to reindex after unset?
   ➡️ array_values

6. What prints?

```php
$u = [
  ['name' => 'Ada'],
  ['name' => 'Olek']
];
foreach ($u as $i => $p) {
    echo $i . ':' . $p['name'] . ' ';
}
```

➡️ 0\:Ada 1\:Olek

---

Now you have solid foundations for working with arrays in PHP. Practice creating lists, maps, and simple multidimensional structures. Each small project (to-do list, cart, address book) will strengthen your understanding and fluency.
