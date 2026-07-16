---
title: "The #[\\NoDiscard] Attribute in PHP 8.5"
slug: nodiscard-attribute
seo_title: "PHP 8.5 #[NoDiscard] Attribute: Don't Ignore Return Values"
seo_description: "Learn the PHP 8.5 #[NoDiscard] attribute that warns when you call a function but ignore its return value - catching a whole class of silent bugs."
---

Some functions are only useful for the value they **return**. If you call one and throw that value away, you almost certainly made a mistake. **PHP 8.5** adds the `#[\NoDiscard]` attribute, which tells PHP to **warn you** when a function's return value is ignored. It catches a subtle, common class of bug.

Like `#[\Deprecated]` from the previous version, this is an attribute - a small label in `#[...]` you attach to a function. You're just using the one PHP provides.

## The bug it prevents

Look at this function. It doesn't change anything in place - it **returns** a new, cleaned-up string:

```php
<?php
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

$comment = '  <b>Nice!</b>  ';
sanitize($comment); // BUG: the result is thrown away!

echo $comment; // still "  <b>Nice!</b>  " - nothing was cleaned
```

The call to `sanitize($comment)` looks like it does something, but its whole purpose is the value it returns - and we ignored it. `$comment` is unchanged. This kind of bug is easy to miss because the code *looks* correct and runs without any error.

## Marking the function with #[\NoDiscard]

Add `#[\NoDiscard]` above the function to say "the caller must use my return value":

```php
<?php
#[\NoDiscard]
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

sanitize($comment); // Warning: return value of sanitize() is discarded
```

Now PHP raises a warning the moment you call `sanitize()` without doing anything with the result. The mistake surfaces immediately instead of hiding as wrong data later.

## Adding a message

You can include a short note explaining why the value matters, just like with `#[\Deprecated]`:

```php
<?php
#[\NoDiscard(message: "assign the cleaned string to a variable")]
function sanitize(string $input): string {
    return trim(strip_tags($input));
}
```

The message appears in the warning, guiding whoever made the mistake toward the fix.

## Fixing the warning: use the value

The obvious fix is to actually use what comes back:

```php
<?php
$clean = sanitize($comment); // no warning - we used the result
echo $clean; // Nice!
```

## When you really do want to ignore it

Once in a while you genuinely mean to discard the value - maybe you're calling the function only for a side effect in that one spot. To silence the warning **on purpose**, cast the call to `(void)`:

```php
<?php
(void) sanitize($comment); // "yes, I meant to ignore this"
```

The `(void)` cast is a clear signal to PHP - and to the next person reading the code - that ignoring the result was intentional, not an accident.

## Where this is most useful

`#[\NoDiscard]` fits functions whose return value is the *whole point*:

- Functions that return a **new** value instead of modifying their argument (like our `sanitize`).
- Functions that return a **result you must check**, such as a success flag or a validation outcome.
- "Builder" style methods that return a new object rather than changing the current one.

For these, silently dropping the return value is nearly always a bug - which is exactly what the attribute is there to catch.

## Summary

- PHP 8.5's `#[\NoDiscard]` attribute warns when a function's **return value is ignored**.
- It targets functions whose only purpose is the value they return.
- Add `message:` to explain what the caller should do with the value.
- Fix the warning by using the return value, or cast the call to `(void)` when you mean to ignore it.

## FAQ

### What does `#[\NoDiscard]` do in PHP 8.5?

It marks a function so that PHP emits a warning whenever the function is called but its return value isn't used. It's meant for functions whose result is their whole purpose.

### How do I intentionally ignore the return value?

Cast the call to `(void)`, for example `(void) sanitize($comment);`. This tells PHP the discard is deliberate and suppresses the warning.

### How is it different from `#[\Deprecated]`?

`#[\Deprecated]` warns that a function itself is outdated. `#[\NoDiscard]` warns that you called a still-valid function but forgot to use what it returned. They solve different problems.
