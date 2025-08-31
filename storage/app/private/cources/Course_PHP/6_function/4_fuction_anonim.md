---
title: "Anonymous Functions (Closures) in PHP"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "PHP Anonymous Functions and Closures Tutorial"
seo_description: "Learn how to use anonymous functions and closures in PHP. Master arrow functions, use keyword, callbacks, and functional programming with examples."
slug: php-anonymous-functions-guide
---

Anonymous functions, also known as **closures**, are functions without a name. They can be assigned to a variable, passed as an argument, or returned from another function. They are very useful in PHP programming, especially when:

* you need short, one-time functions (e.g., in `array_map`, `array_filter`, `usort`),
* you want to encapsulate logic together with context (capture values from the outer scope),
* you create factories, middleware, event handlers, routes in frameworks (Laravel, Slim),
* you apply a functional style and want more concise code.

Modern PHP (7.4+ and 8.x) also offers **arrow functions** and **first-class callable syntax**, which make working with callbacks easier.

---

## Basics: what is an anonymous function and a closure?

* **Anonymous function**: defined without a name, e.g., `function ($x) { return $x * 2; }`.
* **Closure**: an anonymous function that can *capture* variables from the surrounding scope using the `use` keyword.
* **Arrow function**: shorter syntax since PHP 7.4, e.g., `fn($x) => $x * 2`. Automatically captures variables from the surrounding scope (by value).

When to use them?

* When a function is to be used as an argument (`callable`) — e.g., in array functions.
* When you want to pass logic along with context (e.g., a filter threshold).
* When named functions feel “too heavy” for a simple operation.

---

## PHP Code Examples (with comments)

### 1) Basic anonymous function

```php
<?php
$double = function (int $x): int {
    return $x * 2;
};

echo $double(5); // 10
```

### 2) Passing closure as a callback

```php
<?php
$numbers = [1, 2, 3, 4, 5];
$squares = array_map(function (int $n): int {
    return $n * $n;
}, $numbers);

print_r($squares); // [1, 4, 9, 16, 25]
```

### 3) Capturing variables with use(...)

```php
<?php
$threshold = 10;

$filter = function (int $n) use ($threshold): bool {
    return $n > $threshold;
};

data = [5, 10, 11, 20];
$result = array_filter($data, $filter);
print_r($result); // [11, 20]
```

### 4) Capturing by reference

```php
<?php
$counter = 0;

$inc = function () use (&$counter): int {
    $counter++;
    return $counter;
};

echo $inc(); // 1
echo $inc(); // 2
echo $counter; // 2
```

### 5) Closures in loops

```php
<?php
$callbacks = [];
for ($i = 1; $i <= 3; $i++) {
    $callbacks[] = function () use ($i) { return $i; };
}

echo $callbacks[0](); // 1
echo $callbacks[1](); // 2
echo $callbacks[2](); // 3
```

⚠️ If using references (`use (&$i)`), all would return 4 (final loop value).

### 6) Arrow functions

```php
<?php
$numbers = [1, 2, 3, 4];
$multiplier = 3;
$times = array_map(fn (int $n): int => $n * $multiplier, $numbers);

print_r($times); // [3, 6, 9, 12]
```

* Always one-liners, automatically capture by value.

### 7) Closure vs callable

```php
<?php
function applyToArray(array $data, Closure $fn): array {
    return array_map($fn, $data);
}

function filterWith(array $data, callable $predicate): array {
    return array_filter($data, $predicate);
}

$result = applyToArray([1, 2, 3], function (int $x): int { return $x + 1; });
$onlyEven = filterWith([1, 2, 3, 4], fn (int $x): bool => $x % 2 === 0);
```

### 8) Sorting with usort

```php
<?php
$users = [
    ['name' => 'Anna', 'age' => 27],
    ['name' => 'Bartek', 'age' => 22],
    ['name' => 'Celina', 'age' => 27],
];

usort($users, function (array $a, array $b): int {
    return [$a['age'], $a['name']] <=> [$b['age'], $b['name']];
});

print_r($users);
```

### 9) Closures in object context

```php
<?php
class Cart {
    private array $items = [
        ['name' => 'Book', 'price' => 50],
        ['name' => 'Pen',  'price' => 5],
    ];

    public function filterByPrice(float $min): array {
        return array_filter($this->items, function (array $item) use ($min): bool {
            return $item['price'] >= $min;
        });
    }
}

$cart = new Cart();
print_r($cart->filterByPrice(10));
```

### 10) Static closures

```php
<?php
$double = static function (int $x): int {
    return $x * 2;
};

echo $double(4); // 8
```

### 11) First-class callable syntax (PHP 8.1+)

```php
<?php
$len = strlen(...);
echo $len('PHP'); // 3

$upper = strtoupper(...);
echo $upper('php'); // PHP

$obj = new class {
    public function exclaim(string $s): string { return strtoupper($s) . '!'; }
};
$exclaim = $obj->exclaim(...);
echo $exclaim('php'); // PHP!
```

### 12) Function factory

```php
<?php
function makeAdder(int $a): Closure {
    return function (int $b) use ($a): int {
        return $a + $b;
    };
}

$add10 = makeAdder(10);
echo $add10(5); // 15
```

### 13) Memoization with closure

```php
<?php
function memoize(callable $fn): Closure {
    $cache = [];
    return function ($arg) use ($fn, &$cache) {
        if (!array_key_exists($arg, $cache)) {
            $cache[$arg] = $fn($arg);
        }
        return $cache[$arg];
    };
}

$slowSquare = function (int $n): int {
    usleep(100000);
    return $n * $n;
};

$fastSquare = memoize($slowSquare);
echo $fastSquare(10); // calculated
echo $fastSquare(10); // from cache
```

---

## Best Practices and Common Mistakes

### Best Practices

* Keep closures short and single-purpose.
* Type parameters and return values.
* Use arrow functions for simple transforms/filters.
* Capture only necessary variables in `use(...)`.
* Consider static closures when `$this` is not needed.
* Accept `callable` in APIs instead of just `Closure`.
* Document closure signatures in PHPDoc.

### Common Mistakes

* Using `use (&$var)` accidentally — all closures share one state.
* Forgetting that external variables aren’t available without `use`.
* Trying to serialize closures (unsupported by default).
* Overusing `$this` inside closures.
* Creating overly complex closures instead of named functions.
* Confusing `Closure` vs `callable`.

---

## Summary

* **Anonymous functions (closures)** allow passing logic as data and capturing context.
* Use `use(...)` for external variables, references with caution.
* **Arrow functions** simplify short cases.
* **First-class callables** (PHP 8.1+) give clean closures from functions/methods.
* Type and document closures for clarity and maintainability.

---

## Mini Quiz

1. What does `use` do in anonymous functions?
   ➡️ Captures variables from the outer scope.

2. Difference between `callable` and `Closure`?
   ➡️ `Closure` is a specific object, `callable` is any callable (string, array, Closure).

3. Output?

```php
$threshold = 5;
$fn = function (int $x) use ($threshold): bool {
    return $x > $threshold;
};
$threshold = 100;
var_dump($fn(10));
```

➡️ bool(true) — value was captured when closure was created.

4. Which statement about arrow functions is true?
   ➡️ They auto-capture variables by value and are one-liners.

5. Fill in to sort descending:

```php
usort($data, function ($a, $b) {
    return $b <=> $a;
});
```

6. What happens with this code?

```php
$callbacks = [];
for ($i = 0; $i < 3; $i++) {
    $callbacks[] = function () use (&$i) { return $i; };
}
echo $callbacks[0](), $callbacks[1](), $callbacks[2]();
```

➡️ All print 3 (shared reference).

7. How to create a `Closure` from `trim` in PHP 8.1+?
   ➡️ `$trim = trim(...);`

8. One-line arrow function to return last character of string?
   ➡️ `$lastChar = fn(string $s): string => substr($s, -1);`

---

Now you know how to use **anonymous functions and closures** in PHP to write concise, flexible, and modern code.
