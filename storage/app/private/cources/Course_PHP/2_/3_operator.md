---
title: "Operators in PHP — Arithmetic, Comparison, and Logic"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP Operators: Arithmetic, Comparison, and Logic Guide"
seo_description: "Master PHP operators with this comprehensive tutorial. Learn arithmetic, comparison, and logical operators with practical examples and best practices."
slug: operators-arithmetic-comparison-logic
---

## Introduction

Operators play a crucial role in PHP programming. Thanks to them, we can add numbers, check conditions, compare values, and control program flow. Arithmetic, comparison, and logical operators are the absolute basics of PHP — without them, it’s hard to write even a simple `if` statement or loop. In this lesson, you’ll learn:

* how addition, subtraction, division, modulo, and exponentiation work,
* how to combine conditions using logical operators,
* how to safely compare values and avoid pitfalls of PHP type juggling.

This is a practical lesson with plenty of PHP code examples, SEO-friendly hints, and beginner mistakes to avoid.

---

## Basics: What Is an Operator and Operand?

* **Operator**: a symbol or keyword that performs an operation (e.g., +, -, ==, &&).
* **Operand**: a value on which the operator acts (e.g., variables, numbers, strings).
* **Expression**: a combination of operands and operators that yields a result.

In this lesson, we focus on three groups:

* **Arithmetic operators** – for numeric operations.
* **Comparison operators** – return a boolean (true/false) depending on value relations.
* **Logical operators** – combine and negate boolean conditions.

---

## Arithmetic Operators in PHP

### Overview

* **+** addition
* **-** subtraction
* \*\*\*\*\* multiplication
* **/** division (usually returns float)
* **%** modulo (remainder of division; works on integers — PHP casts operands to int)
* \*\*\*\* exponentiation (e.g., 2 \*\* 3 = 8)
* **+x / -x** unary plus/minus
* **++ / --** increment and decrement (pre- and post-)

Additional functions:

* **intdiv(\$a, \$b)** – integer division (PHP 7+)
* **fdiv(\$a, \$b)** – floating-point division with safe handling of division by zero; returns INF/-INF/NaN instead of warnings (PHP 8+)

### Examples

#### Addition, subtraction, multiplication, division

```php
<?php
$a = 10;
$b = 3;

echo $a + $b; // 13
echo "\n";
echo $a - $b; // 7
echo "\n";
echo $a * $b; // 30
echo "\n";
echo $a / $b; // 3.3333333333 (float)
```

#### Modulo and its pitfalls

```php
<?php
echo 10 % 3;      // 1
echo "\n";
echo -10 % 3;     // -1 (sign depends on dividend)
echo "\n";
echo 5.7 % 2.1;   // 1, because PHP casts to int: 5 % 2
```

⚠️ Note: `%` works only on integers (operands cast to int). For fractions, use custom logic or math functions.

#### Exponentiation and precedence

```php
<?php
echo 2 ** 3;      // 8
echo "\n";
echo 2 ** 3 ** 2; // 512 (right-associative: 2 ** (3 ** 2))
echo "\n";
// Exponentiation has higher precedence than unary minus
echo -3 ** 2;     // -9 (interpreted as -(3 ** 2))
echo "\n";
echo (-3) ** 2;   // 9
```

#### Pre- and post-increment/decrement

```php
<?php
$x = 5;
echo ++$x; // 6 (pre: increments first, then returns)
echo "\n";
echo $x++; // 6 (post: returns first, then increments; now x = 7)
echo "\n";
echo $x;   // 7
```

#### intdiv and fdiv

```php
<?php
echo intdiv(10, 3); // 3

echo "\n";

// fdiv avoids division by zero warnings, returns INF/NaN (PHP 8+)
var_dump(fdiv(1.0, 0.0));  // float(INF)
var_dump(fdiv(-1.0, 0.0)); // float(-INF)
var_dump(fdiv(0.0, 0.0));  // float(NAN)
```

---

## Comparison Operators in PHP

### Common operators

* **==** equal (loose comparison with type juggling)
* **===** identical (equal and same type — strict comparison)
* **!=** or **<>** not equal (loose)
* **!==** not identical (strict)
* **<, <=, >, >=** relational
* **<=>** spaceship operator — returns -1, 0, or 1

### == vs ===

Loose comparisons convert types automatically. This often causes bugs.

```php
<?php
var_dump(5 == "5");    // true  ("5" -> 5)
var_dump(5 === "5");   // false (int vs string)

var_dump(0 == false);  // true
var_dump("" == false); // true
var_dump("0" == false);// true

// Edge case
echo var_dump("0e1234" == "0"); // true, both treated as 0 numerically
```

✅ Recommendation: prefer **===** and **!==** for predictability.

### Spaceship operator <=>

Returns:

* -1 if left < right,
* 0 if equal,
* 1 if left > right.

Example (sorting):

```php
<?php
$nums = [3, 1, 10, 2];

usort($nums, fn($a, $b) => $a <=> $b);

print_r($nums); // [1, 2, 3, 10]
```

### Strings vs numbers

* If one operand is numeric string (e.g., "42") and the other is a number, PHP converts to numeric comparison.
* To compare strings lexicographically, use `strcmp()` / `strcasecmp()`.

```php
<?php
var_dump("20" > "100");       // true (string comparison, '2' > '1')
var_dump("20" == 20);         // true (numeric comparison)
var_dump(strcmp("20", "100")); // > 0 (since '2' > '1')
```

---

## Logical Operators in PHP

### Overview

* **&&** and **and** — logical AND
* **||** and **or** — logical OR
* **!** — negation
* **xor** — exclusive OR (only one true)

⚠️ Note: **&&** / **||** have higher precedence than **and** / **or** — common source of confusion!

### Short-circuiting

* **&&**: if first operand is false, second is not evaluated.
* **||**: if first operand is true, second is not evaluated.

```php
<?php
function heavyTask() {
    echo "Called!\n";
    return true;
}

$val = false && heavyTask(); // heavyTask not called
var_dump($val); // false

$val2 = true || heavyTask(); // heavyTask not called
var_dump($val2); // true
```

### Precedence: && vs and

```php
<?php
$a = false;
$b = true;

$result1 = $a && $b; // false

$result2 = $a = true and false;
// parsed as: ($a = true) and false
// => $a = true, $result2 = false
var_dump($a, $result2); // true, false

// ✅ Prefer && and || with parentheses for clarity
```

### Examples

```php
<?php
$isLoggedIn = true;
$hasAccess = false;

if ($isLoggedIn && $hasAccess) {
    echo "Welcome!\n";
} else {
    echo "Access denied.\n";
}

$age = 20;
if ($age >= 18 || $isLoggedIn) {
    echo "You may continue.\n";
}

var_dump(!true);          // false
var_dump(true xor true);  // false
var_dump(true xor false); // true
```

---

## Combining Operators in Practice

```php
<?php
// User has access if logged in AND (is admin OR has user role)
$isLoggedIn  = true;
$isAdmin     = false;
$hasRoleUser = true;

if ($isLoggedIn && ($isAdmin || $hasRoleUser)) {
    echo "Access granted.\n";
} else {
    echo "Access denied.\n";
}

// Input validation
$input = "42";
if (ctype_digit($input) && (int)$input > 0) {
    echo "Valid positive integer: $input\n";
}
```

---

## Best Practices and Common Mistakes

### Best Practices

* Prefer **===** / **!==** for safe comparisons.
* Use parentheses for readability, especially when mixing logical operators.
* Be careful with operator precedence; prefer &&/|| instead of and/or.
* Avoid floats for money — use ints (cents) or **BCMath**.
* Check division by zero or use `fdiv()`.
* Format cleanly: spaces around operators, meaningful names, comments for tricky parts.

### Common Mistakes

* Using loose comparisons (`==`) and falling into traps like `"0" == false`.
* Assuming `%` works with floats (PHP casts to int).
* Forgetting parentheses with `and`/`or` vs assignment.
* Using floats for money → rounding errors.
* Confusing pre- and post-increment (`++$x` vs `$x++`).

---

## Summary

* Arithmetic operators: +, -, \*, /, %, \*\*, ++, --.
* Comparison operators: ==, ===, !=, !==, <, >, <=, >=, <=>.
* Logical operators: &&, ||, !, and, or, xor.
* Key rules: prefer strict comparisons, use parentheses, avoid floats for finance, beware operator precedence.

Mastering operators is essential for conditions, loops, and input validation in PHP.

---

## Mini Quiz

1. Result?

```php
<?php
echo 2 ** 3 ** 2;
```

➡️ 512 (2 \*\* (3 \*\* 2))

2. Output?

```php
<?php
$x = 5;
echo $x++ . " " . ++$x;
```

➡️ `5 7`

3. True/False?

```php
<?php
var_dump(0 == "0");      // true
var_dump(0 === "0");     // false
var_dump("10" > "2");    // false (string comparison: "1" < "2")
```

4. What’s the result?

```php
<?php
$a = false;
$b = true;
$result = $a = true and false;
var_dump($a, $result);
```

➡️ `$a = true`, `$result = false`

5. What does this return?

```php
<?php
var_dump("0e1234" == "0");
```

➡️ true

6. Which operator returns -1, 0, or 1?
   ➡️ `<=>`

7. What’s the result?

```php
<?php
echo intdiv(10, 3);
var_dump(fdiv(1.0, 0.0));
```

➡️ `3` and `float(INF)`

8. Safer comparison?

* a) if (\$a == \$b)
* b) if (\$a === \$b)
  ➡️ b)

---

Now you have solid foundations for working with operators in PHP. In the next lessons, we’ll apply them in conditionals, loops, and practical exercises.
