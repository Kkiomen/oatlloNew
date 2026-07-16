---
title: "Factory Method"
slug: factory-method
seo_title: "Factory Method Pattern in PHP - Beginner Guide"
seo_description: "Learn the factory method pattern in PHP: move object creation behind a method so callers depend on an interface, not a specific new Concrete()."
---

The **factory method** pattern in PHP moves the decision of *which* object to create into a
dedicated method or class. Callers ask the factory for an object and get back something
that fits an interface - without knowing, or caring, which concrete class they got.

## What is the factory method pattern?

At its core, the factory method pattern is one rule: never let the code that *uses* an
object also decide which class to `new` up. Push that decision behind a method. What comes
back is typed to an interface, so the caller stays ignorant of the concrete class. That one
move is what buys you the swappability and testability the rest of this lesson leans on.

## The problem it solves

When you write `new StripeGateway()` directly in your code, that code is now tied to
Stripe. Every place that creates the object has to be changed if you want to support
PayPal too, and you can't easily swap the class in a test. This is exactly the tight
coupling that
[dependency inversion](/course/design-patterns/solid/dependency-inversion) warns about.

Here's the coupled version:

```php
class Checkout
{
    public function pay(int $amount): void
    {
        $gateway = new StripeGateway(); // hard-wired to one class
        $gateway->charge($amount);
    }
}
```

## The factory version

First, an interface and two implementations:

```php
interface PaymentGateway
{
    public function charge(int $amount): void;
}

class StripeGateway implements PaymentGateway
{
    public function charge(int $amount): void { /* ... */ }
}

class PayPalGateway implements PaymentGateway
{
    public function charge(int $amount): void { /* ... */ }
}
```

Now a factory that decides which one to build:

```php
class PaymentGatewayFactory
{
    public function create(string $provider): PaymentGateway
    {
        return match ($provider) {
            'stripe' => new StripeGateway(),
            'paypal' => new PayPalGateway(),
            default  => throw new InvalidArgumentException("Unknown: $provider"),
        };
    }
}
```

And the caller no longer names a concrete class:

```php
class Checkout
{
    public function __construct(private PaymentGatewayFactory $factory) {}

    public function pay(string $provider, int $amount): void
    {
        $gateway = $this->factory->create($provider);
        $gateway->charge($amount);
    }
}
```

## Why this is better

`Checkout` now depends only on the `PaymentGateway` interface and the factory. Adding a
third provider means adding one class and one line in the factory - `Checkout` doesn't
change. This is the [open-closed principle](/course/design-patterns/solid/open-closed) in
action: open to new gateways, closed to edits in the code that uses them. It's also far
easier to test, because you can hand `Checkout` a fake factory.

## Common mistake

Don't reach for a factory when you only ever create *one* class and always will. If there's
no real choice to make, `new Thing()` is simpler and clearer - and
[YAGNI](/course/design-patterns/core-principles/yagni) applies. The factory earns its keep
when there are several implementations, or when the creation logic itself is non-trivial.

One practical trap: a `match` or `switch` on a string is fine in a single small factory,
but when you find the *same* switch copied across three factories, that is the smell to act
on. It usually means the branching belongs in one place - or that the provider string
should map to a class through config, so adding a gateway is a config line, not a code edit.

## When to use it

Use a factory method when the exact class to create depends on a value at runtime (a
config setting, user input, a file type), when you want callers decoupled from concrete
classes, or when building the object involves logic you don't want scattered everywhere.

## FAQ

### Is a factory the same as a factory method?

They're closely related. Purists distinguish the "factory method" pattern (a method
subclasses override) from a "simple factory" (one class with a create method, like above).
In everyday work people use "factory" for both. The intent - hide which class gets built -
is the same.

### Where should the factory live?

Usually in its own class, injected where it's needed. That keeps creation logic in one
place and lets you swap or mock the factory in tests.
