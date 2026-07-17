---
title: "Decorator - Add Behavior by Wrapping"
slug: decorator
seo_title: "Decorator Pattern in PHP: Add Behavior by Wrapping"
seo_description: "Learn the decorator pattern: wrap an object to add behavior without subclassing, and stack decorators. Add caching and logging in PHP."
---

## What is the decorator pattern?

The **decorator** pattern adds behavior to an object by *wrapping* it in another object
that shares the same interface. The wrapper does its extra work, then hands off to the
thing it wraps. Because the wrapper looks identical to what it wraps, you can stack several
of them to combine behaviors, and the code holding the object never notices.

## The problem it solves

You have a service that fetches exchange rates:

```php
interface RateProvider
{
    public function rateFor(string $currency): float;
}

final class ApiRateProvider implements RateProvider
{
    public function rateFor(string $currency): float
    {
        // ...slow HTTP call...
        return 4.31;
    }
}
```

Now you want caching. And logging. The tempting move is to subclass:
`CachingApiRateProvider`, then `LoggingCachingApiRateProvider`... Every combination needs
its own class, and it all breaks if you swap `ApiRateProvider` for another provider.
That's the [composition over inheritance](/course/design-patterns/core-principles/composition-over-inheritance)
problem from the principles chapter.

## The decorator

A decorator implements the same interface and holds an instance of it:

```php
final class CachingRateProvider implements RateProvider
{
    private array $cache = [];

    public function __construct(private RateProvider $inner) {}

    public function rateFor(string $currency): float
    {
        return $this->cache[$currency] ??= $this->inner->rateFor($currency);
    }
}

final class LoggingRateProvider implements RateProvider
{
    public function __construct(
        private RateProvider $inner,
        private Logger $log,
    ) {}

    public function rateFor(string $currency): float
    {
        $this->log->info("Rate requested: {$currency}");

        return $this->inner->rateFor($currency);
    }
}
```

Each one adds one thing and then delegates to `$inner`. Because they all implement
`RateProvider`, a decorator can wrap either the real provider or another decorator.

## Stacking decorators

You build the behavior you want by nesting:

```php
$provider = new LoggingRateProvider(
    new CachingRateProvider(
        new ApiRateProvider()
    ),
    $log,
);

$provider->rateFor('USD'); // logs, then caches, then calls the API
$provider->rateFor('USD'); // logs, then returns the cached value
```

The rest of your app just sees a `RateProvider`. It has no idea whether it's holding the
bare API client or three layers of wrapping. You add, remove or reorder behaviors by
changing how you build the object, without touching any of the classes.

## When to use it

- You want to add behavior (caching, logging, retries, compression) to individual objects,
  not to a whole class.
- You want those behaviors to be mixable and stackable.
- Subclassing would explode into a class per combination.

## Common mistake

Order matters, and it's easy to get wrong. Logging *outside* caching logs every call;
logging *inside* caching only logs the ones that reach the real provider. Decide which you
want. That ordering is also where a subtle bug hides: put a retry decorator *outside* the
cache and a failed call retries against the network; put it *inside* and it retries the
cache lookup, which can't fail. Keep each decorator focused on one job too - a decorator
that caches *and* logs *and* validates is just the subclass mess wearing a new hat.

## FAQ

### Decorator vs inheritance?

Inheritance fixes behavior at compile time and multiplies classes for each combination. A
decorator composes behavior at runtime from small, single-purpose wrappers you can mix in
any order. It follows the [open/closed principle](/course/design-patterns/solid/open-closed): you add features without editing existing
classes.

### Decorator vs adapter?

A decorator keeps the same interface and adds behavior. An
[adapter](/course/design-patterns/structural-patterns/adapter) changes the interface so a
class fits somewhere new. Same wrapping mechanic, opposite intent.

### Isn't this just middleware?

Yes - HTTP middleware is the decorator pattern applied to a request handler. Each layer can
act before and after the next one, and you stack them in a chosen order.
