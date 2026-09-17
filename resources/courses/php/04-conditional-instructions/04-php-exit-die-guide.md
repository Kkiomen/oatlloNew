---
title: "Ending Scripts in PHP: exit and die"
slug: php-exit-die-guide
seo_title: "PHP exit and die: Stop a Script, Exit Codes, exit vs die"
seo_description: "PHP exit and die stop a script immediately. See exit vs die, exit codes (int vs string), the or die idiom, and what changed in PHP 8.4."
---

**`exit` stops a PHP script immediately, and `die` is exactly the same thing under another name.** Nothing after the call runs. You can call it with no argument, with a **string** (PHP prints it, then stops) or with an **integer** (PHP stops silently and uses the number as the exit code).

```php
<?php
$age = 15;

if ($age < 18) {
    exit("Access denied\n"); // prints the message and stops
}

echo "Welcome!\n"; // never runs when $age is 15
```

That is the whole idea. The rest of this lesson covers the details that trip people up: why `die(404)` prints nothing, what exit codes are for, whether `or die` is still a good idea, and what PHP 8.4 changed.

## exit vs die: is there any difference?

No. `die` is an **alias** of `exit` - the PHP manual lists them as equivalent, and they accept the same arguments and behave identically. Some tutorials claim that `die` is "for errors" or "can only print strings". That is a convention some teams follow, not a rule of the language.

```php
<?php
exit;           // stop, exit code 0
exit();         // the same
die;            // the same
die("Bye\n");   // print "Bye", then stop
exit(1);        // stop with exit code 1, print nothing
```

Pick one and use it consistently. Most modern codebases use `exit`, because its name says what it does.

## PHP exit with a string vs an integer

The type of the argument decides what happens, and this is the most common source of confusion ([data types](/course/php/php-basics/variables-and-data-types-in-php) matter here):

| Call | Printed | Exit code |
|---|---|---|
| `exit;` | nothing | 0 |
| `exit("Stopped");` | `Stopped` | 0 |
| `exit(3);` | nothing | 3 |
| `exit("3");` | `3` | 0 |

Two gotchas follow from this table:

- **`die(404)` does not print "404".** An integer is never printed - it becomes the exit code. If you wanted to show a message, pass a string.
- **`exit("3")` is not the same as `exit(3)`.** The quotes turn it into a string, so PHP prints `3` and still reports success (code 0).

## PHP exit codes: what exit(0) and exit(1) mean

When you run a script in the terminal (`php script.php`), PHP hands the exit code back to whatever started it: your shell, a cron job, a CI pipeline or a deploy script. The convention is simple:

- **`0` means success.** This is also what you get when the script just reaches the end.
- **Any other number means failure.** `1` is the usual "something went wrong"; you can use other numbers to tell different errors apart.

Use values from **0 to 254**. The PHP manual reserves **255** for PHP itself - it is the code you get when a script dies from a fatal error.

```php
<?php
const EXIT_OK = 0;
const EXIT_MISSING_CONFIG = 2;

if (!file_exists('config.php')) {
    echo "config.php not found\n";
    exit(EXIT_MISSING_CONFIG);
}

echo "Config loaded\n";
exit(EXIT_OK);
```

Named [constants](/course/php/php-basics/constants-in-php) make it obvious what each code means. To see the code after running the script:

```bash
php check-config.php
echo $?              # Linux, macOS, Git Bash: prints 2 if config.php is missing
```

In PowerShell use `echo $LASTEXITCODE`, and in the old Windows `cmd` use `echo %ERRORLEVEL%`.

In a browser the exit code does nothing visible - it is not an HTTP status code. `exit(404)` does **not** send a "404 Not Found" page; it just stops the script with an empty response. In the terminal it is even stranger: codes above 255 wrap around, so `exit(404)` shows up as **148**.

## The `or die` idiom and why it is rarely used today

In older PHP code you will see this pattern everywhere:

```php
<?php
file_exists('config.php') or die("Missing config file\n");

echo "Config found\n";
```

It works because of how `or` evaluates, which you saw in the lesson on [logical operators](/course/php/php-basics/operators-arithmetic-comparison-logic): if the left side is true, PHP never looks at the right side. If it is false, `die` runs.

There are three catches. First, `or` has **lower precedence than `=`**, the same trap you met with `and` / `or` in the operators lesson, so mixing this idiom with assignments is easy to get wrong. Second, `die` with a string exits with code **0**, so a failing command-line script reports success.

The biggest problem is what users see. In a web page, `or die("...")` cuts the page off in the middle and shows a raw message - often one that reveals details (file names, database errors) you do not want visitors to read. Today a plain `if` is clearer:

```php
<?php
if (!file_exists('config.php')) {
    echo "Missing config file\n";
    exit(1);
}
```

It is one line longer, reads top to bottom, and lets you choose the exit code. In bigger applications errors are usually handled with exceptions instead of stopping the script - a topic for later in your PHP journey.

## When to use exit in a real script

`exit` is a blunt tool: it ends **everything**. That makes it a good fit in a few specific places:

- **Command-line scripts** - stop early with a non-zero code when input is missing or a step fails, so cron or CI notices.
- **After a redirect** - `header('Location: /login')` only *asks* the browser to go elsewhere; the rest of your script keeps running on the server unless you stop it.

```php
<?php
$isLoggedIn = false;

if (!$isLoggedIn) {
    header('Location: /login'); // header() sends an HTTP header to the browser
    exit;                       // without this, the code below still runs
}

echo "Secret dashboard data";
```

Forgetting that `exit` after a redirect is a classic security bug: the browser moves to `/login`, but the server has already generated the "secret" part of the page, and anyone who ignores the redirect (for example a script using `curl`) can read it.

Also remember that `exit` stops the **whole file**, including any HTML written after `?>`. If you exit halfway through a template, the rest of the page is simply never sent.

## PHP exit in a loop: exit vs break

You already know `foreach` from the lesson on [iterating over arrays](/course/php/array/iterating-arrays-php-foreach-array-walk-array-chunk). If you call `exit` inside it, the loop does not just end - the entire script does:

```php
<?php
$files = ['a.txt', 'b.txt', 'broken.txt', 'c.txt'];

foreach ($files as $file) {
    if ($file === 'broken.txt') {
        exit("Stopped at $file\n");
    }
    echo "Processing $file\n";
}

echo "All done\n"; // never printed
```

Output:

```text
Processing a.txt
Processing b.txt
Stopped at broken.txt
```

If you only want to leave the loop and keep going with the code after it, you need `break` instead. Loops (`for`, `while`, `do-while`) and [break and continue](/course/php/loop/php-break-continue-guide) are covered in the next chapter, so for now just remember: `exit` never means "leave this loop" - it means "stop the program".

## exit vs return in PHP

People often search for this one, so a quick orientation. `return` ends a **function** and hands a value back to the code that called it; the script carries on. `exit` ends the **entire script**, no matter where it is called. You will write your own functions in the lesson on [functions and return](/course/php/function/php-functions-basics-guide). A good habit to take there: functions should usually `return`, and only the top-level script decides whether to `exit`.

## What changed for exit and die in PHP 8.4

Until PHP 8.4, `exit` and `die` were special *language constructs*. Since **PHP 8.4** they are real functions with the signature `exit(string|int $status = 0): never`. For everyday code nothing changes - `exit;` without parentheses still works. The differences show up with unusual arguments:

- **Wrong types throw an error.** `exit([1])` (an array) now fails with a `TypeError`. Before 8.4, anything that was not an integer was quietly converted to a string and printed.
- **`exit(true)` behaves differently.** Before 8.4 it printed `1` and exited with code 0. Now `true` is converted to the integer `1`, so it prints nothing and exits with code **1**.
- **`exit(null)` and floats** like `exit(1.5)` now produce deprecation warnings.

The fix is the same in every case: pass an integer or a string, nothing else. (One more detail for later: functions registered as shutdown functions and object destructors still run after `exit`. You will meet both once you get to functions and classes.)

## Summary

- `exit` and `die` are identical: they stop the script at once.
- A string argument is printed and the exit code is 0; an integer is not printed and becomes the exit code.
- Exit code 0 means success, 1-254 mean failure, 255 is reserved by PHP.
- Prefer `if (...) { exit(1); }` over `... or die(...)`.
- Always `exit` after a redirect header.
- Inside a loop, `exit` stops the whole script, not just the loop.
- Since PHP 8.4, pass only `int` or `string`.

## FAQ

### Is die the same as exit in PHP?

Yes. `die` is an alias of `exit`: same arguments, same behaviour. The choice is purely a matter of style, and most modern code uses `exit`.

### How do I return an exit code from a PHP script?

Call `exit` with an integer, for example `exit(1);`. Run the script with `php script.php` and check the code with `echo $?` (Linux, macOS, Git Bash) or `echo $LASTEXITCODE` (PowerShell). Use 0 for success and 1-254 for errors.

### Why does die("Error") return exit code 0?

Because a string argument is only printed - it does not set the exit code, which stays at 0. To print a message and signal failure, print first and then call `exit(1);`.

### Does exit stop only the loop or the whole script?

The whole script. Code after the loop, and even HTML after `?>`, is never run. To leave only the loop, use `break`, which you will learn in the chapter on loops.
