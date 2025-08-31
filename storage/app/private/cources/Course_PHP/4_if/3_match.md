---
title: "The match Expression in PHP 8+: A Modern Approach to Conditionals"
hash: null
last_verified: 2025-08-31 11:01
position: 3
seo_title: "PHP match Expression Tutorial: Modern Conditional Logic"
seo_description: "Learn how to use the match expression in PHP 8+. Master strict comparisons, value returns, and modern conditional logic with practical examples."
slug: php-match-expression-guide
---

The **match** expression, introduced in **PHP 8**, is a modern construct for matching values and returning results. Unlike `switch`, it is an **expression**, meaning that **match always returns a value** that can be assigned to a variable or returned from a function.

Why it matters:

* Cleaner code: shorter, more readable than `if/elseif` and `switch`.
* Safer: uses **strict comparison (===)** – no unexpected matches like in `switch`.
* No fall-through: you don’t need `break;`.
* Requires completeness: if not all cases are covered, PHP throws an error (unless you add `default`).

In practice, **match** is great for mapping values, formatting statuses, selecting labels, simple routers, and working with **enums** (PHP 8.1+).

---

## Basics: how match works

### Differences between match, switch, and if/elseif

* **match vs switch**

    * match uses **strict identity (===)**, switch defaults to **loose (==)**.
    * match is an **expression** (returns a value), switch is a **statement** (does not).
    * match has no fall-through — each arm ends automatically, no `break` needed.
    * match enforces full coverage (or `default`), otherwise `UnhandledMatchError` is thrown.
* **match vs if/elseif**

    * if/elseif is more flexible for complex conditions or ranges.
    * match is ideal for simple 1:1 matches (or multiple values to one result).
    * You can use the `match (true)` trick for ranges (see below).

### Basic syntax

* Each arm has the form: `condition => result,`
* You can group multiple values in one arm, separated by commas.
* The `default` arm is optional but recommended.

---

## PHP Code Examples

### Simple value-to-text mapping

```php
<?php
$day = 3;

$dayName = match ($day) {
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday',
    default => 'Invalid day number',
};

echo $dayName; // Wednesday
```

### Multiple values in one arm

```php
<?php
$status = 201;

$message = match ($status) {
    200, 201, 202 => 'Success or accepted',
    400, 404 => 'Client error',
    500, 502, 503 => 'Server error',
    default => 'Unknown status',
};

echo $message; // Success or accepted
```

### match as an expression: assignment and return

```php
<?php
function normalizeRole(string $role): string
{
    return match (strtolower($role)) {
        'admin' => 'admin',
        'owner', 'superuser' => 'admin',
        'editor' => 'editor',
        'viewer', 'reader' => 'viewer',
        default => 'guest',
    };
}

$userRole = normalizeRole('SuperUser'); // admin
```

### Ranges with match(true)

```php
<?php
$score = 87;

$grade = match (true) {
    $score >= 90 => 'A',
    $score >= 80 => 'B',
    $score >= 70 => 'C',
    $score >= 60 => 'D',
    default => 'F',
};

echo $grade; // B
```

### Incomplete matches and UnhandledMatchError

```php
<?php
$day = 8;

try {
    $type = match ($day) {
        1, 2, 3, 4, 5 => 'weekday',
        6, 7 => 'weekend',
        // missing default → UnhandledMatchError
    };
} catch (UnhandledMatchError $e) {
    echo "Match error: " . $e->getMessage();
}
```

### Throwing exceptions inside match arms

```php
<?php
function requireNonEmpty(?string $value): string
{
    return match (true) {
        $value === null, $value === '' => throw new InvalidArgumentException('Value cannot be empty'),
        default => $value,
    };
}

echo requireNonEmpty('ok'); // ok
```

### match with enums (PHP 8.1+)

```php
<?php
enum Status: string {
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

function statusLabel(Status $s): string
{
    return match ($s) {
        Status::Draft => 'Draft',
        Status::Published => 'Published',
        Status::Archived => 'Archived',
    };
}

echo statusLabel(Status::Published); // Published
```

### Strict equality: '5' vs 5

```php
<?php
$input = '2';

$result = match ($input) {
    2 => 'number two',   // no match (int vs string)
    '2' => 'string two', // this matches
    default => 'other',
};

echo $result; // string two
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use **match** for value mapping (1:1 or grouped values).
* Ensure **completeness**: add `default` or cover all values.
* Keep return types consistent (e.g., all strings).
* Use **match(true)** for simple range conditions.
* Combine with **enums** for maximum safety (forces full coverage).
* Keep arms simple; extract heavy logic into functions.
* Always end arms with commas.

### Common Mistakes

* Forgetting `default` or incomplete coverage → UnhandledMatchError.
* Expecting loose comparison (==) like switch → match uses strict ===.
* Mixing return types across arms → may cause TypeError or reduce clarity.
* Putting side effects inside arms → harder to test; better to extract logic.
* Duplicating values in multiple arms → later arms are unreachable.

---

## Summary

* The **match** expression in PHP 8+ is concise, strict, and safer than switch.
* It’s an **expression**: always returns a value.
* Uses **strict identity (===)**.
* No fall-through, no `break` needed.
* Enforces full coverage (or `default`).
* Ideal for mapping, enums, and validation.

---

## Mini Quiz

1. How does match differ from switch in comparisons? → match uses ===, switch uses ==.
2. What happens if no match and no default? → UnhandledMatchError.
3. How to match multiple values in one arm? → Separate with commas.
4. How to use match for ranges? → Use match(true) with boolean expressions.
5. Does match return a value? → Yes, can assign or return.
6. Why is match with enums powerful? → Forces completeness when enums expand.
7. What happens if arms return mixed types but function return type is string? → TypeError.
8. Do you need break in match? → No, there’s no fall-through.

---

Now you know how to use the **match expression** in PHP 8+ to write cleaner, safer, and more predictable conditionals.
