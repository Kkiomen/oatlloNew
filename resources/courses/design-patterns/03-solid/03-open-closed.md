---
title: "Open/Closed Principle (OCP)"
slug: open-closed
seo_title: "Open/Closed Principle in PHP (OCP) With Examples"
seo_description: "The Open/Closed Principle says code should be open to extension but closed to modification. Learn OCP in PHP by replacing a growing switch with new types."
---

## What is the Open/Closed Principle?

The **Open/Closed Principle** is the "O" in SOLID:

> Software entities should be open for extension, but closed for modification.

In plain terms: you should be able to add new behavior by writing **new code**, not by
editing code that already works. Every time you edit a tested class to bolt on another case,
you risk breaking what was there before.

A historical footnote that clears up a lot of confusion: Bertrand Meyer coined "open/closed"
in 1988 meaning extension through *inheritance*. The version you'll use in modern PHP is
Martin's later reading - extension through *interfaces and polymorphism*. Same slogan, and
that shift is why today's examples inject a contract rather than subclass a base.

## The growing switch

A common shape that violates OCP is a `switch` (or `if/elseif` chain) that grows a new
branch every time a requirement is added:

```php
final class ShippingCalculator
{
    public function cost(string $method, float $weight): float
    {
        switch ($method) {
            case 'standard':
                return $weight * 1.5;
            case 'express':
                return $weight * 3.0;
            default:
                throw new \InvalidArgumentException("Unknown method: $method");
        }
    }
}
```

Add "overnight" shipping and you must open this class and add a branch. Add "pickup" and you
open it again. The class is never finished, and each edit risks the existing cases.

## Extension through new types

Define a contract, then add behavior as new classes that implement it:

```php
interface ShippingMethod
{
    public function cost(float $weight): float;
}

final class StandardShipping implements ShippingMethod
{
    public function cost(float $weight): float
    {
        return $weight * 1.5;
    }
}

final class ExpressShipping implements ShippingMethod
{
    public function cost(float $weight): float
    {
        return $weight * 3.0;
    }
}

final class ShippingCalculator
{
    public function cost(ShippingMethod $method, float $weight): float
    {
        return $method->cost($weight);
    }
}
```

Now "overnight" shipping is a brand new class - `OvernightShipping implements
ShippingMethod` - and `ShippingCalculator` never changes. The old, tested classes stay
closed; the system stays open to new methods.

This is exactly the shape of the **Strategy** pattern, which you'll meet later in
Chapter 6. For now,
just notice the move: behavior that varies becomes a set of interchangeable objects behind
one interface.

## Wiring it up: tagged services

Extension is only truly "closed" when adding a new type does not force you to edit the code
that *uses* the types either. Frameworks solve this with **tagged services**: every
implementation is marked with a tag, and the consumer asks the container for "all services
tagged X". Add a new class with that tag and it joins the set automatically - nothing else
changes.

In **Symfony** you tag by interface (autoconfiguration) and inject them all with a tagged
iterator:

```php
// Services implementing ShippingMethod are auto-tagged 'app.shipping_method'
// (via #[AutoconfigureTag] on the interface, or an _instanceof rule in config).

final class ShippingCalculator
{
    /** @param iterable<ShippingMethod> $methods */
    public function __construct(
        #[TaggedIterator('app.shipping_method')]
        private iterable $methods,
    ) {}
}
```

**Laravel** has the same idea in its service container - `tag()` groups bindings and
`tagged()` resolves them all:

```php
// in a service provider
$this->app->tag(
    [StandardShipping::class, ExpressShipping::class],
    'shipping_methods',
);

// wherever you need them
$methods = $this->app->tagged('shipping_methods'); // iterable of instances
```

Either way a new `OvernightShipping` is one new class plus a tag - the calculator, the
provider, and every existing method stay untouched. This is how you build plugin-style,
extensible apps that follow OCP end to end. (The container itself is covered in Chapter 7;
the pattern behind these interchangeable classes is Strategy, in Chapter 6.)

## A worked example: pick a handler by context

The everyday version of this in Laravel is a set of handlers that each answer two questions:
"do I apply here?" and "then do the work". Give the interface a `supports()` check and a
method that runs the logic - here `pay()`:

```php
interface PaymentHandler
{
    public function supports(Order $order): bool;

    public function pay(Order $order): void;
}

final class CardPayment implements PaymentHandler
{
    public function supports(Order $order): bool
    {
        return $order->method === 'card';
    }

    public function pay(Order $order): void
    {
        // charge the card
    }
}

final class PaypalPayment implements PaymentHandler
{
    public function supports(Order $order): bool
    {
        return $order->method === 'paypal';
    }

    public function pay(Order $order): void
    {
        // call the PayPal API
    }
}
```

Tag the handlers in a service provider, then hand them to a processor:

```php
// AppServiceProvider::register()
$this->app->tag([CardPayment::class, PaypalPayment::class], 'payment_handlers');

$this->app->bind(PaymentProcessor::class, fn ($app) =>
    new PaymentProcessor($app->tagged('payment_handlers'))
);
```

The processor walks the list and runs the first handler whose `supports()` matches the
current context:

```php
final class PaymentProcessor
{
    /** @param iterable<PaymentHandler> $handlers */
    public function __construct(private iterable $handlers) {}

    public function process(Order $order): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($order)) {
                $handler->pay($order);
                return;
            }
        }

        throw new \RuntimeException("No payment handler for: {$order->method}");
    }
}
```

Now add Apple Pay: write `ApplePayPayment implements PaymentHandler`, add it to the tag, and
you are done. `PaymentProcessor` - the class that actually runs the logic - never changes.
The condition that used to sit in a `switch` now lives inside each handler's `supports()`,
where it can be tested and extended in isolation. That is the Open/Closed Principle doing
real work.

## Common mistake: designing for OCP too early

Trying to make everything open/closed up front, guessing at every future extension point.
That's [YAGNI](/course/design-patterns/core-principles/yagni) turned inside out - you build
flexibility no one needs. OCP pays off when a class has already changed for the same reason
two or three times. That repetition tells you where an extension point actually belongs.

## Common mistake: keeping the switch behind the interface

Adding the interface but keeping the switch - for example, a factory that still does
`switch ($method)` to pick the class. Sometimes that's fine, but if the mapping itself keeps
changing, push the decision to the edge (configuration or a registry) so the core logic
stays closed.

## FAQ

### What is the Open/Closed Principle?

It's the idea that you should add new behavior by writing new code, not by modifying
existing, working code. Classes are "closed" to changes but "open" to being extended with
new types.

### How does OCP relate to the Strategy pattern?

Strategy is a concrete way to achieve OCP: you put each varying behavior in its own class
behind a shared interface, then swap them in. Adding a behavior means adding a class, not
editing one.

### Is a switch statement always a violation of OCP?

No. A short switch that never changes is fine. It only becomes a problem when you keep
reopening it to add cases - that repeated modification is the smell OCP addresses. One useful
middle ground in PHP: a `match` over a backed enum. When the set of cases is genuinely fixed,
static analysers like PHPStan flag any enum case you forgot to handle, so the "switch" fails
loudly at analysis time instead of silently at runtime. Reach for polymorphism when the set
keeps growing; a checked `match` is enough when it doesn't.
