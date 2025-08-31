---
title: "Constants in PHP — Complete Beginner's Guide"
hash: null
last_verified: 2025-08-31 11:01
position: 2
seo_title: "PHP Constants Tutorial: How to Define and Use Constants"
seo_description: "Learn how to create and use constants in PHP. Complete guide covering const keyword, define() function, class constants, and best practices."
slug: constants-in-php
---

## Introduction

Constants in PHP are values that cannot be changed once defined. Unlike variables, constants do not start with a dollar sign and are available throughout the execution of the program. They are essential in PHP programming when you need to store configurations, version numbers, directory paths, application settings, or values that should remain immutable. Understanding constants is fundamental to writing high-quality PHP code: they make maintenance easier, minimize errors, and improve readability.


## Basics: What Is a Constant?

* A **constant** is a named value that cannot be overwritten or removed once created.
* **Difference from a variable**:

    * Variable: prefixed with `$`, can be modified.
    * Constant: no `$`, **immutable** after definition.
* **Scope**:

    * Global constants (defined at file or namespace level) are accessible throughout the script.
    * **Class constants** belong to a class and are accessed using the `::` operator.
* **Naming convention**: Usually UPPER_CASE with `_`, e.g., `APP_VERSION`, `BASE_PATH`.
* **Types of constants**:

    1. Global and namespaced constants (`const` keyword or `define()` function).
    2. **Class constants** (`public|protected|private const NAME = value;`).
    3. **Built-in** constants (e.g., `PHP_VERSION`, `PHP_OS`, `DIRECTORY_SEPARATOR`) and **magic** constants (e.g., `__FILE__`, `__DIR__`).

Note: This information applies to modern PHP 8+. Some behavior differs in older versions.

---

## How to Define Constants in PHP

### const — Preferred, Compiled at Load Time

* Syntax: `const NAME = CONSTANT_EXPRESSION;`
* Works at file level (global) and in classes/interfaces.
* Value must be known at compile time (numbers, strings, constant arrays, and operations on them).

### define() — Defined at Runtime

* Syntax: `define('NAME', $value);`
* Works at runtime (can be used inside conditions, functions, loops).
* Useful for dynamic constant names.
* In namespaces, by default creates a global constant (unless you specify the full name with backslashes).

### Class Constants

* Syntax: `public const NAME = value;`
* Since PHP 7.1, class constants can have visibility modifiers (`public/protected/private`).
* Since PHP 8.1, you can use `final const` to prevent overriding in child classes.

---

## PHP Code Examples

### 1) Basic Global Constants: const and define

```php
<?php
// Simple constant — const (preferred)
const APP_NAME = 'MyApplication';
const APP_VERSION = '1.0.0';

// define() — works at runtime
define('DEBUG_MODE', true);

// Using constants
echo APP_NAME . ' v' . APP_VERSION . PHP_EOL; // "MyApplication v1.0.0"
if (DEBUG_MODE) {
    echo "Debug mode enabled" . PHP_EOL;
}
```

### 2) Constants and Modification Attempt (Error)

```php
<?php
const LIMIT = 100;

// The following line causes a Fatal error:
// LIMIT = 200;

echo LIMIT; // 100
```

### 3) Constant Arrays and Constant Expressions

```php
<?php
// Array as a constant (PHP 5.6+)
const DEFAULT_ROLES = ['USER', 'MODERATOR', 'ADMIN'];

// Constant built from other constants
const BASE_DIR = __DIR__;
const PUBLIC_DIR = BASE_DIR . '/public';

print_r(DEFAULT_ROLES);
echo PUBLIC_DIR; // e.g., "/var/www/project/public"
```

Note: `const` cannot use function calls in its value (except built-in magic constants like `__DIR__`). `define()` can take the result of any function, since it works at runtime.

### 4) define() with Dynamic Name and Access via constant()

```php
<?php
$env = 'PROD';
define("API_URL_$env", 'https://api.example.com');

// Access by string name
$constName = "API_URL_$env";
echo constant($constName); // "https://api.example.com"

// Check if constant exists
if (defined($constName)) {
    echo "Defined $constName";
}
```

### 5) Constants in Namespaces

```php
<?php
namespace App\Config;

// Creates a constant in App\Config
const TIMEOUT = 30;

// WARNING: define() without full name creates a GLOBAL constant:
define('TIMEOUT_GLOBAL', 45);

// define() with full name (PHP 5.3+)
define('App\\Config\\RETRIES', 3);

echo \App\Config\TIMEOUT;    // 30
echo \TIMEOUT_GLOBAL;        // 45 (global)
echo \App\Config\RETRIES;    // 3
```

### 6) Class Constants (Scope and Inheritance)

```php
<?php
class HttpClient
{
    public const DEFAULT_TIMEOUT = 10;        // public
    protected const DEFAULT_HEADERS = ['Accept' => 'application/json'];

    // Since PHP 8.1: prevent overriding
    // final public const VERSION = '2.0';
}

class CustomHttpClient extends HttpClient
{
    // Redefining a constant with the same name is possible
    // (depending on version and final restriction), but usually bad practice:
    public const DEFAULT_TIMEOUT = 15;
}

echo HttpClient::DEFAULT_TIMEOUT;      // 10
echo CustomHttpClient::DEFAULT_TIMEOUT; // 15
```

### 7) Magic Constants — Useful in Practice

```php
<?php
echo __FILE__ . PHP_EOL;       // Full path to this file
echo __DIR__ . PHP_EOL;        // Current file directory
echo __LINE__ . PHP_EOL;       // Current line number
echo __FUNCTION__ . PHP_EOL;   // Function name (inside a function)
echo __CLASS__ . PHP_EOL;      // Class name (inside a class)
echo __METHOD__ . PHP_EOL;     // Class::method (inside a method)
echo __NAMESPACE__ . PHP_EOL;  // Current namespace
```

### 8) Practical Example: Config Paths and Logic

```php
<?php
// config.php
const ROOT_PATH   = __DIR__;
const STORAGE_DIR = ROOT_PATH . '/storage';
const LOGS_DIR    = STORAGE_DIR . '/logs';
const TMP_DIR     = STORAGE_DIR . '/tmp';

const APP_ENV     = 'production'; // 'local', 'staging', 'production'
const APP_DEBUG   = (APP_ENV !== 'production');

function logger(string $message): void {
    $file = LOGS_DIR . '/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    // In real code add file error handling
    file_put_contents($file, $line, FILE_APPEND);
}

if (APP_DEBUG) {
    logger('Application running in debug mode.');
}

echo "Logs: " . LOGS_DIR . PHP_EOL;
```

### 9) Typical Errors — Redefinition and Name Collisions

```php
<?php
const RATE = 1.23;

// Attempt to redefine:
if (!defined('RATE')) {
    // This block won’t execute, because const is resolved at compile time,
    // and defined('RATE') checks global constants defined via define().
    // For const in namespaces, check with the full name.
}

// define() will warn if constant already exists:
define('RATE', 2.0); // Warning: constant already defined
```

Safer approach:

```php
<?php
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
    // ... safe initialization, runs only once
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **const** whenever possible:

    * faster, checked at compile time,
    * clearer (especially in classes and namespaces).
* **UPPER_SNAKE_CASE naming**: `APP_NAME`, `DEFAULT_TIMEOUT`, `MAX_ITEMS`.
* Group constants logically:

    * in config files,
    * in proper namespaces (`namespace App\Config;`),
    * as class constants if related to a class.
* Use built-in PHP constants:

    * Paths/system: `DIRECTORY_SEPARATOR`, `PATH_SEPARATOR`, `PHP_EOL`.
    * Platform: `PHP_VERSION`, `PHP_OS_FAMILY`, `PHP_INT_MAX`.
    * Errors/reporting: `E_ALL`, `E_WARNING`, etc.
* Check existence before define() if code may run multiple times:

    * `if (!defined('NAME')) define('NAME', 'value');`.
* For environment-specific configs, consider:

    * `.env` file or environment variables,
    * then map them to constants only if truly needed.

### What to Avoid (Common Mistakes)

* Overusing global constants to pass data between modules. This makes testing harder and increases coupling. Instead, pass dependencies to functions/classes.
* Defining constants in multiple places with inconsistent naming — risks collisions and confusion.
* Relying on old, unsupported `define()` case-insensitive option. Since PHP 8+, constants are always **case-sensitive**. Stick to one naming convention.
* Using `const` in blocks where not allowed (e.g., inside functions for global constants) — `const` only works at file/namespace level and in classes.
* Trying to reassign or unset constants — not possible.

---

## Summary

* **Constants in PHP** are named values that cannot be changed after definition.
* Main declaration methods:

    * `const` — preferred, compile-time, works in namespaces and classes.
    * `define()` — runtime, allows dynamic names and conditional use.
* **Class constants** help organize configuration tied to a class; they can have visibility modifiers and be `final`.
* Use **built-in** and **magic** constants for more portable, readable code.
* Follow consistent **best practices**: UPPER_SNAKE_CASE, logical grouping, avoid global state, check `defined()` where needed.

---

## Mini Quiz — Test Yourself

1. True/False: Constants in PHP can be overwritten after definition.

* Answer: ...

2. Which syntax defines a constant inside a class?

* a) `$this->const NAME = 'x';`
* b) `const NAME = 'x';` inside the class
* c) `define('NAME', 'x');` inside the class
* d) `var const NAME = 'x';`

3. What does this code output?

```php
<?php
const A = 'Hi';
const B = A . '!';
echo B;
```

* a) Hi!
* b) B
* c) Compile error
* d) Nothing

4. Which statement about `define()` in namespaces is correct?

* a) Always creates a constant in the current namespace.
* b) Always creates a global constant unless full name with backslashes is given.
* c) Never works in namespaces.
* d) Creates a constant only in a class.

5. Which of the following values can be assigned to a constant via `const`?

* a) Result of `time()`
* b) Array `['A', 'B']`
* c) Object `new stdClass()`
* d) File handle

6. How to check if a constant named `APP_ENV` is defined?

* a) `isset(APP_ENV)`
* b) `defined('APP_ENV')`
* c) `exists('APP_ENV')`
* d) `constant_exists(APP_ENV)`

7. Which operator is used to access a class constant?

* a) `->`
* b) `::`
* c) `=>`
* d) `??`

8. What does `PHP_EOL` mean?

* a) PHP version
* b) Directory separator
* c) End-of-line character depending on OS
* d) Script directory path

9. True/False: `const` can be used inside a function to define a global constant.

* Answer: ...

10. Task: Write a one-liner using `constant()` to display the value of a constant stored in the variable `$name`.

Suggested solution (check yourself): `echo constant($name);`

Good luck! In the next lesson, we’ll use constants to organize configuration and errors, combining them with functions and control structures in PHP.
