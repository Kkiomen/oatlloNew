---
title: "Proxy - Control Access to an Object"
slug: proxy
seo_title: "Proxy Pattern in PHP: Control Access to an Object"
seo_description: "Learn the proxy pattern: a stand-in with the same interface that controls access to the real object - lazy loading, caching, access checks."
---

## What is the proxy pattern?

The **proxy** pattern puts a stand-in in front of the real object. The proxy shares the
real object's interface, so callers can't tell the difference, but it controls access -
deciding when to create the real object, whether the caller is allowed, or whether an
answer can be cached. To the code using it, the proxy *is* the object; the substitution is
meant to go unnoticed.

## The problem it solves

Say generating a report is expensive:

```php
interface Report
{
    public function html(): string;
}

final class SalesReport implements Report
{
    public function __construct()
    {
        // ...runs heavy database queries right here...
    }

    public function html(): string
    {
        return '<table>...</table>';
    }
}
```

You wire this report onto a dashboard, but the page often renders without anyone opening
the report. You're paying the heavy cost every time, even when the report is never shown.

## The proxy

A proxy implements the same interface and holds off on the real work until it's actually
needed - this is *lazy loading*:

```php
final class LazyReport implements Report
{
    private ?Report $real = null;

    public function __construct(private Closure $factory) {}

    public function html(): string
    {
        $this->real ??= ($this->factory)();

        return $this->real->html();
    }
}
```

The caller receives a `Report` and uses it exactly the same way:

```php
$report = new LazyReport(fn () => new SalesReport());

// Nothing heavy has happened yet.
echo $report->html(); // Now SalesReport is built, once, on first use.
```

`SalesReport` is only constructed when `html()` is first called, and never if it isn't. The
dashboard code didn't change - it still just holds a `Report`.

## Other jobs a proxy does

The same shape covers several needs, all by controlling access to the real object:

- **Protection proxy** - check permissions before delegating: `if (! $user->canView()) throw ...;`
- **Caching proxy** - remember the result and skip the real call next time (much like the
  [caching decorator](/course/design-patterns/structural-patterns/decorator) you saw
  earlier).
- **Remote proxy** - stand in for an object that actually lives on another server.

## When to use it

- Creating the real object is expensive and often unnecessary (lazy loading).
- Access needs a gatekeeper - permissions, rate limits, logging - before reaching the real
  object.
- You want caching or remote access without the caller knowing.

## Common mistake

A proxy should be *invisible*: same interface, same observable behavior, just controlled
access. If your proxy changes what the method returns or adds unrelated features, it's
drifted into being a [decorator](/course/design-patterns/structural-patterns/decorator) or
worse. Keep it to the one job of managing access to the real subject.

## FAQ

### Proxy vs decorator?

They share an interface with what they wrap, but the intent differs. A decorator *adds
behavior*; a proxy *controls access* to an object whose behavior stays the same (deciding
when it runs, whether it's allowed, or reusing its result). Same mechanic, different goal.

### Proxy vs adapter?

An [adapter](/course/design-patterns/structural-patterns/adapter) changes the interface so
a class fits somewhere new. A proxy keeps the *exact* same interface so it's an invisible
stand-in. Adapter converts; proxy substitutes.

### Isn't lazy loading built into ORMs already?

Yes - lazy-loaded relations in an ORM are the proxy pattern under the hood. A [later chapter](/course/design-patterns/patterns-in-the-real-world/patterns-you-already-use-in-laravel)
returns to this framework example. Knowing the pattern helps you recognize what's happening
when a property "magically" loads on first access - and why that magic bites you in a loop:
each iteration triggers the proxy's real call, which is exactly how the N+1 query problem
starts. The invisible stand-in is convenient right up until you can't see the cost.
