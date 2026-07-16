---
title: "Adapter - Make an Incompatible Class Fit"
slug: adapter
seo_title: "Adapter Pattern in PHP: Fit an Incompatible Class"
seo_description: "Learn the adapter pattern: wrap a class with a mismatched interface so it fits the one your code expects. Adapt a payment gateway in PHP."
---

## What is the adapter pattern?

The **adapter** pattern wraps a class that has the wrong interface so it fits the one your
code already expects. It's the software version of a travel plug adapter: the socket and
the plug don't match, so you drop a small piece in between. In PHP this "small piece" is a
class that implements your interface and forwards each call to the mismatched one.

## The problem it solves

Your code talks to payments through a small interface you own:

```php
interface PaymentGateway
{
    public function charge(int $amountInCents, string $currency): string;
}
```

Everything in your app depends on `PaymentGateway`, not on any particular vendor. That's
the [dependency inversion](/course/design-patterns/solid/dependency-inversion) idea from
the SOLID chapter in action.

Now you sign up with a payment provider, and their SDK looks nothing like your interface:

```php
final class AcmePay
{
    // Third-party class you cannot change.
    public function makePayment(float $dollars, string $isoCode): AcmeResult
    {
        // ...talks to Acme's API...
        return new AcmeResult(reference: 'acme_9f3a');
    }
}
```

It takes dollars as a float, not cents as an int; its method is `makePayment`, not
`charge`; and it returns an `AcmeResult` object, not a string. You can't edit their class,
and you don't want vendor details leaking through your whole app.

## The adapter

Write a small class that implements *your* interface and translates each call to *their*
method:

```php
final class AcmePayAdapter implements PaymentGateway
{
    public function __construct(private AcmePay $acme) {}

    public function charge(int $amountInCents, string $currency): string
    {
        $dollars = $amountInCents / 100;
        $result = $this->acme->makePayment($dollars, $currency);

        return $result->reference;
    }
}
```

Your app keeps talking to `PaymentGateway`; the adapter does the translation in one place:

```php
$gateway = new AcmePayAdapter(new AcmePay());
$reference = $gateway->charge(1500, 'USD'); // "acme_9f3a"
```

If you switch providers later, you write a new adapter and change nothing else. The mess
of matching each vendor stays boxed inside one small class.

## When to use it

- You need to use a class - a third-party SDK, a legacy module - whose interface doesn't
  match what your code expects.
- You want to keep vendor-specific details out of your main code.
- You're integrating two things that were designed separately and can't be changed.

## Common mistake

An adapter should *translate*, not *decide*. If your adapter starts adding retries,
logging, discount rules or validation, it has stopped being an adapter. Keep it thin: map
names, convert units, reshape data. Extra behavior belongs in a decorator or in your own
service. One tell that you've kept it thin: the adapter needs no state of its own beyond
the wrapped object, so its unit test is just "given this vendor call, I return that shape."

## FAQ

### Adapter vs facade?

An adapter makes one class match a *specific interface you already have*, usually so it's
a drop-in replacement. A facade gives a *new, simpler* interface over a whole subsystem.
Adapter targets a required shape; facade invents a convenient one. Both get their own
lesson in this chapter, so the difference is easy to see side by side.

### Adapter vs decorator?

Both wrap an object. A decorator keeps the *same* interface and adds behavior. An adapter
*changes* the interface so an object fits where it otherwise couldn't. Different goal:
adapt to fit vs enhance in place.

### Should I write an interface just to add an adapter?

Often yes. Coding against a small interface you own (like `PaymentGateway`) is what makes
the adapter drop in cleanly. Without it, vendor types spread through your code and swapping
providers means editing everywhere.
