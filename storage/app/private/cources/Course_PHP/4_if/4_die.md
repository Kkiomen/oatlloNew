---
title: "Ending Scripts in PHP: exit and die"
hash: null
last_verified: 2025-08-31 11:01
position: 4
seo_title: "PHP exit and die | Beginner’s Guide"
seo_description: "Learn how to use exit and die in PHP. Differences from return and break, usage in web and CLI, passing messages and exit codes, best practices, and common pitfalls."
slug: php-exit-die-guide
---

In this lesson, you’ll learn about the **script termination instructions in PHP**: `exit` and `die`. You’ll understand when and how to use them in practice (both in web applications and CLI scripts), how to pass **messages** and **exit codes**, and how to follow **best practices**. You’ll also see how they differ from `return` and `break`, as well as common pitfalls to avoid.

---

## Basics: what do exit and die do?

### In short

* **`exit`** and **`die`** in PHP are *language constructs* (not functions) that **immediately terminate the entire script**.
* They are **synonyms** — they behave identically. Choice is a matter of style (`die` is often used for fatal errors, `exit` in general contexts).
* They can accept:

    * an **integer** — used as an **exit code** (important in CLI),
    * a **string** — which will be **output** before termination.

Examples of valid syntax:

* `exit;`
* `exit();`
* `exit(0);` — terminate with code 0 (success in CLI).
* `exit(1);` — terminate with error code 1.
* `exit("Error: no permission");`
* `die("Something went wrong");`
* `die;`

In PHP 8+, the argument must be either **int** or **string**. Other types cause a **TypeError**.

### What happens internally with exit/die?

* The script stops immediately — no further code runs.
* PHP still executes:

    * any **finally blocks** (in try/catch/finally),
    * **destructors** of objects,
    * functions registered with **`register_shutdown_function`**.
* If **output buffering** is enabled, its contents are flushed to the client (unless cleared).

### Web vs CLI

* In **web applications**, `exit` stops generating the HTTP response. If headers and content were already set, they will still be sent (depending on output buffering).
* In **CLI** scripts, the integer from `exit(int)` becomes the **process exit code** (0 = success, >0 = error). This is crucial in automation, CI/CD, cron jobs.

---

## PHP Code Examples

### 1) Basic usage with message

```php
<?php
die("Application stopped due to configuration error.");
// This code will never run
```

### 2) Using exit codes (CLI)

```php
<?php
$options = getopt("", ["file:"]);

if (empty($options['file'])) {
    fwrite(STDERR, "Usage: php script.php --file=path\n");
    exit(1); // signal error
}

// ... program logic ...
exit(0); // success
```

### 3) Safe redirect in a web app

```php
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php", true, 302);
    exit; // stop further execution
}
```

Best practice: always use `exit` after `header('Location')` to prevent accidental data leakage.

### 4) Returning API response and terminating

```php
<?php
header('Content-Type: application/json; charset=UTF-8');

echo json_encode(['status' => 'error', 'message' => 'Invalid input'], JSON_UNESCAPED_UNICODE);
exit; // no further code should run
```

### 5) Difference: exit vs return

```php
<?php
function calculate() {
    if (rand(0,1)) {
        return 123; // ends only this function
    }
    return 456;
}

$result = calculate();
echo $result;

if ($result < 200) {
    exit("Result too small"); // ends entire script
}
```

### 6) Difference: exit vs break/continue

```php
<?php
foreach ([1,2,3] as $i) {
    if ($i === 2) {
        break; // exits loop only
    }
}
echo "Script continues after break\n";

foreach ([1,2,3] as $i) {
    if ($i === 2) {
        exit("Stopped at i=2"); // ends entire script
    }
}
```

### 7) Cleanup with finally and shutdown functions

```php
<?php
register_shutdown_function(function () {
    error_log("Shutdown: cleaning up");
});

try {
    exit("Critical error. Exiting.\n");
} finally {
    error_log("Finally: closing resources");
}
```

### 8) Controlling output buffering

```php
<?php
ob_start();
echo "Buffered output.";

exit; // buffer is flushed by default

// If you want to discard buffer:
// ob_end_clean();
// exit;
```

### 9) Fatal error handler before exit

```php
<?php
function fatal(string $message, int $code = 1): void {
    error_log("[FATAL] " . $message);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
    } else {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Application error: " . $message;
    }
    exit($code);
}

if (!file_exists('config.php')) {
    fatal('Missing config file', 2);
}
```

### 10) exit inside include/require affects entire script

```php
<?php
// lib.php
function checkPermissionOrExit(bool $ok): void {
    if (!$ok) {
        exit("Access denied");
    }
}

// index.php
require __DIR__.'/lib.php';
checkPermissionOrExit(false);
// exit in lib.php terminates index.php too
```

---

## Best Practices and Common Mistakes

### Best Practices

* Use `exit`/`die` consciously, mainly:

    * in **CLI scripts** to return exit codes,
    * in **entry points** (controllers, front controllers) after sending a response or redirect,
    * in small admin/utility scripts.
* Always call `exit` after `header('Location')`.
* For APIs: set headers, send JSON, then `exit`.
* In CLI: write errors to **STDERR**, use **0** for success and >0 for errors.
* Use **constants** for exit codes (e.g., `const EXIT_SUCCESS = 0; const EXIT_FAILURE = 1;`).
* Register **shutdown functions** if cleanup is needed.
* Log critical errors before calling `exit`.

### What to Avoid

* Don’t overuse `exit`/`die` inside **domain logic** — use exceptions instead.
* Don’t confuse `exit` with `return` (function only) or `break` (loop only).
* Don’t use numeric `exit` values as HTTP codes — they are different. Use `http_response_code()`.
* Don’t show sensitive error details to end users. Log them securely instead.
* Don’t assume code after `exit` will run (except finally/shutdown functions).
* Don’t pass unsupported types to `exit` (PHP 8+ throws TypeError).

### Ending response vs continuing processing

Sometimes you want to **end HTTP response** but still continue processing (e.g., sending email in background). Options:

* `fastcgi_finish_request()` (if available).
* Queue/background jobs (RabbitMQ, CRON, workers).

Unlike `exit`, these let you respond early without killing the process.

---

## Summary

* **`exit`** and **`die`** are aliases that immediately terminate the script.
* Accept **int** (exit code) or **string** (message + termination).
* In **web**: use after headers/responses, especially after redirects.
* In **CLI**: use proper exit codes and write to STDERR.
* Remember: finally blocks, destructors, and shutdown functions still execute.
* Prefer exceptions for error handling in libraries; reserve exit for entry points.

---

## Mini Quiz

1. What’s the difference between `exit(1)` in CLI vs web?
2. What does `exit("Connection error")` do?
3. Which ends only a loop: exit, return, or break?
4. Why call `exit` after `header('Location: ...')`?
5. Does die differ from exit?
6. Does finally execute when exit is called?
7. How to set HTTP 404 and terminate?
8. How to log error to STDERR and exit with code 2 in CLI?

---

You now have solid foundations for using `exit` and `die` in PHP. In the next lesson, we’ll move on to **exceptions and try/catch** for more controlled error handling in larger applications.
