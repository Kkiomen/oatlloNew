---
title: "Basic Conditional Statements in PHP: if, else, elseif"
hash: null
last_verified: 2025-08-31 11:01
position: 1
seo_title: "Conditional Statements in PHP | Beginner’s Guide"
seo_description: "Learn the basics of conditional statements in PHP: if, else, and elseif. Understand truthy/falsy values, comparison operators, best practices, and common mistakes."
slug: conditional-statements-php-if-else-elseif
---

Conditional statements are the foundation of programming in PHP. They allow your script to make decisions: execute one part of code or another depending on conditions. Without conditionals, it would be difficult to write user logins, shopping cart logic, form validation, or personalized content.

* In this lesson, you’ll learn the basics: **if**, **else**, and **elseif**.
* You’ll see simple and practical PHP code examples.
* You’ll learn best practices and avoid common mistakes.

The previous lesson covered arrays and their functions — now we’ll show how to combine arrays with conditionals (e.g., checking if an array is not empty before doing something).

---

## Basics of Conditional Statements in PHP

### What is a condition?

A condition is an expression that evaluates to a **boolean**: **true** or **false**. Based on this result, PHP decides which block of code to execute.

* **if (condition)** — executes the block if the condition is true.
* **else** — executes the alternative block if the condition is false.
* **elseif (another_condition)** — adds more tests if the previous ones were not met.

### if syntax

```php
<?php
$isLoggedIn = true;

if ($isLoggedIn) {
    echo "Welcome back!";
}
```

### if...else syntax

```php
<?php
$hour = (int) date('H'); // e.g. 0..23

if ($hour < 12) {
    echo "Good morning!";
} else {
    echo "Good evening!";
}
```

### if...elseif...else syntax

PHP executes only the first block where the condition is true; the rest are skipped.

```php
<?php
$points = 78;

if ($points >= 90) {
    echo "Grade: A";
} elseif ($points >= 75) {
    echo "Grade: B";
} elseif ($points >= 60) {
    echo "Grade: C";
} else {
    echo "Grade: D";
}
```

### Important: truthy and falsy in PHP

In PHP, some values are treated as false, even if they’re not literally `false`:

* False: `false`, `0`, `0.0`, `""` (empty string), `"0"` (string zero!), `[]` (empty array), `null`
* True: everything else (e.g., `"abc"`, `1`, `-5`, `[1,2]`, object)

⚠️ Note: `"0"` is treated as false — a common source of confusion.

### Comparison and logical operators

* Comparisons: `==`, `===` (identical: type and value), `!=`, `!==`, `<`, `>`, `<=`, `>=`
* Logical: `&&` (and), `||` (or), `!` (not)
* There are also `and`, `or` with lower precedence — usually stick to `&&` and `||`.

---

## PHP Code Examples (with comments)

### 1) Simple condition: access only for adults

```php
<?php
$age = 19;

if ($age >= 18) {
    echo "You have access to this section.";
}
```

### 2) if...else: greeting depending on the time

```php
<?php
$hour = (int) date('H');

if ($hour < 12) {
    echo "Good morning!";
} else {
    echo "Good afternoon!";
}
```

### 3) if...elseif...else: discount thresholds

```php
<?php
$cartValue = 260;

if ($cartValue >= 500) {
    echo "15% discount";
} elseif ($cartValue >= 200) {
    echo "7% discount";
} elseif ($cartValue >= 100) {
    echo "3% discount";
} else {
    echo "No discount";
}
```

### 4) Nested conditions and combining operators

```php
<?php
$country = "PL";
$age = 20;
$hasConsent = true;

if ($country === "PL" && $age >= 18) {
    if ($hasConsent) {
        echo "You can participate in the contest.";
    } else {
        echo "We need your consent.";
    }
} else {
    echo "Criteria not met.";
}
```

### 5) Working with arrays (linking to previous lesson)

```php
<?php
$products = ["monitor", "mouse", "keyboard"];

// empty($products) returns true for an empty array
if (!empty($products)) {
    echo "We have " . count($products) . " products in stock.";
} else {
    echo "No products available.";
}

// Checking for a value in an array
if (in_array("mouse", $products, true)) { // true => strict comparison
    echo "Mouse is available.";
}
```

### 6) Safe key checking in associative arrays

```php
<?php
$user = [
    "login" => "ola",
    // "age" => 17 // commented: key may not exist
];

// isset checks if the key exists and is not null
if (isset($user["age"]) && $user["age"] >= 18) {
    echo "User is an adult.";
} else {
    echo "Age missing or user underage.";
}
```

### 7) Loose vs strict comparisons (== vs ===)

```php
<?php
$num = 0;
$text = "0";

if ($num == $text) {
    echo "== considers values equal (type conversion).";
}

if ($num === $text) {
    echo "This won’t print, because types differ (int vs string).";
}
```

### 8) Beware operator precedence: && vs and

```php
<?php
$result = false;
$a = true;
$b = false;

// && has higher precedence than =
$result = $a && $b; // ($a && $b) → false, then assignment
var_dump($result); // bool(false)

// and has lower precedence than =
$result = $a and $b; // ($result = $a) → true assigned, then true and $b → false
var_dump($result); // bool(true) — surprise!

// Use && and || and add parentheses for clarity
```

### 9) Using if in templates (alternative syntax)

Alternative syntax with `endif;` helps mixing PHP with HTML.

```php
<?php
$isLoggedIn = true;
$login = "ola";
?>

<div class="welcome">
    <?php if ($isLoggedIn): ?>
        <p>Welcome, <?= htmlspecialchars($login) ?>!</p>
    <?php elseif (!$isLoggedIn): ?>
        <p>Welcome, Guest!</p>
    <?php else: ?>
        <p>Unknown login state.</p>
    <?php endif; ?>
</div>
```

---

## Best Practices and Common Mistakes

### Best Practices

* Always use **curly braces { }**, even for single statements — improves readability and prevents errors.
* Prefer **strict comparisons (`===`, `!==`)** instead of `==` and `!=`, especially for form/API data.
* Simplify conditions — use well-named intermediate variables:

  ```php
  $isAdult = isset($user["age"]) && $user["age"] >= 18;
  if ($isAdult) { ... }
  ```
* Avoid deep nesting — use **guard clauses** (early returns):

  ```php
  if (!$isLoggedIn) {
      echo "Please log in";
      return;
  }
  // further code for logged-in users...
  ```
* Add **parentheses** in complex conditions for clarity.
* Stick to one convention: use **`elseif`** (recommended) instead of `else if`.
* Use **`isset()`** to check if keys exist; use **`empty()`** for “is empty” (be careful with "0").
* Escape output in HTML (e.g., `htmlspecialchars`) and validate inputs.

### Common Mistakes

* Using `=` instead of `==` or `===` in conditions:

  ```php
  if ($a = 5) { ... } // ERROR: assignment instead of comparison
  ```
* Missing braces leading to “dangling else” problems.
* Confusing precedence of `and`/`or` with `&&`/`||` — stick to `&&` and `||`.
* Misunderstanding truthy/falsy: e.g., `"0"` is false, so `if ("0") { ... }` won’t run.
* Checking values without confirming key existence:

  ```php
  if ($user["age"] > 18) { ... } // Notice if 'age' doesn’t exist
  ```

  Instead:

  ```php
  if (isset($user["age"]) && $user["age"] > 18) { ... }
  ```
* Comparing to null loosely (`==`) instead of strictly (`===` or `is_null()`).

---

## Summary

* Conditional statements **if, else, elseif** control program flow in PHP.
* Conditions rely on **true/false**; remember truthy/falsy rules.
* Use **strict comparisons (`===`, `!==`)**, braces, and parentheses for clarity and safety.
* Avoid pitfalls: `=` instead of `==`, precedence issues with `and`, missing `isset()`.
* Alternative syntax `if: ... elseif: ... else: ... endif;` is handy in templates.

---

## Mini Quiz — Test Yourself!

1. What will this print?

```php
<?php
$value = "0";
if ($value) {
    echo "A";
} else {
    echo "B";
}
```

➡️ B

2. Which syntax is recommended in PHP?
   ➡️ `elseif`

3. What’s the output?

```php
var_dump(0 == "0");
var_dump(0 === "0");
```

➡️ true, false

4. Safer way to check user’s age in `$u`:
   ➡️ `if (isset($u["age"]) && $u["age"] >= 18) { ... }`

5. Difference between `&&` and `and`?
   ➡️ Both are logical AND, but with different precedence.

6. What prints?

```php
$result = false;
$a = true;
$b = false;

$result = $a and $b;
var_dump($result);
```

➡️ bool(true)

7. In an `if ... elseif ... else` chain, which block executes?
   ➡️ Only the first block where the condition is true.

8. True or false: `empty([])` returns true.
   ➡️ True

9. What’s the result?

```php
if ("10" > 2) {
    echo "X";
} else {
    echo "Y";
}
```

➡️ X

10. Which comparison is safer for form data (string vs int)?
    ➡️ `===`

---

Now you know the basic conditional statements in PHP: if, else, and elseif. Practice creating conditions based on form data and arrays, and you’ll quickly feel confident programming in PHP.
