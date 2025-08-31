---
title: "Variable Scope in PHP: Global, Static, and Closures"
hash: null
last_verified: 2025-08-31 11:01
position: 5
seo_title: "PHP Variable Scope | Global, Static, and Closures"
seo_description: "Learn about variable scope in PHP: local, global, static, and closures. Includes examples, superglobals, best practices, common pitfalls, and a quiz."
slug: php-variable-scope-guide
---

Variable scope in PHP defines **where a variable is visible and accessible**. It affects:

* correctness (avoiding “Undefined variable” errors),
* readability and maintainability,
* security (preventing state leaks),
* testability (fewer side effects).

In practice, scope determines whether a variable is accessible:

* inside a function,
* in the whole file/script (global),
* in closures (anonymous functions),
* between multiple calls to a function (static).

---

## Basics: what is variable scope in PHP?

### Main types of scope

* **Local scope**: variables defined inside a function exist only there.
* **Global scope**: variables defined outside functions (at file level). Not visible in functions unless explicitly imported.
* **Superglobals**: special arrays (`$_GET`, `$_POST`, `$_SERVER`, `$_SESSION`, `$_COOKIE`, `$_FILES`, `$_ENV`, `$_REQUEST`, `$_GLOBALS`) available everywhere.
* **Static variable inside a function**: keeps its value across multiple calls of the same function.
* **Closure**: anonymous functions can “capture” variables from the surrounding context using `use`. Capture can be by value or by reference.

### What does *not* create a new scope

`if`, `for`, `foreach`, `while`, `switch` **do not create new scopes**. New scope is mainly created inside functions, methods, and classes.

### Quick comparison

* Inside a function you see only its parameters and local variables.
* To access a global variable inside a function, use **global** or **\$GLOBALS**.
* Static variables in functions remember values across calls.
* Closures import variables with `use ($x)` or `use (&$x)`.

---

## Global Variables in PHP

### Problem without `global`

```php
<?php
$counter = 10; // global variable

function increment() {
    // echo $counter; // Undefined variable
    // $counter++;   // Error – not visible in this scope
}

increment();
```

### Using `global`

```php
<?php
$counter = 10;

function increment() {
    global $counter; // import global variable into local scope
    $counter++;
    echo "Inside function: $counter\n"; // 11
}

increment();
echo "After call: $counter\n"; // 11
```

### \$GLOBALS superglobal

```php
<?php
$counter = 5;

function setTo(int $newValue): void {
    $GLOBALS['counter'] = $newValue; // access global variable
}

setTo(42);
echo $counter; // 42
```

### Superglobals like $_GET, $_POST

```php
<?php
function showId(): void {
    $id = $_GET['id'] ?? 'none';
    echo "ID = $id\n";
}
```

⚠️ Always validate and sanitize input from `$_GET`, `$_POST`, etc.

### Why avoid globals

* Hard to track data flow.
* Difficult to test.
* Conflicting changes from multiple places.
* Prefer passing values via parameters or dependency injection.

---

## Static Variables in Functions

Static variables inside a function are created only once and keep their values across calls.

### Counter example

```php
<?php
function callCount(): int {
    static $counter = 0; // initialized only once
    $counter++;
    return $counter;
}

echo callCount(); // 1
echo callCount(); // 2
echo callCount(); // 3
```

### Simple ID generator

```php
<?php
function nextId(string $prefix = 'ID'): string {
    static $seq = 0;
    $seq++;
    return $prefix . '-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
}

echo nextId();      // ID-001
echo nextId('USR'); // USR-002
```

### Initialization limitations

* Static variables must be initialized with **constant values**.
* Function calls and dynamic expressions are not allowed.

### Static in classes (briefly)

* Class static properties/methods are a different mechanism.
* Shared at class level, not per function.
* Covered in OOP lessons.

---

## Closures and Scope

Anonymous functions (closures) can capture variables from the surrounding context.

### Capturing by value

```php
<?php
$tax = 0.23;

$gross = function (float $net) use ($tax): float {
    return $net * (1 + $tax);
};

echo $gross(100.0); // 123
```

### Capturing by reference

```php
<?php
$counter = 0;

$inc = function () use (&$counter): void {
    $counter++;
};

$inc();
$inc();
echo $counter; // 2
```

### Closures with global

```php
<?php
$mode = 'dev';

$logger = function (string $msg) use ($mode) {
    echo "[$mode] $msg\n";
};

$changeMode = function (string $new) {
    global $mode;
    $mode = $new;
};

$logger('Start');  // [dev] Start
$changeMode('prod');
$logger('Start');  // still [dev] – copied by value
```

### By reference for live updates

```php
<?php
$mode = 'dev';

$logger = function (string $msg) use (&$mode) {
    echo "[$mode] $msg\n";
};

$logger('Start'); // [dev] Start
$mode = 'prod';
$logger('Start'); // [prod] Start
```

### Arrow functions

```php
<?php
$tax = 0.23;
$gross = fn(float $net): float => $net * (1 + $tax);
echo $gross(100.0); // 123
```

Arrow functions capture variables by value automatically. They cannot capture by reference.

### Loop pitfalls

```php
<?php
$callbacks = [];
for ($i = 0; $i < 3; $i++) {
    $callbacks[] = function () use (&$i) { echo $i . PHP_EOL; };
}
foreach ($callbacks as $c) { $c(); }
// Often prints: 3, 3, 3
```

Correct way:

```php
for ($i = 0; $i < 3; $i++) {
    $copy = $i;
    $callbacks[] = function () use ($copy) { echo $copy . PHP_EOL; };
}
// Prints: 0, 1, 2
```

---

## Practical Examples

### Global vs local

```php
<?php
$course = 'PHP';

function showCourse(): void {
    $local = 'JavaScript';
    echo "Local: $local\n";
}

showCourse();
echo "Global: $course\n"; // PHP
```

### Using global and \$GLOBALS

```php
<?php
$status = 'OK';

function breakStatus(): void {
    global $status;
    $status = 'ERROR';
}

function fixStatus(): void {
    $GLOBALS['status'] = 'OK';
}

breakStatus();
echo $status; // ERROR
fixStatus();
echo $status; // OK
```

### Static cache inside a function

```php
<?php
function expensiveCalc(int $x): int {
    static $cache = [];
    if (isset($cache[$x])) {
        return $cache[$x];
    }
    $result = $x * $x; // pretend expensive
    $cache[$x] = $result;
    return $result;
}

echo expensiveCalc(10); // 100 (calculated)
echo expensiveCalc(10); // 100 (cached)
```

### Closure as a filter

```php
<?php
function greaterThan(int $threshold): callable {
    return function (int $x) use ($threshold): bool {
        return $x > $threshold;
    };
}

$filter = greaterThan(5);
print_r(array_filter([1, 5, 7, 10], $filter));
```

### Closure as a counter

```php
<?php
function makeCounter(): callable {
    $count = 0;
    return function () use (&$count): int {
        return ++$count;
    };
}

$c = makeCounter();
echo $c(); // 1
echo $c(); // 2
```

### Arrow function in sorting

```php
<?php
$tax = 0.23;
$values = [100, 50, 200];
$gross = array_map(fn($n) => $n * (1 + $tax), $values);
print_r($gross); // [123, 61.5, 246]
```

---

## Best Practices and Common Mistakes

### Best Practices

* Pass data to functions via parameters instead of globals.
* Use static sparingly (counters, caches).
* Closures: prefer by-value capture; use references only when necessary.
* In loops, create copies when closures should capture iteration values.
* Always validate superglobals.
* Use meaningful variable names.
* Keep functions pure (no side effects) where possible.

### Common Mistakes

* Expecting a global to be visible inside functions without `global` or `$GLOBALS`.
* Using references in closures without understanding consequences.
* Capturing loop variables by reference (`3,3,3` issue).
* Overusing static and globals, making testing harder.
* Invalid static initialization (function calls).
* Accidentally binding `$this` in closures when static closure intended.

---

## Summary

* Scope defines where a variable is visible.
* **Global**: outside functions, accessible with `global` or `$GLOBALS`.
* **Superglobals**: always available but must be sanitized.
* **Static variables**: persist across function calls.
* **Closures**: capture outer variables with `use`. Arrow functions auto-capture by value.
* Avoid global state; prefer parameters and pure functions.

---

## Mini Quiz

1. What does this print?

```php
<?php
$z = 5;
function f() { echo isset($z) ? 'yes' : 'no'; }
f();
```

➡️ no

2. How to access global `$x` inside a function?
   ➡️ `global $x;` or `$GLOBALS['x']`

3. What does `static $c = 0;` inside a function do?
   ➡️ Remembers value between calls.

4. How to capture a variable by reference in a closure?
   ➡️ `use (&$x)`

5. Arrow functions capture variables…?
   ➡️ Automatically by value.

6. What’s wrong with this?

```php
function g() {
    static $now = time();
}
```

➡️ Invalid – cannot use runtime expressions for static initialization.

7. Output?

```php
$mode = 'dev';
$log = function () use ($mode) { echo $mode; };
$mode = 'prod';
$log();
```

➡️ dev

8. Output?

```php
$fs = [];
for ($i = 0; $i < 3; $i++) {
    $copy = $i;
    $fs[] = function () use ($copy) { echo $copy; };
}
foreach ($fs as $f) $f();
```

➡️ 012

---

Now you know the rules of **variable scope in PHP**. Practice with globals, statics, and closures to write cleaner and safer code.
