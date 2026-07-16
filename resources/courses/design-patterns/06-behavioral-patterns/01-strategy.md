---
title: "Strategy Pattern"
slug: strategy
seo_title: "Strategy Pattern in PHP - Swap Algorithms at Runtime"
seo_description: "Learn the Strategy pattern in PHP: put each algorithm behind an interface and pick one at runtime, replacing a growing if/switch and honoring OCP."
---

## What is the Strategy pattern?

The **Strategy** pattern lets you define a family of interchangeable algorithms, put each
behind a shared interface, and choose which one to use at runtime. It's the classic cure
for a `switch` statement that keeps growing.

## The problem: a growing switch

You need to calculate shipping cost, and the method depends on the carrier. The first
version is fine, then it isn't:

```php
final class ShippingCalculator
{
    public function cost(string $carrier, float $weight): float
    {
        switch ($carrier) {
            case 'standard': return 5.00 + $weight * 0.5;
            case 'express':  return 12.00 + $weight * 1.2;
            case 'pickup':   return 0.00;
            // every new carrier means editing this method...
            default: throw new InvalidArgumentException("Unknown carrier");
        }
    }
}
```

Every new carrier forces you to reopen this class and add a branch. That's exactly what the
[Open/Closed Principle](/course/design-patterns/solid/open-closed) warns against: the class
is not closed for modification.

## The strategy version

Put each algorithm behind an interface, one class per strategy:

```php
interface ShippingStrategy
{
    public function cost(float $weight): float;
}

final class StandardShipping implements ShippingStrategy
{
    public function cost(float $weight): float
    {
        return 5.00 + $weight * 0.5;
    }
}

final class ExpressShipping implements ShippingStrategy
{
    public function cost(float $weight): float
    {
        return 12.00 + $weight * 1.2;
    }
}
```

The calculator now receives a strategy instead of a string, and never needs to change:

```php
final class ShippingCalculator
{
    public function __construct(private ShippingStrategy $strategy) {}

    public function cost(float $weight): float
    {
        return $this->strategy->cost($weight);
    }
}

$calc = new ShippingCalculator(new ExpressShipping());
echo $calc->cost(3.0); // 15.6
```

Adding a new carrier means writing a new class that implements the interface. No existing
code is touched. You can even swap the strategy mid-flight based on user choice.

## When to use it

Reach for Strategy when you have several ways to do one thing and the choice varies - by
config, by user input, by context. Sorting orders, pricing rules, payment providers,
compression formats, validation styles: all natural strategies. If there's only ever one
algorithm and no sign of a second, a plain method is simpler; don't add the interface
speculatively.

A practical bonus most tutorials skip: strategies rarely hold state, so a single instance
can be shared everywhere and registered once in the container - you don't `new` one up per
call. That also makes them trivial to swap by binding a different implementation to the
interface, no calling code touched.

## Common mistake

Making the strategy interface do too much. Each strategy should have one focused job
(`cost`, `sort`, `format`). If your interface grows five methods that not every strategy
uses, you've drifted into a mini-god-object and broken
[interface segregation](/course/design-patterns/solid/interface-segregation). Keep the
contract as small as the varying behavior.

## FAQ

### What is the difference between the strategy and state pattern?

They look identical in code - both delegate to an interface. The intent differs. Strategy
picks an algorithm from the outside and rarely changes it; the object doesn't decide. State
(later in this chapter) is about an object changing its own behavior as it moves through a
lifecycle, and states often trigger the next state.

### How is Strategy different from a simple callback or closure?

A closure is a lightweight strategy. For a one-line algorithm, passing a `callable` is
perfectly fine. Reach for a full interface and classes when strategies need names, their own
dependencies, or shared setup - anything more than a single expression.

### Doesn't Strategy just move the switch somewhere else?

Something still decides which strategy to build (often a factory or the DI container). But
that choice now lives in one place, made once, instead of being repeated inside every method
that needs the behavior - and adding a strategy no longer edits working code.
