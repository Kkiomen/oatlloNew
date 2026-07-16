---
title: "Prototype"
slug: prototype
seo_title: "Prototype Pattern in PHP - clone and __clone"
seo_description: "Learn the prototype pattern in PHP: copy an existing configured object with clone instead of rebuilding it from scratch, and handle __clone safely."
---

The **prototype** pattern creates a new object by *copying* an existing one, rather than
building it from scratch. When you already have an object set up the way you want, cloning
it can be simpler and cheaper than constructing a fresh one step by step.

## What is the prototype pattern?

The prototype pattern turns an existing, fully configured object into the template for new
ones. Rather than run the construction steps again, you copy the finished object and adjust
only what differs. In PHP the pattern is nearly built in: the `clone` keyword is the copy,
and the `__clone()` hook is where you keep the copy from secretly sharing state with its
source. Get that hook right and the rest is trivial.

## The problem it solves

Suppose building an object is expensive or involved - lots of configuration, or data pulled
from somewhere slow. If you need several nearly identical copies, redoing all that setup
each time is wasteful. With a prototype, you configure one object once, then copy it and
tweak only what differs.

PHP has this built in with the `clone` keyword, so the pattern is often just idiomatic PHP:

```php
$template = new EmailMessage();
$template->from = 'hello@oatllo.com';
$template->subject = 'Welcome';

$toAlice = clone $template;
$toAlice->to = 'alice@example.com';

$toBob = clone $template;
$toBob->to = 'bob@example.com';
```

Both copies start from the fully configured `$template`; you only set the part that
changes.

## The shallow copy trap

By default `clone` makes a **shallow copy**: scalar values are duplicated, but object
properties are copied *by reference*, so the clone and the original share the same nested
object. Change it through one, and it changes for both:

```php
class Invoice
{
    public function __construct(public Customer $customer) {}
}

$a = new Invoice(new Customer('Acme'));
$b = clone $a;
$b->customer->name = 'Globex';

echo $a->customer->name; // "Globex" - the original changed too!
```

## Fixing it with `__clone`

PHP calls the special `__clone()` method automatically on the new object right after a
clone. Use it to duplicate any nested objects so the copy is independent:

```php
class Invoice
{
    public function __construct(public Customer $customer) {}

    public function __clone(): void
    {
        $this->customer = clone $this->customer; // deep-copy the nested object
    }
}
```

Now `clone $a` gets its own `Customer`, and editing one invoice no longer affects the
other. Only add `__clone()` when a class holds object properties that shouldn't be shared -
for objects made purely of scalars, the default shallow copy is already correct.

## Common mistake

Forgetting the shallow-copy behavior is the number one prototype bug: you clone an object,
change the copy, and mysteriously the original changes too. Any time a class has object
properties and you plan to clone it, ask whether those should be copied in `__clone()`.

## When to use it

Reach for prototype when creating an object from scratch is costly or complicated and you
need several similar ones, or when you want to snapshot an object's current state and
branch from it. For simple objects, a plain constructor is clearer - don't clone just for
the sake of it.

## FAQ

### Is `clone` the whole pattern in PHP?

Essentially, yes. The Gang of Four described prototype for languages without built-in
cloning; PHP gives you `clone` and `__clone()` directly, so the pattern is mostly just
using those correctly - especially handling nested objects.

### Does `clone` call the constructor?

No. `clone` copies the existing object's properties without running `__construct()`. If you
need setup logic on copy, put it in `__clone()` instead. This is also why prototype can be
faster than rebuilding: it skips whatever expensive work the constructor would redo.

### When should I use prototype instead of a factory or builder?

Use prototype when the *starting point* is an object you already have, not a fresh set of
inputs. A factory or builder makes an object from parameters; prototype copies a live one
and tweaks the difference. It shines for "give me another one just like this" cases -
duplicating a configured template, or snapshotting an object's current state to branch from.
For a plain object built from a few values, a constructor is clearer than cloning.
