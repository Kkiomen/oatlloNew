---
title: "The #[\\Deprecated] Attribute in PHP 8.4"
slug: deprecated-attribute
seo_title: "PHP 8.4 #[Deprecated] Attribute: Mark Old Code Safely"
seo_description: "Learn the PHP 8.4 #[Deprecated] attribute to mark functions, methods, and constants as outdated so PHP warns anyone who still uses them."
---

As a project grows, you sometimes replace an old function with a better one but can't delete the old one right away - other code still calls it. **PHP 8.4** adds the `#[\Deprecated]` attribute, a clean, built-in way to mark code as outdated so PHP **warns** anyone who keeps using it.

If the word "attribute" is new to you, an attribute is a small label written in `#[...]` that you attach to code to add extra information. You don't need to know how to build your own here - you just use the one PHP provides.

## Marking a function as deprecated

Write `#[\Deprecated]` on the line above the function you want to retire:

```php
<?php
#[\Deprecated]
function oldGreeting(): string {
    return 'Hi';
}

echo oldGreeting();
```

When this runs, PHP still returns `'Hi'` - the function works exactly as before. But it also emits a **deprecation warning**, something like:

```bash
Deprecated: Function oldGreeting() is deprecated
```

The code doesn't break. It just tells you, loudly and automatically, that you're using something that's on its way out.

## Adding a helpful message

A bare warning isn't very helpful on its own. The attribute accepts a `message` telling people what to use instead, and a `since` noting when it was deprecated:

```php
<?php
#[\Deprecated(message: "use newGreeting() instead", since: "2.0")]
function oldGreeting(): string {
    return 'Hi';
}

function newGreeting(): string {
    return 'Hello there';
}

echo oldGreeting();
```

Now the warning reads:

```bash
Deprecated: Function oldGreeting() is deprecated, use newGreeting() instead
```

Anyone who calls the old function gets a clear pointer to the replacement, without you having to write documentation they might never read.

## It works on methods and constants too

You can deprecate a class method the same way:

```php
<?php
class PaymentService {
    #[\Deprecated(message: "use charge() instead", since: "3.1")]
    public function pay(float $amount): void {
        $this->charge($amount);
    }

    public function charge(float $amount): void {
        // new implementation
    }
}
```

And class constants:

```php
<?php
class Config {
    #[\Deprecated(message: "use TIMEOUT_SECONDS")]
    const TIMEOUT = 30;

    const TIMEOUT_SECONDS = 30;
}
```

Whenever someone calls `pay()` or reads `Config::TIMEOUT`, PHP emits the deprecation warning.

## Why this is better than a comment

Before 8.4, the common way to mark something deprecated was a `@deprecated` note in a doc comment:

```php
<?php
/**
 * @deprecated use newGreeting() instead
 */
function oldGreeting(): string {
    return 'Hi';
}
```

The problem: **nothing enforces a comment**. PHP ignores it completely, so the old function is called and no one notices until they happen to read the source. The `#[\Deprecated]` attribute is different - **PHP itself** raises a runtime warning, so the deprecation actually reaches whoever is still using the code.

## A note on seeing the warnings

Deprecation warnings are a type of notice. In development you'll usually see them in your output or error log. In production you typically log them rather than display them, so they don't reach end users. Either way, they give you a reliable trail of what still needs updating before you can safely delete the old code.

## Summary

- PHP 8.4's `#[\Deprecated]` attribute marks functions, methods, and constants as outdated.
- The code still works, but PHP emits a **deprecation warning** when it's used.
- Add `message:` to point to the replacement and `since:` to record when it was deprecated.
- Unlike a `@deprecated` doc comment, this is enforced by PHP at runtime, so it actually gets noticed.

## FAQ

### Does `#[\Deprecated]` stop the code from running?

No. The function, method, or constant still works normally. PHP only adds a deprecation warning so users know to switch to the replacement.

### What arguments does the attribute take?

Two optional named arguments: `message` (text describing what to use instead) and `since` (the version or date it was deprecated). Both show up in the warning.

### How is this better than a `@deprecated` doc comment?

A doc comment is just text - PHP ignores it. The `#[\Deprecated]` attribute is understood by PHP, which raises a real runtime warning whenever the deprecated code is used.
