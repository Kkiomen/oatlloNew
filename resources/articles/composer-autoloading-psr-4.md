---
name: "Composer Autoloading and PSR-4 Explained"
slug: composer-autoloading-psr-4
short_description: "How Composer autoloading and PSR-4 actually work: the namespace-to-directory map, dump-autoload, classmap vs files, and production optimization."
language: en
published_at: 2027-02-08 09:00:00
is_published: true
tags: [composer, php, psr-4, autoloading]
---

Composer autoloading with PSR-4 is the reason you can write `use App\Services\InvoiceMailer;` at the top of a file and never think about where that class lives on disk. No `require`, no `include`, no manual bookkeeping. You add a file, you use the class, it just resolves.

Until it doesn't. And when it doesn't, the error is almost always the same flavor of `Class "App\Services\InvoiceMailer" not found`, which tells you nothing about *why*. I've lost more time to that message than I'd like to admit, usually because I misunderstood what Composer was actually doing under the hood.

So let's take the hood off. This is a practical walk through how the autoloader resolves a class name into a file, what PSR-4 requires from you, and the handful of commands and flags that decide whether it works in development, in tests, and in production.

## What the autoloader is doing when you write `use`

PHP has a hook called `spl_autoload_register`. When you reference a class that isn't loaded yet, PHP hands the fully-qualified class name (FQCN) to every registered autoloader in turn until one of them loads it.

Composer registers exactly one such callback. When you write this once, near the top of your entry point:

```php
require __DIR__ . '/vendor/autoload.php';
```

you're pulling in the generated file that wires Composer's autoloader into PHP. From that point on, referencing any class your project knows about triggers Composer's resolver. The `require` for `vendor/autoload.php` is the *only* manual include you should need in a Composer-managed project.

Under `vendor/composer/` you'll find the generated files that back this: `autoload_psr4.php`, `autoload_classmap.php`, `autoload_static.php`, and friends. You never edit those by hand. They're rebuilt from your `composer.json` every time you run install, update, or dump.

## How PSR-4 resolves a class into a file path

PSR-4 is a spec that maps a **namespace prefix** to a **base directory**. The mapping lives in the `autoload.psr-4` block of your `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "App\\Tests\\": "tests/"
        }
    }
}
```

Given a class name, the autoloader resolves it in a strict, mechanical way:

- **Match the longest prefix.** Take the FQCN, find the registered prefix that matches the front of it. Composer checks longer prefixes first, so `App\Tests\UserTest` matches `App\Tests\` rather than the shorter `App\`.
- **Strip the prefix.** Remove the matched namespace prefix from the FQCN, leaving the relative part.
- **Replace separators.** Swap every namespace separator `\` for a directory separator `/`.
- **Append `.php`.** Add the extension and join it onto the base directory.

Walk it through with `App\Services\InvoiceMailer` and the map above:

1. Longest matching prefix is `App\` → base dir `src/`.
2. Strip it, leaving `Services\InvoiceMailer`.
3. Replace `\` with `/` → `Services/InvoiceMailer`.
4. Append `.php` and prepend the base dir → `src/Services/InvoiceMailer.php`.

That's the whole algorithm. There's no scanning, no guessing, no reading every file to see what's inside. The path is computed directly from the name, which is what makes PSR-4 fast and, critically, **dynamic** — a class whose file didn't exist a second ago will load the instant the file appears at the computed path, with no regeneration step.

### The case-sensitivity trap

PSR-4 resolution is case-sensitive on any case-sensitive filesystem, which means Linux. Your macOS or Windows machine will happily load `src/services/invoicemailer.php` when the class is `App\Services\InvoiceMailer`, because those filesystems don't care about case. Then you deploy to a Linux container and it explodes.

The namespace segments must match the directory names exactly, and the class name must match the filename exactly, including capitalization. `App\Services\InvoiceMailer` demands `src/Services/InvoiceMailer.php`, with a capital S, I, and M. This is one of the most common "works on my machine" failures I've had to debug, and it never shows up locally.

## classmap, files, and when to reach for each

PSR-4 is the default and the one you'll use for almost everything. Composer supports two other strategies, though, and the difference matters the day a class refuses to load for reasons PSR-4 can't explain.

**`classmap`** scans one or more directories, opens every PHP file, reads out the class, interface, trait, and enum names it declares, and builds a literal map of `FQCN => file path`. It doesn't care about namespace-to-directory conventions at all; a class can sit anywhere. That flexibility is also its cost: because the map is built once by scanning, **it does not know about files you add later**. Add a new class into a classmapped directory and it will *not* load until you regenerate the map.

```json
{
    "autoload": {
        "classmap": ["database/seeds", "app/Legacy"]
    }
}
```

Classmap is the right tool for legacy code that doesn't follow PSR-4 naming, or for third-party directories you can't restructure.

**`files`** is different again: every file listed is `require`d unconditionally on every request, before any code runs. There's no class resolution involved; this is for procedural code that defines functions, not classes. Global helper functions are the classic use.

```json
{
    "autoload": {
        "files": ["src/helpers.php"]
    }
}
```

Keep the `files` list short. Everything in it loads on every single request whether you use it or not, so it's pure overhead if you're careless.

## The command that fixes half of these problems: `dump-autoload`

`composer dump-autoload` regenerates the autoload files in `vendor/composer/`. It reads your current `composer.json`, rebuilds the PSR-4 map, and re-scans every classmapped directory. That's it.

When do you actually need it?

- **After editing the `autoload` section of `composer.json`.** A new PSR-4 prefix, new classmap dir, or new file all mean Composer has to regenerate to pick up the change.
- **After adding a class to a classmapped directory.** The scan-based map is stale until you re-dump. This is *the* answer to "why does my new class throw class-not-found even though the file is right there."
- **Never, for a new PSR-4 class in an existing mapped directory.** PSR-4 computes the path from the name at runtime, so a brand-new file at the correct path loads immediately. If you're re-dumping every time you add a normal class, you've misdiagnosed something.

`composer install` and `composer update` both run a dump automatically at the end, so you don't dump separately after those.

If you've hit the class-not-found error and want a systematic checklist for tracking it down (prefix mismatches, wrong base dir, case bugs, stale classmaps), I wrote a dedicated troubleshooting guide: [PHP class not found and autoload issues](/blog/php-class-not-found-autoload).

## autoload-dev: keep test-only code out of production

There's a second block, `autoload-dev`, for namespaces you only need in development and testing: factories, test cases, seeders. It's loaded when you run `composer install` normally, but skipped when you install with `--no-dev` (which you should be doing on production builds).

```json
{
    "autoload": {
        "psr-4": { "App\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "App\\Tests\\": "tests/" }
    }
}
```

Putting test namespaces here means your production autoloader map stays lean and doesn't reference directories that won't even be deployed.

## Optimizing the autoloader for production

In development you want the dynamic behavior: add a file, it loads, no ceremony. In production you want the opposite: predictability and speed, no filesystem probing on every class load. Composer gives you three levels.

**Level 1, `--optimize` (`-o`).** Converts your PSR-4 rules into a big classmap. Instead of computing a path and asking the filesystem "does this file exist?" on every lookup, Composer already has an exact `FQCN => path` map built at deploy time.

```bash
composer dump-autoload --optimize
```

The trade-off mirrors classmap's: classes added after the dump that aren't in the map fall back to the slower PSR-4 check. On a deployed production build, nothing is being added, so this is a pure win.

**Level 2, `--classmap-authoritative` (`-a`).** Goes further: it tells Composer the classmap is the *complete and only* truth. If a class isn't in the map, don't even bother checking the filesystem; declare it not found immediately.

```bash
composer dump-autoload --classmap-authoritative
```

This removes the fallback `file_exists` probing entirely, which is faster. The obvious hazard: any class that legitimately isn't in the map (say, something generated at runtime) will fail to load. Only use it when your entire codebase is PSR-4/classmap-clean.

**Level 3, APCu cache.** With `--apcu-autoloader`, Composer caches the results of class-to-file lookups in APCu shared memory, so repeated lookups across requests skip the work. It layers on top of whatever strategy you're using rather than replacing it, and it needs the APCu extension installed and enabled on the server.

```bash
composer dump-autoload --apcu-autoloader
```

For a typical production deploy I run:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

`--optimize-autoloader` is the install-time equivalent of the `-o` dump flag. If you're packaging PHP into containers, autoloader optimization pairs naturally with the image work covered in [reducing Docker image size for PHP](/blog/reduce-docker-image-size-php) and a proper [production Laravel Docker setup](/blog/dockerize-laravel-production).

## Pitfalls I keep seeing

- **Trailing slash and separators in `composer.json`.** The namespace key needs escaped backslashes and a trailing one (`"App\\"`), and the directory value conventionally ends with a slash (`"src/"`). Getting this subtly wrong produces a map that silently never matches.
- **Namespace not matching the directory tree.** PSR-4 has no tolerance here. If the class declares `namespace App\Services;` the file must sit under the base dir + `Services/`. A file in the right folder with the wrong `namespace` line fails just as hard as a misplaced file.
- **Case mismatches that only bite on Linux.** Covered above, but worth repeating because it costs the most time. Match capitalization exactly.
- **Forgetting to re-dump after touching classmapped dirs.** New file in a classmap directory equals invisible class until you run `dump-autoload`.
- **Shipping `--classmap-authoritative` with runtime-generated classes.** Anything not in the frozen map is dead on arrival. Great for locked-down code, wrong for anything that materializes classes at runtime.
- **Committing an optimized autoloader to your repo.** The optimized classmap is a build artifact. Generate it in your deploy pipeline, don't check it in and drag it around your dev environment.

## FAQ

### Do I need to run `composer dump-autoload` every time I add a class?

No, not for PSR-4 classes in a directory that's already mapped. PSR-4 resolves the file path from the class name at runtime, so a new file at the correct path loads immediately. You only need to dump after changing the `autoload` config or after adding a class to a **classmapped** directory.

### What's the difference between `--optimize` and `--classmap-authoritative`?

`--optimize` builds a classmap from your PSR-4 rules but keeps the PSR-4 filesystem check as a fallback for classes not in the map. `--classmap-authoritative` drops that fallback entirely: if a class isn't in the map, it's reported as not found without touching the disk. Authoritative is faster but unforgiving of anything missing from the map.

### Why does my class load locally but break on the server?

Nine times out of ten it's case sensitivity. macOS and Windows filesystems ignore case, so `invoicemailer.php` loads a class named `InvoiceMailer`. Linux doesn't forgive that. Make the filename and every namespace segment match the class name's capitalization exactly.

### Should test classes go in `autoload` or `autoload-dev`?

`autoload-dev`. It keeps test-only namespaces out of the production autoloader when you install with `--no-dev`, so your deployed build doesn't carry references to directories that aren't even shipped.

## Wrapping up

PSR-4 autoloading is deterministic, and that's the point: prefix in, file path out, computed mechanically with no scanning. Once you can trace `App\Services\InvoiceMailer` to `src/Services/InvoiceMailer.php` in your head, most "class not found" errors stop being mysteries and become a short checklist — check the prefix, check the base dir, check the case, and check whether you're in classmap territory and forgot to dump.

For day-to-day work, let PSR-4 stay dynamic and don't over-dump. For production, install with `--no-dev --optimize-autoloader`, add `--classmap-authoritative` if your codebase is clean, and layer APCu on top if the extension is available. That combination gives you the fast, predictable class loading you want in production without the friction you'd hate in development.