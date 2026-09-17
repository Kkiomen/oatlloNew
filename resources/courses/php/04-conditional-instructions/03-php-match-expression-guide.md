---
title: "The match Expression in PHP 8+: A Modern Approach to Conditionals"
slug: php-match-expression-guide
seo_title: "PHP match Expression: Syntax, default and match(true)"
seo_description: "PHP match explained: syntax, multiple values per arm, default, match(true) for ranges, return match and the UnhandledMatchError - runnable PHP 8 examples."
---

The **PHP `match` expression** (added in **PHP 8.0**) compares one value against a list of options with strict `===` and **returns the result of the first option that matches**. Think of it as a shorter, stricter [`switch`](/course/php/conditional-instructions/php-switch-statement-guide) that gives you a value back:

```php
<?php
$status = 404;

$message = match ($status) {
    200 => 'OK',
    404 => 'Not Found',
    500 => 'Server Error',
    default => 'Unknown status',
};

echo $message; // Not Found
```

Three things to know before anything else:

- `match` is an **expression**, not a function and not a statement. It produces a value, so you assign it, `echo` it or `return` it.
- It compares with **`===`**, so the string `'404'` does **not** match the integer `404`.
- If nothing matches and there is no `default`, PHP throws an **`UnhandledMatchError`**.

Not to be confused with `preg_match()` - that is a function for regular expressions and has nothing to do with the `match` expression.

---

## PHP match syntax

```php
$result = match (subject) {
    value1 => result1,
    value2, value3 => result2,
    default => result3,
};
```

The rules, all in one place:

- The **subject** in the parentheses is evaluated once.
- Each **arm** has the form `condition => result`. Arms are separated by commas; a comma after the last arm is allowed.
- One arm can list **several values** separated by commas (they work like "or").
- Arms are checked **top to bottom** and the **first match wins**. Nothing falls through, so there is no `break`.
- The right side of `=>` must be a **single expression**, not a block of statements.
- `default` is optional, but a match can have **only one** `default` arm.
- The whole `match` ends with a **semicolon** after the closing `}`.

---

## PHP match examples

### Basic example: map a value to text

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

This is the job `match` does best: turn one value into another. The same thing with `if`/`elseif` would take seven conditions and seven assignments.

### Match multiple values in one arm

Separate the values with commas. Any of them triggers the arm:

```php
<?php
$day = 'sat';

$type = match ($day) {
    'mon', 'tue', 'wed', 'thu', 'fri' => 'weekday',
    'sat', 'sun' => 'weekend',
    default => 'unknown',
};

echo $type; // weekend
```

### The default arm

`default` catches every value that no other arm matched. Two limits that people run into:

- You can't write two `default` arms. PHP stops with `Fatal error: Match expressions may only contain one default arm`.
- You can't mix `default` with other values in one arm (`'x', default => ...` is a syntax error). Put it on its own line - usually the last one.

### What happens when nothing matches: UnhandledMatchError

`switch` without a matching `case` quietly does nothing. `match` refuses to guess:

```php
<?php
$day = 8;

$type = match ($day) {
    1, 2, 3, 4, 5 => 'weekday',
    6, 7 => 'weekend',
};
```

Output:

```text
Fatal error: Uncaught UnhandledMatchError: Unhandled match case 8
```

The message includes the value that slipped through (strings are shown in quotes, e.g. `Unhandled match case 'abc'`), which makes the bug easy to find. The fix is either to add the missing value or to add a `default` arm. You can also catch this error like any other exception - you'll meet `try`/`catch` later in the course.

### Strict comparison: '2' vs 2

`match` uses [strict comparison with `===`](/course/php/php-basics/operators-arithmetic-comparison-logic), so the type has to match as well as the value:

```php
<?php
$input = '2'; // a string, not an integer

$result = match ($input) {
    2 => 'number two',   // skipped: int vs string
    '2' => 'string two', // this one matches
    default => 'other',
};

echo $result; // string two
```

**The gotcha in real code:** everything that comes from a form, a URL or a file arrives as a **string**. If your arms are integers (`1 =>`, `2 =>`), nothing matches and you land in `default` or get an `UnhandledMatchError`. Convert the value first, for example `match ((int) $page) { ... }`.

### match(true): ranges and other conditions

`match` itself only checks `===`. To test conditions like "greater than", pass `true` as the subject - the first arm whose condition equals `true` wins:

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

Two things to watch here:

1. **Order matters.** If `$score >= 60` were the first arm, a score of 87 would get a `D`, because the first true condition wins.
2. **The condition must be exactly `true`, not just "truthy".** A comparison like `$score >= 90` or `str_contains($text, 'PHP')` returns a real `true`/`false`, so it works. A value like `1` or a non-empty string does not - `1 === true` is `false`. That is how `preg_match()` catches people: it returns `1`, not `true`, so the arm never matches. Cast it with `(bool) preg_match(...)`.

```php
<?php
$text = 'I am learning PHP';

$topic = match (true) {
    str_contains($text, 'PHP') => 'PHP',
    str_contains($text, 'JavaScript') => 'JavaScript',
    default => 'something else',
};

echo $topic; // PHP
```

### return match from a function

Because `match` is an expression, you can return it directly. You'll write your own functions in the [functions chapter](/course/php/function/php-functions-basics-guide); for now it's enough to see the shape:

```php
<?php
function shippingCost(string $country): int
{
    return match ($country) {
        'PL' => 10,
        'DE', 'FR' => 25,
        default => 50,
    };
}

echo shippingCost('DE'); // 25
```

### Only the matching arm runs

PHP checks the arms one by one and stops at the first match. The results of the other arms are **never evaluated**, so a function call on the right side of a non-matching arm doesn't run at all. That's why an arm can safely throw an error, e.g. `default => throw new InvalidArgumentException('Unknown role')` - it only fires when that arm is chosen.

---

## When not to use match

- **You need several statements per branch.** The right side of `=>` is one expression. If a branch has to set three variables and print something, use `if`/`elseif` or `switch`.
- **You rely on loose comparison on purpose.** Then `switch` (`==`) is the honest choice - but usually it's better to convert the type and keep `match`.
- **Your server runs PHP 7.** `match` doesn't exist there and the file fails with a parse error such as `syntax error, unexpected '=>'`. Also, since PHP 8.0 `match` is a reserved word, so old code with a function named `match()` breaks on upgrade.

### match vs switch in one table

| | `match` | `switch` |
|---|---|---|
| Comparison | strict `===` | loose `==` |
| Returns a value | yes | no |
| Needs `break` | no | yes, or it falls through |
| No matching case | `UnhandledMatchError` | nothing happens |
| Multiple statements per branch | no | yes |

`match` also works well with **enums** (PHP 8.1+). Enums are beyond this course, but there is a [complete guide to PHP enums](/php-enums-complete-guide) on the blog.

---

## Common mistakes with PHP match

- **Forgetting the semicolon** after the closing `}` - it's an expression, so it ends like one.
- **Comparing a string input with integer arms** - convert the type first.
- **Putting a broad condition first in `match(true)`** - later arms become unreachable.
- **Using a truthy value in `match(true)`** - cast to `bool`.
- **Leaving out `default` for values you don't control** - user input will eventually hit a value you didn't list.

---

## Summary

- `match` (PHP 8.0+) compares a value with `===` and returns the result of the first matching arm.
- Several values can share one arm: `'sat', 'sun' => 'weekend'`.
- `default` handles everything else; without it an unmatched value throws `UnhandledMatchError`.
- `match (true)` turns it into a compact chain of conditions - order the arms from most to least specific.
- Each arm is a single expression; for multi-step branches use `if` or `switch`.

In the next lesson you'll see how `exit` and `die` stop a script completely.

## FAQ

### Is match a function or a statement in PHP?

Neither - `match` is an **expression**. It evaluates to a value, which is why you can write `$x = match (...) { ... };` or `return match (...) { ... };`. It is also unrelated to `preg_match()`, which is a regular expression function.

### How do I match multiple values in a PHP match expression?

List them in one arm separated by commas: `'sat', 'sun' => 'weekend',`. The arm matches if the subject is identical (`===`) to any of the listed values.

### What happens if no arm matches in a PHP match?

If no arm matches and there is no `default`, PHP throws an `UnhandledMatchError` with a message like `Unhandled match case 8`. Add a `default` arm to handle every other value.

### Can a PHP match arm run multiple lines of code?

No. The right side of `=>` must be a single expression. For branches that need several statements, use `if`/`elseif` or `switch`, or put the logic in a function and call it from the arm.
