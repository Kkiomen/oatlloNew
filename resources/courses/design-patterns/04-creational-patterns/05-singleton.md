---
title: "Singleton"
slug: singleton
seo_title: "Singleton Pattern in PHP - and Why to Avoid It"
seo_description: "Learn the singleton pattern in PHP, then the honest reasons it's often an anti-pattern: global state, hard testing, and why DI usually beats it."
---

The **singleton** pattern makes sure a class has exactly *one* instance and gives everyone
a single global way to reach it. It's the most famous creational pattern - and, honestly,
the one you should reach for least. We'll show it, then explain why.

## What is the singleton pattern?

Two mechanics define the singleton pattern in PHP: a private constructor, so nothing
outside the class can `new` it, and a static accessor that creates the instance the first
time and returns that same object forever after. Together they promise "there is exactly
one of me, and here is how you get it." The promise is real. The trouble, as you'll see, is
the *global reach* it hands out along the way.

## The problem it claims to solve

Sometimes it feels like there should only ever be one of something: one configuration
object, one database connection, one logger. The singleton enforces that by hiding the
constructor and handing out the same instance every time.

## What it looks like

```php
final class Logger
{
    private static ?Logger $instance = null;

    private function __construct() {}          // no `new` from outside

    public static function instance(): Logger
    {
        return self::$instance ??= new self(); // create once, reuse forever
    }

    public function log(string $message): void
    {
        // write the message somewhere
    }
}
```

Anywhere in the codebase, you call:

```php
Logger::instance()->log('User registered');
```

The private constructor blocks `new Logger()`, and `instance()` always returns the same
object. That's the whole pattern.

## Why it's often an anti-pattern

It looks convenient, but a singleton causes real problems:

- **It's global state in disguise.** Any code, anywhere, can grab the instance and change
  it. That's exactly the tight coupling and hidden dependencies the earlier chapters warned
  against - `Logger::instance()` buried inside a method is a dependency that doesn't show up
  in the constructor, so you can't see it from the outside.
- **It's hard to test.** Because the instance is shared and created behind a static method,
  you can't easily swap in a fake logger for a test, and state leaks between tests since the
  single instance survives from one to the next.
- **It hides dependencies.** A class that calls `Config::instance()` internally looks like
  it needs nothing, but it secretly depends on that global. Readers and tests are misled.

## What to do instead: dependency injection

The usual fix is to create *one* instance yourself and *pass it in* to whoever needs it -
dependency injection, from the
[dependency inversion lesson](/course/design-patterns/solid/dependency-inversion):

```php
class Registration
{
    public function __construct(private Logger $logger) {} // dependency is visible

    public function register(string $email): void
    {
        // ...
        $this->logger->log("Registered $email");
    }
}
```

`Logger` no longer needs a private constructor or a static instance - it's a plain class.
You still create only one and share it, but *you* control that from the outside. The
dependency is now visible in the constructor, and a test can pass a fake logger with no
trouble. Frameworks make this effortless: Laravel's service container hands you the same
shared instance wherever you type-hint it - the benefit of a singleton without the global.

## When (rarely) to use it

There are narrow cases - a truly stateless helper, or low-level infrastructure - where a
singleton is acceptable. But in application code, if you're reaching for one, first ask
whether injecting a single shared instance would work instead. It almost always does.

A useful tell in code review: the damage of a singleton isn't the "one instance" part, it's
the static call baked into a method body. `Config::instance()` sitting inside `register()`
is a dependency you can't see from the constructor and can't replace in a test. If you keep
"one instance" but reach it through the constructor instead of a static method, every
objection on this page disappears - which is exactly what dependency injection does.

## FAQ

### Isn't a database connection a good singleton?

You *do* usually want one shared connection, but that's a reason to create it once and
inject it - not to hard-wire a global. Frameworks manage a single connection for you
through their container, which gives you the "one instance" benefit without the downsides.

### Why do people call it an anti-pattern if it's a Gang of Four pattern?

Because experience showed its costs - global state and untestable code - outweigh its
convenience in most cases. It's a valid pattern that's simply overused. Knowing *why* it's
discouraged is more useful than memorizing how to write one.

### Singleton vs dependency injection: which should I use?

Prefer dependency injection in almost all application code. Both give you a single shared
object; the difference is who holds the reference. A singleton exposes itself globally
through a static method, which hides the dependency and blocks test doubles. Dependency
injection creates the one instance at the edge and passes it in, so the dependency is
visible and swappable. In a framework like Laravel the service container does this for you -
bind a class as a singleton once, type-hint it anywhere, and you get "one instance" with
none of the global-state cost.
