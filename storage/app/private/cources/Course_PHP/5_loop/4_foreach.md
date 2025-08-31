---
title: "The foreach Loop in PHP – Complete Lesson for Beginners"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "PHP foreach Loop Tutorial: Complete Guide for Beginners"
seo_description: "Learn how to use the foreach loop in PHP. Master array iteration, key-value pairs, references, and best practices with practical examples."
slug: php-foreach-loop-guide
---

The **foreach loop** in PHP is designed for convenient iteration over **arrays** and **collections** (objects that can be traversed step by step). It is the simplest way to clearly process data in PHP programming: product lists, users, orders, query results, configuration arrays, etc. Unlike `for`, `while`, or `do-while`, foreach is specifically built for collections — it’s shorter, safer, and more understandable, especially for beginners.

---

## Basics

### What is foreach?

The **foreach** loop sequentially goes through each element of an array or iterable object and provides:

* the value only, or
* the key-value pair.

This means you don’t have to manually count indexes or check array length.

### General syntax

* Values only:

```php
foreach ($collection as $value) {
    // use $value
}
```

* Key and value:

```php
foreach ($collection as $key => $value) {
    // use $key and $value
}
```

* Iteration “by reference” (modifying elements in place):

```php
foreach ($collection as &$value) {
    // modification changes the original element
}
unset($value); // important after using reference!
```

Foreach works on:

* arrays (indexed and associative),
* objects implementing Traversable (Iterator, IteratorAggregate),
* generators (`yield`).

It does **not** work on non-iterable types like strings or ints (without conversion).

---

## PHP Code Examples

### Iterating over a simple array

```php
<?php
$fruits = ['apple', 'banana', 'pear'];

foreach ($fruits as $fruit) {
    echo "Fruit: $fruit\n";
}
```

### Iterating over an associative array (key => value)

```php
<?php
$prices = [
    'bread' => 5.50,
    'milk'  => 3.20,
    'butter'=> 7.80,
];

foreach ($prices as $product => $price) {
    echo "Product: $product, price: " . number_format($price, 2) . " PLN\n";
}
```

### Modifying array elements in place (reference)

```php
<?php
$numbers = [1, 2, 3, 4];

foreach ($numbers as &$n) {
    $n *= 2;
}
unset($n); // important!

print_r($numbers); // [2, 4, 6, 8]
```

⚠️ Forgetting `unset($n)` may cause hard-to-find bugs.

### Foreach with destructuring (list/\[])

```php
<?php
$points = [
    [2, 3],
    [4, 1],
    [0, 5],
];

foreach ($points as [$x, $y]) {
    echo "Point: ($x, $y)\n";
}

$users = [
    ['id' => 1, 'name' => 'Ada'],
    ['id' => 2, 'name' => 'Bartek'],
];

foreach ($users as ['id' => $id, 'name' => $name]) {
    echo "User #$id: $name\n";
}
```

### Iterating over an object (public properties)

```php
<?php
$person = (object)[
    'name' => 'Jan',
    'age'  => 30,
];

foreach ($person as $property => $value) {
    echo "$property: $value\n";
}
```

### Iterating over collections (Traversable, generators)

```php
<?php
$collection = new ArrayObject(['a' => 10, 'b' => 20]);

foreach ($collection as $key => $value) {
    echo "$key = $value\n";
}

function evenUpTo(int $max): Generator {
    for ($i = 0; $i <= $max; $i++) {
        if ($i % 2 === 0) yield $i;
    }
}

foreach (evenUpTo(10) as $n) {
    echo "Even: $n\n";
}
```

### Safe iteration with possibly null data

```php
<?php
$maybeNull = null;

foreach ((array)$maybeNull as $el) {
    // won’t enter, no warnings
}

if (is_iterable($maybeNull)) {
    foreach ($maybeNull as $el) {}
} else {
    // handle non-iterable case
}
```

### Flow control: continue and break

```php
<?php
$products = [
    'bread' => 5.50,
    'milk'  => 3.20,
    'butter'=> 7.80,
];

foreach ($products as $name => $price) {
    if ($price > 7) {
        echo "Expensive: $name\n";
        continue;
    }

    if ($name === 'milk') {
        echo "Found milk, stopping.\n";
        break;
    }

    echo "OK: $name ($price)\n";
}
```

### Nested foreach (array of arrays)

```php
<?php
$orders = [
    ['id' => 1, 'items' => ['apple', 'banana']],
    ['id' => 2, 'items' => ['pear']],
];

foreach ($orders as $order) {
    echo "Order #{$order['id']}:\n";
    foreach ($order['items'] as $item) {
        echo "- $item\n";
    }
}
```

### Practical example: summing values

```php
<?php
$cart = [
    ['product' => 'pen', 'price' => 2.50, 'qty' => 3],
    ['product' => 'notebook', 'price' => 8.00, 'qty' => 2],
];

$total = 0.0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

echo "Total: " . number_format($total, 2) . " PLN\n";
```

### Practical example: generating HTML list

```php
<?php
$tasks = ['Clean windows', 'Write report', 'Call client'];

echo "<ul>\n";
foreach ($tasks as $task) {
    echo "  <li>" . htmlspecialchars($task, ENT_QUOTES, 'UTF-8') . "</li>\n";
}
echo "</ul>";
```

### Modifying array during foreach

```php
<?php
$numbers = [1, 2, 3];

foreach ($numbers as $n) {
    echo "$n\n";
    if ($n === 2) {
        $numbers[] = 4; // new element usually not visited in this loop
    }
}
```

⚠️ Foreach “plans” iteration at the start. Adding/removing elements usually doesn’t affect the current loop.

---

## Best Practices and Common Mistakes

### Best Practices

* Prefer **foreach** for arrays/collections over `for`/`while`.
* Use clear variable names: `$user`, `$product`, `$row`.
* Use key-value form if key is meaningful.
* Use references only when needed; always `unset($ref)` after.
* Don’t resize arrays during foreach.
* Use `is_iterable` or `(array)` for safety.
* For large data sets, prefer **generators (yield)**.
* Use destructuring for readability when structure is known.

### Common Mistakes

* Forgetting `unset($value)` after reference iteration.
* Iterating over non-iterable (e.g., string, int).
* Expecting added elements to be visited immediately.
* Trying reference on key (`foreach ($arr as &$k => $v)` → syntax error).
* Assuming pointer functions (next/prev) affect foreach (they don’t).
* Using `for` with arrays inefficiently (`count()` in condition repeatedly).

### foreach vs other loops

* `for` — best for numeric ranges and indexes.
* `while/do-while` — best for unknown iterations.
* `foreach` — best for arrays/collections; idiomatic in PHP.

---

## Summary

* **foreach** is the main PHP loop for arrays/collections.
* Syntax: `foreach ($arr as $value)` or `foreach ($arr as $key => $value)`.
* Use `&` for modifications, then unset reference.
* Works with Traversable objects and generators.
* Don’t resize arrays mid-loop.
* Use destructuring for clarity.

---

## Mini Quiz

1. Syntax for foreach with key and value?
   ➡️ `foreach ($arr as $key => $value) { ... }`

2. Why use `&` in foreach? What to do after?
   ➡️ Modify originals; unset(\$value) after.

3. foreach on null?
   ➡️ Warning; fix with `(array)$x` or `is_iterable($x)`.

4. Will added element be visited immediately?
   ➡️ Usually no.

5. How to destructure `[x,y]` arrays?
   ➡️ `foreach ($points as [$x, $y])`

6. What does foreach iterate on stdClass?
   ➡️ Public properties.

7. When use generators with foreach?
   ➡️ Large/streaming data.

8. Why is foreach better than for for arrays?
   ➡️ Clearer, avoids index/count issues.

---

Now you have solid foundations for the **foreach loop** in PHP. In your projects, choose foreach as the default tool for arrays and collections, applying the best practices above. You can revisit [for](php-for-loop-guide), [while](php-while-loop-guide), and [do-while](php-do-while-loop-guide) lessons to compare use cases.
