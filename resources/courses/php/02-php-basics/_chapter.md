---
title: "PHP - variables, data types, constants, and syntax basics"
slug: php-basics
description: "Learn the basics of PHP: variables, data types, constants, scopes, and concatenation. Also, find out when to use single quotes and when to use double quotes."
---

PHP – variables, data types, constants, and syntax basics

In this chapter, you will learn the fundamental elements of PHP, which are the foundation for creating dynamic web applications. We will cover **variables**, **data types**, **constants**, **variable scope**, and the difference between single quotes and double quotes. You will also learn how **concatenation** works in PHP.

## Variables in PHP

Variables in PHP start with the `$` sign. They can store different values and change their type during program execution.

```php
<?php
$name = "John";
$age = 25;
$isActive = true;
```

## Data types

- `string` – text
- `int` – integers
- `float` – floating-point numbers
- `bool` – boolean values (true/false)
- `array` – arrays
- `object` – objects
- `null` – no value

## Constants in PHP

Constants are values that cannot be changed during program execution. We create them using `define()` or `const`.

```php
<?php
define("SITE_NAME", "MyWebsite");
const VERSION = "1.0";
```

## Variable scope

Variables can have different scopes:

- **Global** – available throughout the script
- **Local** – available only inside a function
- **Static** – remember their value between function calls

```php
<?php
function counter() {
    static $i = 0;
    $i++;
    return $i;
}

echo counter(); // 1
echo counter(); // 2
```

## Single quotes vs. double quotes

In PHP, there is a difference between using single quotes (`' '`) and double quotes (`" "`):

- Single quotes: the text is interpreted literally.
- Double quotes: allow variable interpolation.

```php
<?php
$name = "John";
echo 'Hello $name'; // Hello $name
echo "Hello $name"; // Hello John
```

## Concatenation

Concatenation means joining strings using the `.` operator.

```php
<?php
$firstName = "John";
$lastName = "Smith";
echo $firstName . " " . $lastName; // John Smith
```

## Best practices

1. Name variables and constants descriptively (`$userAge`, `MAX_USERS`).
2. Use `const` instead of `define()` when defining constants in application code.
3. Avoid mixing single and double quotes unnecessarily.

FAQDo variables in PHP have a fixed type?No, PHP is a dynamically typed language, so a variable’s type can change during execution.When should I use single quotes and when double quotes?Use single quotes when you want to display text literally. Double quotes are better when you need variable interpolation.Can a constant in PHP be overwritten?No, constants in PHP have immutable values – once defined, they cannot be overwritten.
