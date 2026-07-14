---
name: "Fixing \"Class Not Found\" Autoload Errors in PHP"
slug: php-class-not-found-autoload
short_description: "Why PHP throws a class not found autoload error and how to fix it fast: PSR-4 mapping, dump-autoload, namespaces, and Laravel caches."
language: en
published_at: 2026-10-07 09:00:00
is_published: true
tags: [php, composer, autoloading, debugging]
---

If you write PHP for long enough, you will meet this line:

```
Fatal error: Uncaught Error: Class "App\Services\InvoiceMailer" not found
```

Nine times out of ten, a **php class not found autoload** error has nothing to do with the class being missing. The file is sitting right there on disk. The problem is that Composer's autoloader can't turn the class name into a file path it's allowed to load. Once you understand how that mapping works, the fix is usually a 30-second job instead of a 30-minute panic.

I've hit this error on fresh projects, on deploys that worked yesterday, and on a script I copied out of a Gist at midnight. The causes are boringly repetitive once you know where to look. Let me walk through how autoloading actually resolves a class, then hand you a checklist you can run top to bottom.

## What actually happens when PHP "can't find" a class

PHP does not scan your project for classes. When it hits `new InvoiceMailer()` or a static call and the class isn't already loaded, it fires every registered autoloader function in turn, passing them the fully-qualified class name (FQCN). If none of them load the class, you get the fatal error.

In a Composer project, the registered autoloader is Composer's. It reads the maps generated in `vendor/composer/` and tries to translate the FQCN into a filename.

The most common strategy is **PSR-4**. Here's the rule in one sentence: PSR-4 maps a namespace prefix to a base directory, then treats the rest of the namespace as folders and the class name as the filename plus `.php`.

So with this in `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

The class `App\Services\InvoiceMailer` resolves like this:

- Strip the mapped prefix `App\` → leaves `Services\InvoiceMailer`
- Replace the namespace separators with directory separators → `Services/InvoiceMailer`
- Prepend the base dir and append `.php` → `app/Services/InvoiceMailer.php`

If a file exists at exactly that path **and** declares exactly that namespace and class, it loads. If any part of that chain is off by one character, you get "class not found." That's the whole game.

## The usual suspects (and how to confirm each one)

### The namespace in the file doesn't match the path

This is the number-one cause I see. The file lives at `app/Services/InvoiceMailer.php` but the top of the file says:

```php
<?php

namespace App\Service; // singular — should be Services

class InvoiceMailer {}
```

PSR-4 will look for `App\Service\InvoiceMailer` in `app/Service/`, not find it, and give up. Open the file, check the `namespace` line against the folder path, character for character.

### A missing or wrong `use` statement

If the calling file is in a different namespace and you didn't import the class, PHP resolves the short name relative to the *current* namespace:

```php
<?php

namespace App\Http\Controllers;

// No `use` — PHP looks for App\Http\Controllers\InvoiceMailer
class ReportController
{
    public function send()
    {
        $mailer = new InvoiceMailer(); // not found here
    }
}
```

Add the import, and it resolves correctly:

```php
use App\Services\InvoiceMailer;
```

Alternatively, reference the FQCN directly with a leading backslash: `new \App\Services\InvoiceMailer()`. The leading backslash means "from the global namespace," which is what trips people up. Write `new App\Services\InvoiceMailer()` *without* the backslash inside `App\Http\Controllers` and it becomes `App\Http\Controllers\App\Services\InvoiceMailer`. That doubled prefix produces a genuinely confusing error message.

### Case mismatch or a filename that doesn't match the class

PSR-4 is case-sensitive on the filesystem level, and this is the classic "works on my machine" bug. Your Mac or Windows dev box has a case-insensitive filesystem, so `Invoicemailer.php` happily loads the class `InvoiceMailer`. You deploy to a Linux server, its filesystem is case-sensitive, and the same code throws immediately.

The fix is discipline: the filename must match the class name exactly, including case. `InvoiceMailer` lives in `InvoiceMailer.php`, never `invoicemailer.php` or `InvoiceMailer.PHP`.

### You forgot `composer dump-autoload`

Composer caches the class-to-file map. When you add a brand-new class, or rename one, or edit the `autoload` block in `composer.json`, the cached map is stale until you regenerate it:

```bash
composer dump-autoload
```

For production, generate an optimized classmap so lookups don't touch the filesystem at runtime:

```bash
composer dump-autoload --optimize
```

If you added a class and it "doesn't exist" even though the path and namespace look perfect, this is almost certainly it. I've lost real time to this exact thing more than once.

### The `autoload` mapping in composer.json is wrong

Maybe your source lives in `src/` but `composer.json` still maps `App\` to `app/`. Or someone renamed the root namespace and didn't update the map. Check that the prefix and directory in the `psr-4` block actually describe your project layout:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Note the trailing backslashes are escaped in JSON (`App\\`) and the prefix ends with a separator. After any change here, run `composer dump-autoload`.

### The class belongs to a package you never installed

If the missing class is something like `GuzzleHttp\Client` or `Ramsey\Uuid\Uuid`, it lives in a third-party package. "Not found" here means the package isn't in `vendor/`:

```bash
composer require guzzlehttp/guzzle
```

Check `composer.json` and `vendor/` before assuming your own code is broken. A fresh clone that skipped `composer install` will throw this for every vendor class.

### classmap vs psr-4 confusion

Some libraries, and plenty of legacy code, use `classmap` instead of PSR-4. A classmap doesn't derive the path from the namespace; it scans the listed files and directories once and records exactly where each class sits:

```json
{
    "autoload": {
        "classmap": ["database/seeders", "database/factories"]
    }
}
```

The gotcha: classmaps are built at dump time. Add a new class into a classmapped directory and it stays invisible until you run `composer dump-autoload` again. PSR-4, by contrast, can find new files on the fly in development because it computes the path from the name.

### Non-Composer scripts with no autoloader

If you're running a standalone script that isn't part of a Composer project, nothing registers an autoloader for you. Either pull in Composer's:

```php
require __DIR__ . '/vendor/autoload.php';
```

or, in tiny scripts, `require` the class file manually. No autoloader, no autoloading, and PHP won't guess.

## Laravel-specific: stale caches

Laravel adds its own layer of caching on top of Composer, and it can mask an otherwise-correct setup. If the class genuinely exists, the namespace is right, and `dump-autoload` didn't help, clear the framework caches:

```bash
composer dump-autoload
php artisan optimize:clear
```

`optimize:clear` wipes the config, route, event, and compiled caches in one shot. I hit this after moving a service provider between namespaces — the code was correct, but a cached `bootstrap/cache/services.php` still pointed at the old FQCN. One `optimize:clear` and it was gone.

If you're modernizing an older Laravel codebase while you're in here, it's a good moment to lean on newer language features too — our guide to [PHP enums](/blog/php-enums-complete-guide) and the write-up on [typed class constants in PHP 8.3](/blog/php-8-3-typed-class-constants) both pair well with a tidy, well-namespaced `app/` directory.

## Cause to fix checklist

Run this in order. Most cases resolve before you reach the bottom.

- **Symptom: brand-new class, path and namespace look correct.** Run `composer dump-autoload`. Stale map is the likeliest culprit.
- **Symptom: works locally, breaks on the Linux server.** Compare filename casing to the class name. Case-sensitive filesystem is exposing a mismatch.
- **Symptom: doubled namespace in the error (`App\Http\App\Services\...`).** You used a relative FQCN without a leading `\`, or you're missing a `use` import.
- **Symptom: the class is a vendor/library class.** Confirm the package is installed with `composer require` and that `composer install` actually ran.
- **Symptom: namespace declared in the file doesn't match its folder.** Fix the `namespace` line to mirror the PSR-4 directory path.
- **Symptom: you changed the `autoload` block.** Verify the prefix/directory pair, then `composer dump-autoload`.
- **Symptom: Laravel, everything above checks out.** Run `php artisan optimize:clear` to drop stale compiled caches.
- **Symptom: plain PHP script, no framework.** Make sure `require 'vendor/autoload.php'` runs before you touch the class.

## FAQ

### Does `composer dump-autoload` delete anything or touch my database?

No. It only regenerates the files in `vendor/composer/` that map class names to paths. It never touches your source, your `.env`, or your database. It's safe to run anytime, and it's the first thing to try when a class you just wrote "doesn't exist."

### Why does my code work on Windows or macOS but fail on the production Linux box?

Windows and macOS default to case-insensitive filesystems, so `Mailer.php` and `mailer.php` are the same file to them. Linux treats them as different files. PSR-4 asks for the exact class name as the filename, so any casing drift that your dev machine forgave will surface the moment you deploy to Linux.

### What's the difference between PSR-4 and classmap autoloading?

PSR-4 computes a file path from the namespace at runtime, so new files following the convention are found automatically in development. A classmap is a precomputed lookup table built during `composer dump-autoload`; it's fast and explicit but won't see new classes until you regenerate it. Most application code uses PSR-4; classmaps suit directories where files don't follow a namespace convention.

### The class exists and the namespace is right, so why is it still not found?

Two remaining suspects: a stale Composer map (run `composer dump-autoload`) or, in Laravel, a stale compiled cache (run `php artisan optimize:clear`). If both are clean, re-read the error message carefully. The FQCN it prints is exactly what the autoloader searched for, and it will reveal a typo or a doubled prefix you glossed over.

## Wrapping up

"Class not found" reads like PHP lost your file, but it's really the autoloader failing to translate a name into a path it's permitted to load. Fix it by working the translation backwards: read the FQCN in the error, check that a matching file exists at the PSR-4 path with matching casing, confirm the `namespace` and `use` statements line up, and regenerate the map with `composer dump-autoload`. On Laravel, add `php artisan optimize:clear` for stale caches. Keep the checklist above nearby, and this error stops being a mystery and becomes a 30-second diagnostic.