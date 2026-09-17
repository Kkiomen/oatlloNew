---
title: "Function Arguments in PHP: Optional Parameters and Default Values"
slug: php-function-arguments-guide
seo_title: "PHP Optional Parameters, Defaults and Named Arguments"
seo_description: "Make a PHP parameter optional with a default value, skip optional arguments with named arguments, and avoid the null and ordering traps. Tested examples."
---

To make a **PHP parameter optional**, give it a **default value** in the function definition. If the caller leaves that argument out, PHP uses the default:

```php
<?php
function greet(string $name = 'Guest'): string {
    return "Hello, $name!";
}

echo greet();        // Hello, Guest!
echo greet('Alice'); // Hello, Alice!
```

That is the whole trick. `$name` has a default, so `greet()` works without arguments. A parameter **without** a default is required: calling the function without it throws an `ArgumentCountError` ("Too few arguments to function").

You already know how to [define functions and use the return statement](/course/php/function/php-functions-basics-guide). This lesson goes deeper into what goes inside the parentheses:

- the rules for **optional parameters** (order, allowed default values),
- what happens when you pass `null` to a parameter with a default,
- how to **skip an optional argument** in the middle with **named arguments** (PHP 8.0+),
- how to tell "argument omitted" from "argument passed as `null`".

---

## Parameters vs arguments: the two words

- A **parameter** is the variable in the function definition: `$name` in `function greet($name)`.
- An **argument** is the value you pass when calling: `'Alice'` in `greet('Alice')`.

So "optional parameter" and "optional argument" describe the same thing from two sides: the parameter has a default, so the argument may be left out.

---

## PHP optional parameters: the rules

### Required parameters go first, optional parameters last

PHP fills parameters **left to right**. If an optional parameter comes before a required one, you can never actually leave it out, because you still have to pass something in its position to reach the required parameter.

```php
<?php
function paginate(int $page, int $perPage = 20): string {
    return "Page $page, $perPage per page";
}

echo paginate(1);     // Page 1, 20 per page
echo paginate(2, 50); // Page 2, 50 per page
```

What happens if you get the order wrong?

```php
<?php
function paginateWrong(int $perPage = 20, int $page) {
    // ...
}
```

This is **not** a fatal error, which surprises many people. Since PHP 8.0 it is a **deprecation notice**: *"Optional parameter $perPage declared before required parameter $page is implicitly treated as a required parameter"*. The default is silently ignored and `$perPage` becomes required. The fix is simple: move required parameters to the front, or drop the useless default.

### What a default value can be

A default value must be a **constant expression**, meaning PHP can work it out without running your code. Allowed:

- numbers, strings, `true`/`false`, `null`,
- arrays: `[]`, `['role' => 'guest']`,
- constants (see [constants in PHP](/course/php/php-basics/constants-in-php)) and magic constants like `__DIR__`,
- simple operations on those: `60 * 60`, `'app_' . 'log'`,
- since **PHP 8.1**, objects created with `new` (you will meet objects in the chapter on object-oriented programming).

Not allowed: variables, function calls such as `time()` or `date('Y-m-d')`. PHP rejects them with *"Constant expression contains invalid operations"*.

```php
<?php
const DEFAULT_ROLE = 'guest';

function makeUser(
    string $role = DEFAULT_ROLE,
    array $tags = [],
    int $ttl = 60 * 60,
): array {
    return ['role' => $role, 'tags' => $tags, 'ttl' => $ttl];
}

print_r(makeUser());

// Invalid - a function call is not a constant expression:
// function bad(string $day = date('Y-m-d')) {}
```

**The workaround for "dynamic" defaults** is a `null` default plus a check inside the function:

```php
<?php
function logLine(string $message, ?string $day = null): string {
    $day = $day ?? date('Y-m-d');
    return "[$day] $message";
}

echo logLine('Deploy done');               // [today's date] Deploy done
echo logLine('Deploy done', '2026-01-01'); // [2026-01-01] Deploy done
```

### A default array is fresh on every call

If you come from Python, you may have learned that a mutable default (like a list) is shared between calls. **PHP does not do that.** `array $filters = []` gives you a new, empty array every time, so it is safe to modify it inside the function. Merge caller values over your defaults with the usual [operations on arrays](/course/php/array/basic-operations-arrays-php):

```php
<?php
function buildQuery(array $filters = []): string {
    $filters = array_replace(['status' => 'active'], $filters);
    return http_build_query($filters);
}

echo buildQuery();                   // status=active
echo buildQuery(['status' => 'all']); // status=all
```

---

## Default values and null: the most common trap

**Passing `null` does not trigger the default.** The default is used only when the argument is **left out**. With a typed parameter, `null` is simply a wrong value:

```php
<?php
function setColor(string $color = 'blue'): string {
    return $color;
}

echo setColor();     // blue
echo setColor(null); // TypeError: Argument #1 ($color) must be of type string, null given
```

This bites in real code when the value comes from somewhere that can be empty, for example `$_GET['color'] ?? null`. If `null` should mean "use the default", say so explicitly with a **nullable type** (`?string`) and a `null` default:

```php
<?php
function findUser(?int $id = null): string {
    if ($id === null) {
        return 'No ID provided - returning all users.';
    }
    return "Looking for user with ID: $id";
}

echo findUser();     // No ID provided - returning all users.
echo findUser(null); // No ID provided - returning all users.
echo findUser(10);   // Looking for user with ID: 10
```

**Write `?int $id = null`, not `int $id = null`.** The second form used to make the type nullable implicitly. It still runs, but since **PHP 8.4** it emits a deprecation: *"Implicitly marking parameter $id as nullable is deprecated"*. You will find it all over older tutorials and codebases, so it is worth recognising.

---

## How to skip an optional argument: PHP named arguments

Say a function has two optional parameters and you only want to change the **second** one:

```php
<?php
function mailer(string $to, string $subject = 'Hi', bool $isHtml = false): string {
    return "To: $to | Subject: $subject | HTML: " . ($isHtml ? 'yes' : 'no');
}
```

With positional arguments, you cannot jump over `$subject`. Before PHP 8 the only option was to repeat the default by hand:

```php
echo mailer('user@example.com', 'Hi', true); // you must copy 'Hi' yourself
```

If the default ever changes, every such call keeps the old value. Since **PHP 8.0**, **named arguments** solve this: you pass a value by parameter name, and everything you skip keeps its default.

```php
echo mailer('user@example.com', isHtml: true);
// To: user@example.com | Subject: Hi | HTML: yes

echo mailer(isHtml: true, to: 'admin@example.com');
// Order of named arguments does not matter
```

Named arguments work with built-in functions too, which makes calls with a long list of optional parameters readable:

```php
echo str_pad('7', 3, pad_type: STR_PAD_LEFT, pad_string: '0'); // 007
```

### Named arguments rules (and the errors you will see)

- **Positional first, named after.** `mailer(to: 'a@b.com', 'Hello')` is a compile error: *"Cannot use positional argument after named argument"*.
- **Names must exist.** `mailer('a@b.com', title: 'x')` throws *"Unknown named parameter $title"*.
- **No value twice.** `mailer('a@b.com', to: 'b@c.com')` throws *"Named parameter $to overwrites previous argument"*.
- **Required is still required.** `mailer(subject: 'x')` throws `ArgumentCountError`: *"Argument #1 ($to) not passed"*.

One consequence worth knowing: once callers use named arguments, **renaming a parameter breaks their code**. Parameter names are now part of how a function is used, so pick them carefully.

---

## Variadic parameters: any number of optional arguments

A variadic parameter (`...$name`) collects all remaining arguments into an array. Passing none is fine, so it is optional by nature.

```php
<?php
function logMessage(string $message, string ...$tags): string {
    $list = $tags ? '[' . implode(', ', $tags) . ']' : '[no-tags]';
    return "$list $message";
}

echo logMessage('Start');               // [no-tags] Start
echo logMessage('Deploy', 'ci', 'prod'); // [ci, prod] Deploy
```

A variadic parameter must be the **last** one, and it cannot have a default value.

---

## Omitted argument vs null: telling them apart

With `?string $subject = null`, calling `sendEmail('a@b.com')` and `sendEmail('a@b.com', null)` look identical inside the function. If you really need to know the difference, `func_num_args()` returns how many arguments were actually passed:

```php
<?php
function sendEmail(string $to, ?string $subject = null): string {
    if (func_num_args() < 2) {
        $subject = 'No subject';
    }
    return "To: $to | Subject: " . ($subject ?? 'NULL');
}

echo sendEmail('a@b.com');          // Subject: No subject
echo sendEmail('a@b.com', null);    // Subject: NULL
echo sendEmail('a@b.com', 'Hello'); // Subject: Hello
```

In practice this is usually a sign the function is doing two jobs. A clearer design is often a default that is not `null` (`string $subject = 'No subject'`), so there is nothing to tell apart.

---

## Best practices for optional parameters

- Put required parameters first and optional ones last.
- Use `?type $x = null` when "no value" is a real, meaningful option; otherwise prefer a concrete default like `20` or `'guest'`.
- Keep defaults to the value most callers want. If most calls override it, it should not be the default.
- Watch out for **boolean flags**: `sendReport(false)` says nothing at the call site. `sendReport(includeDetails: false)` does.
- If a function grows past three or four optional parameters, callers start guessing. Pass an options array or split the function in two.
- Add `declare(strict_types=1);` at the top of your files; the next lesson on [function typing in PHP](/course/php/function/php-function-typing-guide) explains the whole type system.

---

## Summary

- A parameter becomes **optional** when it has a **default value**.
- **Required before optional**; the wrong order is deprecated since PHP 8.0 and makes the default useless.
- Defaults must be **constant expressions**; `new` is allowed since PHP 8.1, function calls never are.
- **Passing `null` does not use the default** - use `?type $x = null` if `null` should be accepted.
- **Named arguments** (PHP 8.0+) let you skip optional arguments in any position.
- `func_num_args()` distinguishes an omitted argument from an explicit `null`.

## FAQ

### How do you make a function parameter optional in PHP?

Give the parameter a default value, like `function greet(string $name = 'Guest')`. If the caller omits that argument, PHP uses the default. Put optional parameters after all required ones.

### How do I skip an optional parameter in PHP?

In PHP 8.0 and newer, use named arguments: `mailer('a@b.com', isHtml: true)` skips `$subject` and keeps its default. In older PHP versions you have to pass the default value for every skipped parameter yourself.

### Does passing null use the default value in PHP?

No. The default is only used when the argument is left out. Passing `null` to a `string` parameter throws a `TypeError`; declare the parameter as `?string $x = null` if `null` should be allowed.

### Can a required parameter come after an optional one in PHP?

It runs, but since PHP 8.0 it triggers a deprecation notice and the optional parameter is treated as required, so its default is never used. Always list required parameters first.

### Can a PHP default parameter value be a function call?

No. Defaults must be constant expressions, so `date('Y-m-d')` or `time()` cause a fatal error. Use `null` as the default and compute the value inside the function with `$day = $day ?? date('Y-m-d');`.
