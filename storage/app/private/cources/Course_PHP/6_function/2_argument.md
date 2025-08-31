---
title: "Function Arguments in PHP: Optional Parameters and Default Values"
hash: null
last_verified: 2025-08-31 11:01
position: 2
seo_title: "PHP Function Arguments: Optional Parameters and Defaults"
seo_description: "Learn how to use function arguments in PHP including optional parameters, default values, typing, and named arguments. Master flexible function design."
slug: php-function-arguments-guide
---

In PHP programming, we often write functions that accept **arguments**. Sometimes not all parameters are required — and that’s a good thing! With **optional parameters** and **default values**, we can create flexible functions that are convenient to use and safe.

In this lesson you’ll learn:

* what optional parameters are in PHP,
* how to define default values,
* how to combine them with typing (int, string, ?int),
* how to use named arguments (PHP 8+),
* how to avoid common mistakes and write clean code.

This is a fundamental PHP skill used everywhere — from simple scripts to web applications.

---

## Basic Explanation

* An **optional parameter** is a parameter with a **default value**. If you call the function without providing that argument, PHP uses the default.
* A **required parameter** is one without a default — you must pass it.

Key rules:

* Required parameters must be defined before optional ones.
* Default value must be a constant expression (number, string, null, true/false, empty array, class constant, magic constant).
* If using types, the default must match the type.

---

## PHP Code Examples

### Simple function with an optional parameter

```php
<?php
declare(strict_types=1);

function greet(string $name = "Guest"): string {
    return "Hello, $name!";
}

echo greet();       // Hello, Guest!
echo greet("Alice"); // Hello, Alice!
```

### Order of parameters: required before optional

```php
<?php
declare(strict_types=1);

function paginate(int $page, int $perPage = 20): array {
    return [
        'page' => $page,
        'perPage' => $perPage,
    ];
}

print_r(paginate(1));      // perPage = 20 (default)
print_r(paginate(2, 50));  // perPage = 50
```

⚠️ In PHP 8+, defining a required parameter after an optional one causes a **fatal error**.

### Nullables and default values

```php
<?php
declare(strict_types=1);

function findUser(?int $id = null): string {
    if ($id === null) {
        return "No ID provided – return all users.";
    }
    return "Looking for user with ID: $id";
}

echo findUser();   // No ID provided – return all users.
echo findUser(10); // Looking for user with ID: 10
```

⚠️ Both omitting the argument and passing `null` result in `$id === null`.

### Allowed vs disallowed defaults

Allowed:

* numbers, strings, true/false, null
* arrays (`[]`, `['role' => 'guest']`)
* constants (`SOME_CONST`, `ClassName::CONST`)
* magic constants (`__DIR__`, `__FILE__`)

Disallowed:

* function calls (`time()`)
* new objects (`new DateTime()`)
* runtime expressions

```php
<?php
const DEFAULT_ROLE = 'guest';

function makeUser(string $role = DEFAULT_ROLE, array $tags = [], string $baseDir = __DIR__): array {
    return compact('role', 'tags', 'baseDir');
}

print_r(makeUser());

// Invalid examples:
// function bad(string $when = date('Y-m-d')) {}
// function bad2(DateTime $d = new DateTime()) {}
```

### Practical examples

#### Default booleans

```php
function sendReport(bool $includeDetails = true): string {
    return $includeDetails ? "Detailed report" : "Summary report";
}

echo sendReport();      // Detailed report
echo sendReport(false); // Summary report
```

#### Default array

```php
function buildQuery(array $filters = []): string {
    $defaults = ['status' => 'active'];
    $filters = array_replace($defaults, $filters);
    $parts = [];
    foreach ($filters as $k => $v) {
        $parts[] = $k . '=' . urlencode((string)$v);
    }
    return implode('&', $parts);
}

echo buildQuery();
```

⚠️ Unlike Python, PHP does not reuse default arrays across calls — safe to use.

#### Default path with **DIR**

```php
function loadConfig(string $dir = __DIR__): string {
    return "Loading config from: $dir/config.php";
}

echo loadConfig();
echo loadConfig('/etc/app');
```

### Named arguments (PHP 8+)

```php
function mailer(string $to, string $subject = 'Hi', string $body = '', bool $isHtml = false): void {
    echo "To: $to | Subject: $subject | HTML: " . ($isHtml ? 'yes' : 'no') . "\n";
}

mailer('user@example.com');

mailer(
    to: 'admin@example.com',
    isHtml: true
);

mailer(
    to: 'team@example.com',
    body: '<b>Hello</b>',
    isHtml: true
);
```

### Variadic parameters (…) and optional arguments

```php
function logMessage(string $message, string ...$tags): void {
    $tagsList = $tags ? '[' . implode(', ', $tags) . ']' : '[no-tags]';
    echo "$tagsList $message\n";
}

logMessage('Start');
logMessage('Deploy', 'ci', 'prod');
```

⚠️ No parameters can follow variadic ones.

### Detecting omitted vs null arguments

```php
function sendEmail(string $to, ?string $subject = null, ?string $body = null): void {
    $subjectProvided = func_num_args() >= 2;
    if (!$subjectProvided) {
        $subject = 'No subject';
    }
    echo "To: $to | Subject: " . ($subject ?? 'NULL') . " | Body: " . ($body ?? 'NULL') . "\n";
}

sendEmail('a@b.com');            // No subject
sendEmail('a@b.com', null);      // subject = NULL
sendEmail('a@b.com', 'Hello');   // subject = Hello
```

Alternative: redesign the API to avoid ambiguity.

---

## Best Practices and Common Mistakes

### Best Practices

* Use strict typing (`declare(strict_types=1)`).
* Put required params first, optional after.
* For nullable defaults, use `?type $x = null`.
* Pick defaults that make sense.
* Document null meaning in PHPDoc.
* Use named arguments (PHP 8+) for clarity.
* Use empty arrays as defaults for collections.
* For many optional params, prefer config objects (DTOs).

### Common Mistakes

* Required after optional → fatal error.
* Default not matching type (`int $x = null` invalid).
* Computed defaults (`time()`) not allowed.
* Confusing omitted vs null.
* Too many optional params → unreadable.
* Optional references (`&$x = ...`) confusing.

---

## Summary

* **Optional params** have defaults.
* **Order matters**: required before optional.
* **Types must match** defaults.
* **Named arguments** improve clarity.
* **Defaults must be constants**.
* To detect omitted vs null, use `func_num_args()` or redesign API.

---

## Mini Quiz

1. What happens if required param follows optional in PHP 8+?
   ➡️ Fatal error.

2. Correct nullable definition?
   ➡️ `function f(?int $x = null)`

3. Which default is valid?
   ➡️ `__DIR__`

4. How to check if 2nd arg passed?
   ➡️ `func_num_args() >= 2`

5. Output of greet() with default `"Guest"`?
   ➡️ Hello, Guest!

---

Now you know how to use **optional parameters and default values** in PHP to design safe and flexible functions.
