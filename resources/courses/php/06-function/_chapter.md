---
title: "PHP - functions: definitions, parameters, types, and scope"
slug: function
description: "Learn functions in PHP: definitions, parameters and default values, argument and return types, variable scope, references, as well as anonymous and arrow functions."
---

Functions in PHP – definitions, parameters, types, and scope

Functions organize code, allow reuse, and make testing easier. In this chapter, you will learn how to define functions, pass parameters (including by reference), use argument and return type declarations, understand variable scope, and work with anonymous and arrow functions.

## Defining and calling functions

```php
<?php
function greet(string $name): string {
    return "Hello, $name!";
}

echo greet("John"); // Hello, John!
```

## Parameters: default, references, variadic

- **Default values** – set a default argument when not provided at the call.
- **By reference** – prefix parameters with `&` to modify the original variable.
- **Variadic** – use `...` to accept any number of arguments.

```php
<?php
function addPrefix(string $txt = "", string $prefix = "app:"): string {
    return $prefix . $txt;
}

function increment(int &$x): void { $x++; }

function sumAll(int ...$nums): int { return array_sum($nums); }

$val = 5; increment($val); // $val = 6
```

## Argument and return types

Since PHP 7+, you can (and should) type parameters and return values; since PHP 8, *union types* and the `mixed` type are available.

```php
<?php
declare(strict_types=1);

function parseId(int|string $id): int {
    return (int)$id;
}

function maybeUser(bool $exists): ?array {
    return $exists ? ["id" => 1, "name" => "John"] : null; // ?array means nullable
}
```

Useful annotations: `int`, `string`, `float`, `bool`, `array`, `callable`, `object`, `iterable`, `mixed`, union types `int|string`, nullable types `?string`.

## Variable scope

- **Local** – variable lives inside the function body.
- **Global** – available everywhere, but inside functions requires `global` or superglobals.
- **Static** – remembers its value between calls.

```php
<?php
$g = 1; // global

function counter(): int {
    static $i = 0; // retains state
    return ++$i;
}

function useGlobal(): int {
    global $g; // access global
    return $g + 1;
}
```

## Anonymous functions (Closures) and arrow functions

Anonymous functions can be assigned to variables and passed as `callable`. They can access external variables using `use`. Arrow functions (`fn`) are shorter and automatically capture variables by value.

```php
<?php
$rate = 1.23;
$vat = function (float $net) use ($rate): float {
    return $net * $rate;
};

echo $vat(100.0); // 123.0

$inc = fn(int $x): int => $x + 1;
echo $inc(5); // 6
```

## Higher-order functions: map, filter, reduce

```php
<?php
$nums = [1, 2, 3, 4, 5];
$even = array_filter($nums, fn($n) => $n % 2 === 0);
$squared = array_map(fn($n) => $n * $n, $nums);
$sum = array_reduce($nums, fn($c, $n) => $c + $n, 0);
```

## Named arguments and order (PHP 8)

```php
<?php
function box(string $text, int $padding = 2, string $char = "-") {}

box(text: "Hello", char: "*"); // you can skip padding using named arguments
```

## Best practices

1. Enable `declare(strict_types=1);` and always type parameters and return values.
2. A function should do one thing – short and clear (Single Responsibility).
3. Avoid side effects; return values instead of modifying global state.
4. Use descriptive names and document contracts (PHPDoc if needed).

FAQWhen should I use references `&`?Only when you need to modify the passed argument or optimize large structures. In most cases, pass by value.What’s the difference between a closure and an arrow function?An arrow function is a shorthand for simple expressions; closures are full anonymous functions, can have a block of code, and explicit `use`.Does typing slow down code?The overhead is minimal, while the gain in quality and error detection is huge. In practice, it’s worth typing everything.
