---
name: "PHP 8.3 Typed Class Constants Explained with Examples"
slug: php-8-3-typed-class-constants
short_description: "A practical guide to PHP 8.3 typed constants: syntax, covariance rules, interface gotchas, and runnable examples for cleaner, safer class contracts."
language: en
published_at: 2026-07-15 09:00:00
is_published: true
tags: [php, php-8-3, oop, type-system]
---

For years, class constants in PHP were the one corner of the type system that stayed stubbornly untyped. You could type your properties, your parameters, your return values — but a `const` was whatever value you happened to assign it, and nothing stopped a subclass from quietly changing its shape. **PHP 8.3 typed constants** close that gap. Now you can write `const string STATUS = 'active';` and the engine will hold every subclass and interface implementer to that promise.

If you've already adopted readonly properties and enums, this feature slots right in. It's the last obvious place where a type annotation was missing, and honestly it's overdue.

## What typed class constants actually are

Before 8.3, this was your only option:

```php
class HttpClient
{
    const TIMEOUT = 30;
    const BASE_URL = 'https://api.example.com';
}
```

The values were typed at runtime (one's an `int`, one's a `string`), but the *declaration* carried no type information. A child class could override `TIMEOUT` with a string and PHP wouldn't blink.

PHP 8.3 lets you pin the type down:

```php
class HttpClient
{
    const int TIMEOUT = 30;
    const string BASE_URL = 'https://api.example.com';
}
```

This works on **class constants, interface constants, enum constants, and trait constants** (trait constants themselves only arrived in 8.2, so this is fresh territory). The type goes between the `const` keyword and the constant name, and any visibility modifier stays where it always was: `public const string FOO = 'bar';`.

You can use most of the type system here:

- Scalar types: `int`, `float`, `string`, `bool`
- `array`
- Class and interface names
- Nullable types (`?string`)
- Union types (`int|string`)

One deliberate exclusion: you **cannot** use `void`, `never`, `callable`, or a bare `static`. Those don't describe a stored value, so they make no sense on a constant.

## Basic syntax and valid examples

Here's a fuller example that runs as-is:

```php
<?php

interface HasVersion
{
    const string VERSION = '1.0.0';
}

class PaymentGateway implements HasVersion
{
    const int MAX_RETRIES = 3;
    const float DEFAULT_FEE = 2.9;
    const array SUPPORTED_CURRENCIES = ['USD', 'EUR', 'PLN'];
    const ?string SANDBOX_KEY = null;

    // Redeclaring an interface constant with the SAME type is fine.
    const string VERSION = '2.1.0';
}

echo PaymentGateway::VERSION;             // 2.1.0
echo PaymentGateway::MAX_RETRIES;         // 3
var_dump(PaymentGateway::SUPPORTED_CURRENCIES);
```

Nothing surprising there, and that's the point. The type annotations document intent and are enforced, but valid code reads exactly like you'd expect.

A subtle but useful detail: the assigned value must be **compatible** with the declared type, not identical to it. An `int` literal assigned to a `const float` is accepted because `int` widens to `float`:

```php
class Config
{
    const float RATIO = 1;   // OK — int 1 coerces to float 1.0
}

var_dump(Config::RATIO);     // float(1)
```

This mirrors how typed properties behave with the standard coercion rules.

## Covariance: the rule that makes this worth using

The real value shows up when constants are inherited. PHP enforces **covariance** on constant types, meaning a child declaration can narrow the type but never widen or contradict it.

Think of it the way you think about return types: a subclass can be more specific, never less.

```php
<?php

class Animal
{
    const int|string ID = 0;
}

class Dog extends Animal
{
    // Narrowing int|string down to int is allowed (covariant).
    const int ID = 42;
}
```

Going the other direction breaks:

```php
class Cat extends Animal
{
    // Widening int|string to include float is NOT allowed.
    const int|string|float ID = 1.5;  // Fatal error
}
```

You'll get:

```
Fatal error: Type of Cat::ID must be compatible with Animal::ID of type int|string
```

This is exactly the guarantee that was missing before. When your base class says a constant is an `int`, you can now *rely* on that everywhere the base type is in play.

## Interface constants and the redeclaration error

Interfaces are where I've seen people trip. An interface can declare a typed constant, and any implementing class that redeclares it must stay compatible.

```php
<?php

interface Priced
{
    const int PRICE = 100;
}

class Product implements Priced
{
    const string PRICE = 'free';  // incompatible!
}
```

This throws a fatal error the moment the class is linked:

```
Fatal error: Type of Product::PRICE must be compatible with Priced::PRICE of type int
```

Worth calling out clearly: this is a **fatal error**, not a catchable `TypeError` exception you can wrap in a `try/catch`. The check happens when PHP resolves the class hierarchy, so there's no runtime handler that will save you — the code simply won't run. Treat it like a type error in a method signature: fix the declaration.

There's also a quieter case. If you assign a value whose type doesn't match the declared constant type at all, that's a compile-time fatal error too:

```php
class Broken
{
    const int LIMIT = 'nope';   // Fatal error: Cannot use string as value for int constant
}
```

## Where this actually matters (and where it doesn't)

I'll be candid: for a lot of small internal constants, typed or not makes little practical difference. `const MAX = 10;` was already obviously an int.

Where typed constants earn their keep:

- **Public API contracts.** If your library exposes constants that consumers extend or implement, the covariance guarantee prevents downstream code from silently reshaping them.
- **Interfaces that define shared constants.** You get a real, enforced contract instead of a naming convention and a prayer.
- **Configuration-style base classes.** When teams extend a base config class, typed constants stop the classic "someone made `TIMEOUT` a string" bug before it ships.
- **Static analysis.** Tools like PHPStan and Psalm already infer constant types, but explicit annotations remove ambiguity for union and nullable cases.

If you're writing a throwaway script or a constant that never leaves its class, don't feel obligated to annotate everything. Use it where inheritance is in play.

## How it fits with enums and readonly properties

Typed constants aren't a replacement for enums — they complement them. Reach for an **enum** when you have a fixed set of named cases with behavior. Reach for a **typed constant** when you need a single fixed value with a guaranteed type, especially one that participates in inheritance.

They pair naturally too. You can type a constant *as* an enum:

```php
<?php

enum Status: string
{
    case Active = 'active';
    case Archived = 'archived';
}

class Post
{
    const Status DEFAULT_STATUS = Status::Active;
}

echo Post::DEFAULT_STATUS->value;   // active
```

And the same instinct that made **readonly properties** attractive ("declare it, lock it, trust it") is what typed constants bring to values that live on the class rather than the instance. Together they push PHP toward code where the shape of your data is checked, not assumed.

## Common pitfalls

- **Expecting a catchable exception.** Incompatible constant types produce a fatal error at class-linking time, not a `TypeError` you can catch. Static analysis and CI are your safety net here, not `try/catch`.
- **Confusing covariance direction.** Children may *narrow* a type (`int|string` → `int`), never widen it. If you find yourself wanting to widen, the base type was probably wrong.
- **Assuming `self`/`static` work as constant types.** They don't. Use the concrete class or interface name.
- **Over-annotating trivial constants.** Typing every private one-off constant adds noise without much payoff. Prioritize public and inherited constants.
- **Forgetting trait constant compatibility.** A trait constant is subject to the same rules when composed into a class that also declares (or inherits) that constant.
- **Targeting older runtimes.** This is strictly PHP 8.3+. Deploying typed-constant syntax to 8.2 or below is a parse error, so gate it in your `composer.json` `require`.

## FAQ

**Can I use union or nullable types on class constants in PHP 8.3?**
Yes. Union types like `int|string` and nullable types like `?string` are both supported. Intersection types are allowed as well when the constant holds an object satisfying multiple interfaces. The only excluded types are ones that don't describe a value, such as `void`, `never`, and `callable`.

**Is the type check done at compile time or runtime?**
Mostly at class-linking time, which is why an incompatible interface or parent constant surfaces as a fatal error rather than a runtime exception. A mismatch between the declared type and the assigned literal is caught when the class is compiled.

**Do typed constants replace enums?**
No. Enums model a closed set of named cases, often with methods. Typed constants pin a type onto a single value. They work well together — you can even declare a constant whose type is an enum.

**Will adding types to existing constants break my code?**
Only if a subclass or implementer already violates the type you're declaring, which usually means there was a latent bug. Adding a type that matches current values is backward compatible for consumers on PHP 8.3+.

## Conclusion

**PHP 8.3 typed constants** finish a job the language started years ago: making the type system consistent across every declaration you can write. The syntax is a one-word addition, but the payoff is a real, enforced contract on inherited and interface constants, backed by covariance rules that behave just like the ones you already know from return types.

Use them where inheritance and public APIs are involved, lean on static analysis to catch the fatal-error cases before deploy, and treat them as the natural companion to enums and readonly properties. If you're already on 8.3, there's no reason to leave your shared constants untyped: start with your interfaces and base classes, and let the compiler hold the line for you.