---
title: "Variables and Data Types in PHP – Complete Beginner's Guide"
hash: null
last_verified: 2025-08-31 11:01
position: 1
seo_title: "PHP Variables and Data Types: Complete Tutorial"
seo_description: "Master PHP variables and data types with this comprehensive guide. Learn about strings, integers, arrays, objects, and best practices for variable naming."
slug: variables-and-data-types-in-php
---

## Basics: What Is a Variable and Data Type in PHP?

### What Is a Variable?

* A **variable** is a named “container” in memory where you store data while a script is running.
* In PHP, variable names always start with **\$**: e.g., `$name`, `$number`, `$grossPrice`.
* PHP is a **dynamically typed** language — a variable can hold different types of data at runtime (though since PHP 7+ you can use type declarations for parameters, return values, and class properties, which is recommended).

### Variable Naming Rules

* Allowed: letters, digits, underscore, but the name cannot start with a digit.
* PHP is case-sensitive with variables: `$number` and `$Number` are different variables.
* Best practices: use descriptive names, in `camelCase`, e.g., `$netPrice`, `$totalPoints`.

### Assignment and References

* Standard assignment copies the value:

    * `$a = 10; $b = $a; $a = 20; // $b remains 10`
* Reference (less common): `$b =& $a;` makes both variables point to the same value.

---

## PHP Data Types (Overview)

### Scalar Types

* **int** (integer, whole numbers): 0, -3, 42
* **float** (double, floating-point numbers): 3.14, -0.001, 1e3
* **string** (sequence of characters): "PHP", 'Alice has a cat'
* **bool** (boolean): `true`, `false`

### Compound Types

* **array**: ordered map key ⇒ value; can be indexed or associative
* **object**: instance of a class
* **callable**: a function, method, or anonymous function (Closure)
* **iterable**: anything you can iterate over (array, Traversable)

### Special Types

* **null**: absence of value
* **resource**: handle to an external resource (e.g., file, DB connection)

### Additional Annotations/Types in Newer PHP (7.4/8.0+)

* **union types**: e.g., `int|string`
* **mixed**: any type
* **object**: any object (not the same as `stdClass`)
* **static**, **self**, **parent**: in OOP context
* **void** (no return value), **never** (function never returns — e.g., throws exception/stops execution)
* **nullable** types: `?int` means `int` or `null`
* **enum** (PHP 8.1+): enumeration type — safe alternative to “magic strings”

---

## Variable Scope in PHP

Scope defines where a variable is visible and accessible.

### Local Variable (inside a function)

* A variable declared inside a function is available only in that function.

### Global Variable

* A variable declared outside a function is not automatically visible inside it.
* To use it in a function, you need the **global** keyword or the **\$GLOBALS** array.

### Static Variable (inside a function)

* **static** makes a variable retain its value between function calls.

### Scope in Closures

* Anonymous functions can “import” a variable from outside using `use ($variable)` or pass by reference `use (&$variable)`.

### Superglobals

* Variables available everywhere: **$\_GET, $\_POST, $\_SERVER, $\_COOKIE, $\_SESSION, $\_FILES, $\_ENV, \$GLOBALS, $\_REQUEST**
* Note: always validate and filter input data (security!).

---

## Value Ranges, Limits, and Precision

* **int**: size depends on platform:

    * 64-bit typical: from -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807
    * 32-bit typical: from -2,147,483,648 to 2,147,483,647
    * Helper constants: **PHP\_INT\_SIZE**, **PHP\_INT\_MAX**, **PHP\_INT\_MIN**
* **float**: usually 64-bit IEEE 754 (double); very large range (\~1.8e308), but limited precision (\~14 significant digits).

    * Be careful with rounding errors in financial calculations — use integers (cents) or arbitrary precision libraries (e.g., ext-bcmath).

---

## Type Conversions and Comparisons in PHP

### Implicit Conversion (Type Juggling)

* PHP often “adjusts” the type on the fly (e.g., when adding a number to a string containing a number).
* For non-numeric strings in arithmetic context, PHP may issue a warning or error depending on version and context. Don’t rely on this — better to cast explicitly.

### Explicit Casting

* `(int) "123"  // 123`
* `(float) "1.5"  // 1.5`
* `(string) 123  // "123"`
* `(bool) 0  // false`, `(bool) 1 // true`

### Comparisons: == vs ===

* `==` (loose) converts types, can lead to surprises.
* `===` (strict) compares both type and value — preferred best practice.

---

## Strings: Practical Notes

* Quotes:

    * `"text $variable"` – interpolation works
    * `'text $variable'` – treated literally (no interpolation)
* New lines and multi-line:

    * Heredoc: `<<<TXT` (with interpolation)
    * Nowdoc: `<<<'TXT'` (without interpolation)
* Encoding: stick to **UTF-8**; for string operations use `mb_*` functions (e.g., `mb_strlen`, `mb_substr`).

---

## PHP Code Examples (with Comments)

### Basic Variables and Types

```php
<?php
declare(strict_types=1); // Enforce strict typing for parameters/returns

$number = 42;           // int
$pi = 3.14159;          // float
$name = "Alice";        // string
$active = true;         // bool

// Arrays:
numbers = [1, 2, 3];
$user = [               // associative array (key => value)
    'id' => 10,
    'name' => 'John',
    'admin' => false,
];

// Object:
$object = (object) ['x' => 10, 'y' => 20]; // cast array to stdClass

var_dump($number, $pi, $name, $active, $numbers, $user, $object);
```

### Scope: Local, Global, Static

```php
<?php
$course = "PHP"; // global variable

function showCourse(): void {
    // echo $course; // Notice: undefined in this scope
    global $course;   // import global variable into function scope
    echo "I am learning $course\n";
}
showCourse();

function counter(): int {
    static $i = 0; // retains value between calls
    $i++;
    return $i;
}
echo counter() . "\n"; // 1
echo counter() . "\n"; // 2
```

### Closures and use()

```php
<?php
$prefix = ">>> ";

$logger = function (string $msg) use ($prefix): void {
    echo $prefix . $msg . PHP_EOL; // using external variable
};

$logger("Start"); // >>> Start

// Modify by reference:
$count = 0;
$inc = function () use (&$count) {
    $count++;
};
$inc();
$inc();
echo $count; // 2
```

### Type Declarations: Parameters, Returns, Properties

```php
<?php
class Cart {
    public array $items = []; // typed property (PHP 7.4+)

    public function add(string $product, int $qty = 1): void {
        $this->items[] = ['product' => $product, 'qty' => $qty];
    }

    public function totalItems(): int {
        return count($this->items);
    }
}

$c = new Cart();
$c->add("Book", 2);
echo $c->totalItems(); // 1
```

### Union Types and Nullable Types

```php
<?php
function formatId(int|string|null $id): string {
    if ($id === null) {
        return "none";
    }
    return "ID: " . (string)$id;
}
echo formatId(10) . PHP_EOL;      // ID: 10
echo formatId("ABC") . PHP_EOL;   // ID: ABC
echo formatId(null) . PHP_EOL;    // none
```

### Conversions, Comparisons, and Pitfalls

```php
<?php
echo (int)"123";     // 123
echo (float)"1.2e2"; // 120

// Loose comparisons can be surprising:
var_dump(0 == "0");       // true
var_dump(0 == "");        // true (!)
var_dump("123" == 123);   // true

// Always prefer strict comparisons:
var_dump(0 === "0");      // false
var_dump("123" === 123);  // false
```

### Null Coalescing and Safe Defaults

```php
<?php
// Example with input data (e.g., from form):
age = $_GET['age'] ?? null;          // if 'age' missing, result is null
$age = $age !== null ? (int)$age : 0; // explicit casting
echo "Age: $age";

// Since PHP 7.4: ??= assignment
$config = [];
$config['debug'] ??= false; // set debug=false if not exists
```

### Resource (Files) – Remember to Close

```php
<?php
$fh = fopen(__DIR__ . "/data.txt", "w");
if ($fh === false) {
    throw new RuntimeException("Cannot open file");
}
fwrite($fh, "Line 1\n");
fclose($fh); // free resource!
```

### Type Checking at Runtime

```php
<?php
$val = 3.14;

if (is_float($val)) {
    echo "This is a float\n";
}

echo gettype($val); // double
```

### Platform Differences: int Range

```php
<?php
echo PHP_INT_SIZE . " bytes\n"; // 8 on 64-bit
echo PHP_INT_MAX . "\n";         // e.g., 9223372036854775807
```

---

## Best Practices and Common Mistakes

### Best Practices

* Enable `declare(strict_types=1);` at the start of source files (more predictable typing).
* Use **strict comparisons** `===` and `!==`.
* Add **types** to parameters, return values, and class properties (PHP 7.4/8+).
* Initialize variables before use; avoid “magic” values.
* Use **null coalescing** (`??`) and `isset()` instead of ignoring warnings.
* For money, use **int** (e.g., cents) or precision libraries (bcmath), avoid floats.
* Validate and filter data from **superglobals** (`$_GET`, `$_POST`) – e.g., `filter_input()`.
* Close **resources** (e.g., `fclose`) and use constructs that release resources on exceptions.
* Avoid **variable variables** (`$$name`) — they reduce readability and security.
* Stick to consistent naming and formatting style (PSR-12).

### Common Mistakes

* Loose comparisons (`==`) leading to incorrect conditions.
* Relying on implicit type conversion of strings to numbers.
* Using **global** unnecessarily — better pass values as parameters.
* Unclosed resources (file handles, connections).
* Mixing character encodings — consistently use **UTF-8** and `mb_*`.
* Assuming int range is “infinite” — check `PHP_INT_MAX` for large numbers.

---

## Summary

* A **variable** is a named container for data, and a **data type** defines what it holds (int, float, string, bool, array, object, null, resource).
* PHP is dynamically typed, but in modern code use **type declarations** and `strict_types`.
* Understand **scope**: local, global, static, closures — key to avoiding bugs.
* Be aware of **ranges** and precision limits (int/float).
* Avoid implicit conversions and loose comparisons; prefer explicit casting and `===`.

---

## Mini Quiz – Test Yourself!

1. What does `declare(strict_types=1);` mean and what does it affect?
2. What’s the difference between `==` and `===` in PHP?
3. How do you access a global variable `$x` inside a function?
4. What is a `nullable` type and how do you declare it for `int`?
5. What type holds a file handle?
6. How do `??` and `??=` work? Give a short example.
7. Why shouldn’t you use `float` for financial calculations?
8. What’s the difference between `"Text $x"` and `'Text $x'`?
9. What does the `static` keyword inside a function do?
10. How do you check the maximum integer value on your platform?

### Answers

1. Enforces strict type checking for parameters and return values; PHP won’t silently convert types in function/method calls. Does not apply to operators.
2. `==` compares loosely (with type conversion), `===` compares type and value strictly.
3. Use `global $x;` in the function or access via `$GLOBALS['x']`.
4. A type that allows `null`; declare as `?int` (means `int|null`).
5. `resource` (special resource type).
6. `??` returns the first non-null operand: `$age = $_GET['age'] ?? 0;`. `??=` assigns only if the variable is null: `$debug ??= false;`.
7. Floats have limited precision and rounding errors; use int (cents) or bcmath.
8. Double quotes interpolate variables (`$x` gets replaced); single quotes show them literally.
9. Variable retains value between subsequent function calls.
10. Use constant `PHP_INT_MAX` (and `PHP_INT_SIZE` for size).
