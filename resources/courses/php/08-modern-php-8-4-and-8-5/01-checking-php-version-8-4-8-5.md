---
title: "Checking Your PHP Version and What's New in 8.4 and 8.5"
slug: checking-php-version-whats-new
seo_title: "How to Check Your PHP Version (8.4 and 8.5) - Simple Guide"
seo_description: "Learn how to check your PHP version from the command line and in code, and get a quick overview of what changed in PHP 8.4 and 8.5."
---

Before you try any of the new features in this chapter, it helps to know **which version of PHP you're running**. A feature added in PHP 8.4 simply won't work on PHP 8.3 - you'll get a syntax error. This short lesson shows you how to check your version two ways, and gives you a map of what's coming in the rest of the chapter.

## Checking the version from the command line

Open your terminal and type:

```bash
php --version
```

You'll see something like this:

```bash
PHP 8.4.3 (cli) (built: Jan 12 2025 10:22:41) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.4.3, Copyright (c) Zend Technologies
```

The first line is what matters. Here it reads **8.4.3**, which means the examples in this chapter that need 8.4 will run, but the 8.5 ones will not.

## Checking the version from inside PHP code

Sometimes you want your code itself to know the version - for example, to show a warning or to pick a different code path. PHP gives you a built-in constant called `PHP_VERSION`:

```php
<?php
echo PHP_VERSION; // 8.4.3
```

There is also `phpversion()`, a function that returns the same string:

```php
<?php
echo phpversion(); // 8.4.3
```

If you only care about the major and minor numbers (the `8` and the `4`), PHP gives you separate constants for each part:

```php
<?php
echo PHP_MAJOR_VERSION;   // 8
echo PHP_MINOR_VERSION;   // 4
echo PHP_RELEASE_VERSION; // 3
```

## Comparing PHP versions safely with version_compare

You might be tempted to compare versions with plain math, but that breaks quickly - `8.4` is not really a number, and `8.10` would look "smaller" than `8.9`. PHP has a dedicated function for this called `version_compare()`:

```php
<?php
if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
    echo 'You can use PHP 8.4 features.';
} else {
    echo 'Please upgrade to PHP 8.4 or newer.';
}
```

The third argument is the comparison you want: `'>='` means "greater than or equal to". This is the safe, correct way to check for a minimum version.

## A quick map of what's new

Here is what the rest of this chapter covers, grouped by the version that introduced it.

**PHP 8.4** brought:

- `new User()->method()` without wrapping `new` in parentheses.
- **Property hooks** - `get` and `set` logic right on a property.
- **Asymmetric visibility** - public to read, private to write.
- `array_find`, `array_find_key`, `array_any`, and `array_all`.
- The `#[\Deprecated]` attribute.

**PHP 8.5** added:

- The **pipe operator** `|>` for chaining function calls.
- `array_first()` and `array_last()`.
- The `#[\NoDiscard]` attribute.

## Summary

- Run `php --version` in the terminal to see your version.
- In code, use the `PHP_VERSION` constant or `phpversion()`.
- Use `version_compare()` to safely check for a minimum version - never compare versions as plain numbers.
- PHP 8.4 and 8.5 add small, focused features that make code shorter and safer.

Now that you know which PHP you have, let's start with the smallest but most satisfying change in 8.4: dropping a pair of parentheses.

## FAQ

### How do I check my PHP version?

Run `php --version` (or `php -v`) in your terminal. Inside PHP code, echo the `PHP_VERSION` constant or call `phpversion()`.

### Why shouldn't I compare PHP versions as numbers?

Because a version like `8.10` is newer than `8.9`, but as a decimal number `8.10` looks smaller. Use `version_compare(PHP_VERSION, '8.4.0', '>=')` instead - it understands version strings correctly.

### Will the earlier chapters still work on older PHP?

Yes. Everything before this chapter runs on PHP 8.0 and up. Only the features in this chapter need PHP 8.4 or 8.5.
