---
title: "Core refactoring techniques"
slug: refactoring-techniques
seo_title: "PHP Refactoring Techniques - Extract Method & More"
seo_description: "Concrete PHP refactoring techniques - Extract Method, Extract Class, Rename, Parameter Object, Replace Conditional with Polymorphism - in small, safe steps."
---

**Refactoring** means changing the *structure* of code without changing what it *does*. The
behavior stays identical; only the design improves. The PHP refactoring techniques in this
lesson are the named moves that make that possible - each one small enough to verify on its own.
That "without changing behavior" part is the whole discipline: you make one move, check, then
make the next, so you always know whether you broke something and which step did it.

## Refactor in small, safe steps

The golden rule: **one small change at a time, with a way to check you didn't break anything.**
That check is ideally a test suite. If you have tests, run them after every step; green means
keep going, red means undo the last move. If you don't have tests yet, write a couple that pin
down the current behavior *before* you touch the code - then you're refactoring, not guessing.

Never mix refactoring with adding features. Change the shape, verify, commit - *then* add the
feature on the clean structure. Each of the techniques below is one such move.

## Extract Method

The most common refactoring. A long method does several things; you pull one chunk into a new,
well-named method.

```php
// Before: a comment is a sign a block wants its own name
public function checkout(Order $order): void
{
    // apply loyalty discount
    if ($order->customer->isLoyal()) {
        $order->total = $order->total * 0.9;
    }

    $this->gateway->charge($order->total);
}
```

```php
// After: the block becomes a named method
public function checkout(Order $order): void
{
    $this->applyLoyaltyDiscount($order);
    $this->gateway->charge($order->total);
}

private function applyLoyaltyDiscount(Order $order): void
{
    if ($order->customer->isLoyal()) {
        $order->total = $order->total * 0.9;
    }
}
```

The method now reads like a summary. Extract Method is the fix for the long-method smell and
the first tool for taming [spaghetti code](/course/design-patterns/refactoring-and-anti-patterns/anti-patterns).

## Extract Class

When a class has grown two responsibilities, move one set of fields and methods into a new
class. This is
[Single Responsibility](/course/design-patterns/solid/single-responsibility) applied as a
mechanical move, and the cure for the large-class smell and
[data clumps](/course/design-patterns/refactoring-and-anti-patterns/code-smells-catalog). The
[case study](/course/design-patterns/refactoring-and-anti-patterns/a-refactoring-case-study)
does this repeatedly.

## Rename

The cheapest high-value refactoring: give a variable, method or class a name that says what it
means. `$d` becomes `$daysUntilExpiry`; `handle()` becomes `chargeAndNotify()`. Good names
remove the need for comments and make every other refactoring easier to reason about. Modern
IDEs rename safely across the whole project in one action.

One caveat that bites in PHP specifically: the IDE only renames what it can *see* as a symbol.
A method reached by name through a string - a container binding, a route action, a magic
`__call`, an array key that mirrors a property - stays behind. After a rename, grep the old
name once before you commit; the refactor that "changed nothing" but broke a route resolved at
runtime almost always got here.

## Introduce Parameter Object

When the same group of parameters travels together, or a method has a long parameter list, wrap
them in a small object.

```php
// Before: four loose parameters, easy to pass in the wrong order
public function ship(string $street, string $city, string $zip, string $country): void
```

```php
// After: one cohesive object
final class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $zip,
        public string $country,
    ) {}
}

public function ship(Address $address): void
```

The signature is clearer, the arguments can't be swapped by mistake, and `Address` gives the
concept a home for its own rules - the fix for a long parameter list and data clumps.

## Replace Conditional with Polymorphism

When a `switch` or `if/elseif` chain branches on a type or a mode, and the same shape of
conditional appears in several places, replace it with polymorphism.

```php
// Before: the same switch shows up wherever shipping is calculated
$cost = match ($method) {
    'standard' => $weight * 1.0,
    'express'  => $weight * 2.5,
    'courier'  => $weight * 4.0,
};
```

```php
// After: one class per case, behind a shared interface
interface ShippingMethod
{
    public function cost(float $weight): float;
}

final class Express implements ShippingMethod
{
    public function cost(float $weight): float
    {
        return $weight * 2.5;
    }
}
// ...one class per method, chosen once
```

Now adding a shipping method means adding a class, not editing every switch - the
[open/closed principle](/course/design-patterns/solid/open-closed) in action. This refactoring
is how you arrive at the
[Strategy pattern](/course/design-patterns/behavioral-patterns/strategy), and it's the cure for
the shotgun-surgery smell.

## Replace Magic Number with Constant

A bare literal like `0.9` or `14` in the middle of code is a **magic number**: it carries no
meaning and, if repeated, drifts. Give it a name.

```php
// Before
$order->total = $order->total * 0.9;

// After
private const LOYALTY_DISCOUNT = 0.10;

$order->total = $order->total * (1 - self::LOYALTY_DISCOUNT);
```

The constant documents intent and creates the single source of truth
[DRY](/course/design-patterns/core-principles/dry) asks for. The same applies to magic strings
like status codes.

## Putting it together

These moves are the vocabulary of refactoring. On their own each is tiny; combined, in small
verified steps, they turn tangled code into clean design. The final lesson shows exactly that -
a
[full case study](/course/design-patterns/refactoring-and-anti-patterns/a-refactoring-case-study)
that walks one ugly service through several of these techniques from before to after.

## FAQ

### Do I really need tests before refactoring?

You need *some* way to confirm behavior didn't change. Automated tests are the best; for a
quick local change, running the code and checking output can do. The danger is refactoring blind
and silently changing behavior - "refactoring" that breaks something isn't refactoring, it's
just editing.

### How big should a refactoring step be?

Small enough that if it goes wrong, you know exactly which move caused it. One Extract Method,
verify, then the next. Big-bang rewrites where everything changes at once are the opposite of
refactoring and the usual way projects get worse, not better.

### When should I stop refactoring?

When the code is clear enough for the change you actually need to make. Refactoring serves a
goal - readability, or making a feature easy to add. It's not a hunt for perfection;
[YAGNI](/course/design-patterns/core-principles/yagni) applies to cleanup too.
