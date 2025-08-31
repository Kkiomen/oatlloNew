---
title: "Iterating Over Arrays in PHP: foreach, array_walk, and array_chunk"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "Iterating Arrays in PHP: foreach, array_walk, array_chunk"
seo_description: "Learn how to iterate over arrays in PHP using foreach, array_walk, and array_chunk. Understand when to use them, see examples, best practices, and common mistakes."
slug: iterating-arrays-php-foreach-array-walk-array-chunk
---


Iterating over arrays is one of the most common tasks in PHP programming. Whether you’re working with indexed, associative, or multidimensional arrays, you need to know how to conveniently loop through elements, modify them, and process data. In this lesson, you’ll learn three key tools:

* the **foreach** loop,
* the **array_walk** function (and briefly **array_walk_recursive**),
* the **array_chunk** function.

You’ll understand when and how to use them, see PHP code examples, and learn best practices to avoid common mistakes.

---

## Basics: when to use foreach, array_walk, and array_chunk

### foreach: the simplest way to iterate

* **foreach** is designed for conveniently looping through array elements.
* You can easily access the value and key, and even modify elements using references.
* Ideal for most tasks: displaying, simple transformations, summation.

### array_walk: iteration with a callback function

* **array_walk** calls a given function for each array element.
* Thanks to the callback, you can encapsulate logic and reuse it easily.
* Can modify elements if you work with references.

### array_chunk: splitting an array into smaller pieces

* **array_chunk** doesn’t iterate directly but divides an array into chunks of specified size.
* Perfect for pagination, batch processing, or limiting memory usage.

---

## PHP Code Examples (Step by Step)

### foreach: basics

#### Iterating an indexed array

```php
<?php
$numbers = [10, 20, 30];

foreach ($numbers as $value) {
    echo "Number: $value\n";
}
```

#### Iterating an associative array (key => value)

```php
<?php
$user = [
    'name' => 'Alice',
    'age' => 25,
    'city' => 'Krakow',
];

foreach ($user as $key => $value) {
    echo "Key: $key, Value: $value\n";
}
```

#### Modifying elements by reference

```php
<?php
$products = ['bread', 'milk', 'eggs'];

foreach ($products as &$p) {
    $p = strtoupper($p);
}
unset($p); // Always unset the reference after foreach

print_r($products);
// ['BREAD', 'MILK', 'EGGS']
```

#### Breaking and skipping iterations

```php
<?php
$nums = [1, 2, 3, 4, 5];

foreach ($nums as $n) {
    if ($n === 3) {
        continue; // skip 3
    }
    if ($n > 4) {
        break; // stop at 5
    }
    echo $n . PHP_EOL;
}
// Output: 1, 2, 4
```

#### Iterating a multidimensional array

```php
<?php
$orders = [
    ['id' => 1, 'amount' => 99.99],
    ['id' => 2, 'amount' => 149.50],
];

foreach ($orders as $order) {
    echo "Order #{$order['id']} - amount: {$order['amount']}\n";
}
```

#### Destructuring in foreach (PHP 7.1+)

```php
<?php
$points = [
    [2, 3],
    [5, 7],
    [10, 1],
];

foreach ($points as [$x, $y]) {
    echo "Point X=$x, Y=$y\n";
}
```

---

### array_walk: iteration with callback

#### Basic example with anonymous function

```php
<?php
$names = ['alice', 'olivia', 'zenek'];

array_walk($names, function (&$value, $key) {
    $value = ucfirst($value);
});

print_r($names); // ['Alice', 'Olivia', 'Zenek']
```

#### Using named function or static method as callback

```php
<?php
function trimAndLowercase(&$v, $k): void {
    $v = strtolower(trim($v));
}

$data = ['  ALICE ', ' Olivia', "ZENEK  "];
array_walk($data, 'trimAndLowercase');
print_r($data); // ['alice', 'olivia', 'zenek']

class Normalizer {
    public static function normalize(&$v, $k): void {
        $v = preg_replace('/\s+/', ' ', trim($v));
    }
}

$texts = ["  lorem   ipsum ", "dolor    sit"];
array_walk($texts, [Normalizer::class, 'normalize']);
print_r($texts); // ["lorem ipsum", "dolor sit"]
```

#### array_walk with user parameter

```php
<?php
$amounts = [100, 200, 300];

array_walk($amounts, function (&$v, $k, $percent) {
    $v = $v * (1 + $percent);
}, 0.23);

print_r($amounts); // [123, 246, 369]
```

#### array_walk_recursive (nested arrays)

```php
<?php
$data = [
    'user' => ['name' => ' Alice ', 'email' => ' MAIL@EXAMPLE.COM '],
    'tags' => [' PHP ', ' Arrays '],
];

array_walk_recursive($data, function (&$v, $k) {
    if (is_string($v)) {
        $v = strtolower(trim($v));
    }
});

print_r($data);
// 'name' => 'alice', 'email' => 'mail@example.com', 'tags' => ['php','arrays']
```

⚠️ Note: unlike array_map, **array_walk** does not return a new array — it modifies the original in place.

---

### array_chunk: splitting arrays into parts

#### Basics

```php
<?php
$nums = [1,2,3,4,5,6,7];
$chunks = array_chunk($nums, 3);

print_r($chunks);
// [[1,2,3],[4,5,6],[7]]
```

#### Preserving keys

```php
<?php
$map = [10 => 'a', 20 => 'b', 30 => 'c', 40 => 'd'];
$chunks = array_chunk($map, 2, true);

print_r($chunks);
// [[10=>'a',20=>'b'], [30=>'c',40=>'d']]
```

#### Pagination example

```php
<?php
$results = range(1, 50);
$perPage = 10;
$pages = array_chunk($results, $perPage);

$pageNum = 2;
$currentPage = $pages[$pageNum - 1] ?? [];

foreach ($currentPage as $record) {
    echo "Record: $record\n";
}
```

#### Batch processing

```php
<?php
$rows = range(1, 1000);

foreach (array_chunk($rows, 100) as $batch) {
    foreach ($batch as $r) {
        // process
    }
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Prefer **foreach** for standard iteration.
* When modifying elements, use **foreach with reference** or **array_walk with reference**. Always unset references after foreach.
* Use **array_walk** for applying a single consistent operation across all elements.
* Use **array_chunk** for pagination or batch processing.
* Keep callbacks small and meaningful.
* Use descriptive variable names and comments.
* Ensure correct data types with type hints or strict types.

### Common Mistakes

* Forgetting `unset($v)` after `foreach ($arr as &$v)` — can cause unexpected behavior.
* Expecting **array_walk** to return a new array — it doesn’t.
* Modifying array structure during foreach — may cause unexpected results.
* Using **array_walk** when **array_map** is more appropriate.
* Forgetting to preserve keys in **array_chunk** when needed.
* Overusing callbacks for trivial logic — foreach is clearer.
* Nested loops on large arrays without optimization.

---

## Summary

* **foreach**: simplest, most common iteration structure; supports references.
* **array_walk**: applies a callback to each element, modifies in place.
* **array_chunk**: splits arrays into chunks; useful for pagination/batch processing.
* Follow best practices: clean up references, use correct tools, comment code, avoid modifying structure mid-loop.

---

## Mini Quiz

1. Output?

```php
$items = ['a','b','c'];
foreach ($items as &$v) {
    $v = strtoupper($v);
}
unset($v);
echo implode(',', $items);
```

➡️ `A,B,C`

2. Which is true about array_walk?

* A: Returns new array.
* B: Modifies in place via reference.
* C: Works only on indexed arrays.
  ➡️ B

3. What does third param `true` in array_chunk(\$arr,2,true) do?
   ➡️ Preserves original keys in chunks.

4. Which to use for batch processing 100 elements?
   ➡️ array_chunk

5. Common mistake with foreach by reference?
   ➡️ Not unsetting reference after loop.

---

Now you have solid foundations for iterating over arrays in PHP. Next, we’ll combine these with filtering and mapping data to show practical PHP programming patterns.
