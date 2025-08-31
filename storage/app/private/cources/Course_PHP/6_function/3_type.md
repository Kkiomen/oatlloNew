---
title: "Function Typing in PHP: int, string, array, bool, mixed, void, object, ?int"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP Function Typing Tutorial: Complete Type System Guide"
seo_description: "Learn how to use function typing in PHP including int, string, array, bool, mixed, void, object, and nullable types. Master type safety with examples."
slug: php-function-typing-guide
---

Function typing in PHP is a way to clearly define what data types **parameters** expect and what **type is returned** by functions. This makes the code safer, easier to understand, and less error-prone. Since PHP 7 you can declare parameter and return types, and since PHP 8 you also have `mixed`, union types, and stricter type error handling.
Here, we focus on specific types: **int, string, array, bool, mixed, void, object, ?int (nullable types)**.

---

## Why is typing important in PHP?

* Improves **readability**: easier to understand what a function accepts and returns.
* Improves **safety**: invalid types cause clear `TypeError`s.
* Helps tools (IDE, static analysis) catch issues before runtime.
* Makes testing and maintaining code in large projects easier.

---

## Basics of Function Typing in PHP

### Where do we declare types?

* For parameters (type hints): `function foo(int $x) { ... }`
* For return values: `function foo(): int { ... }`

### Syntax

* Parameter type: `function name(Type $param) { ... }`
* Return type: `function name(...): Type { return ...; }`
* Nullable type: prefix with `?` → `?int` means “int or null”.
* Default values: can combine with types → `?int $id = null`.

### PHP version requirements

* Scalars (int, string, bool, float), arrays, and classes – since PHP 7.
* `void` – since PHP 7.1.
* `object` – since PHP 7.2.
* `mixed` – since PHP 8.0.

### Strict typing vs loose conversion

* By default, PHP tries to convert types (e.g., `"3"` to int).
* You can enable strict typing to prevent this:

```php
<?php
declare(strict_types=1); // enable strict types for the whole file
```

With strict mode, invalid types throw a `TypeError`.

---

## PHP Code Examples

### int – integers

```php
<?php
declare(strict_types=1);

function add(int $a, int $b): int {
    return $a + $b;
}

echo add(2, 3); // 5
```

### string – text

```php
<?php
declare(strict_types=1);

function greet(string $name): string {
    return "Hello, $name!";
}

echo greet("Alice");
```

### array – arrays

```php
<?php
declare(strict_types=1);

function sumArray(array $numbers): int {
    $sum = 0;
    foreach ($numbers as $n) {
        $sum += (int)$n;
    }
    return $sum;
}

echo sumArray([1, 2, 3]); // 6
```

### bool – booleans

```php
<?php
declare(strict_types=1);

function isAdult(int $age): bool {
    return $age >= 18;
}

var_dump(isAdult(20)); // true
var_dump(isAdult(16)); // false
```

### mixed – “can be many types”

```php
<?php
declare(strict_types=1);

function toInt(mixed $value): int {
    if (is_int($value)) return $value;
    if (is_numeric($value)) return (int)$value;
    throw new InvalidArgumentException("Cannot convert to int");
}

echo toInt("42");
```

Prefer union types (`int|string`) over `mixed` if you know the exact possibilities.

### void – function returns nothing

```php
<?php
declare(strict_types=1);

function logMessage(string $message): void {
    file_put_contents('app.log', $message . PHP_EOL, FILE_APPEND);
}

logMessage("Application started");
```

### object – any object

```php
<?php
declare(strict_types=1);

function getClassName(object $obj): string {
    return get_class($obj);
}

$dto = new stdClass();
$dto->title = "Entry";
echo getClassName($dto); // stdClass
```

### ?int – nullable type

```php
<?php
declare(strict_types=1);

function getUserNameById(?int $id): string {
    if ($id === null) return "guest";
    return "User #$id";
}

echo getUserNameById(null); // guest
echo getUserNameById(5);    // User #5
```

### Variadic and references with typing

```php
<?php
declare(strict_types=1);

function sumMany(int ...$numbers): int {
    return array_sum($numbers);
}

echo sumMany(1, 2, 3); // 6

function fillArray(array &$dest): void {
    $dest[] = "new";
}

$data = [];
fillArray($data);
print_r($data); // ["new"]
```

### TypeError on mismatch

```php
<?php
declare(strict_types=1);

function square(int $n): int { return $n * $n; }

echo square("3"); // TypeError in strict mode
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always enable strict typing: `declare(strict_types=1);`.
* Type all parameters and return values where possible.
* Prefer precise types:

    * class/interface > object
    * union types > mixed
* Use nullable `?Type` when null is valid.
* Ensure return type consistency across all paths.
* Void functions shouldn’t return values.
* Document array shapes in PHPDoc when needed.
* Throw exceptions instead of returning magic values (false).

### Common Mistakes

* Setting default `null` without `?` → `int $x = null` ❌
* Returning a value from void functions.
* Relying on implicit type conversions.
* Overusing `mixed` instead of unions.
* Using `object` instead of specific classes.
* Inconsistent return types (sometimes int, sometimes false).

---

## Summary

* Function typing defines parameter and return types.
* Supported types: int, string, array, bool, mixed, void, object, ?Type.
* Use strict typing for better safety.
* Prefer precise types over generic ones.
* Use `?Type` for nullable values.
* Keep return type consistent.

---

## Mini Quiz

1. What does `function f(?int $x): void` mean?
   ➡️ \$x may be int or null; function returns nothing.

2. With strict_types=1, what happens when calling `add("2", "3")`?
   ➡️ Throws `TypeError`.

3. Can a void function return a value?
   ➡️ No.

4. When to use mixed?
   ➡️ Only if multiple unknown types possible.

5. How to define an optional int parameter that may be null?
   ➡️ `function f(?int $x = null) {}`

6. What does object type mean?
   ➡️ Any instance of any class.

7. What will `isAdult(18)` return?
   ➡️ true.

8. What does `?int` as return type mean?
   ➡️ Function may return int or null.

---

Now you know how to use **function typing in PHP** to write safer, more predictable, and more professional code.
