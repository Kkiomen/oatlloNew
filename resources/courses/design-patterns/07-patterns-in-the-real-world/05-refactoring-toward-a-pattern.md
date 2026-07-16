---
title: "Refactoring toward a pattern"
slug: refactoring-toward-a-pattern
seo_title: "Refactoring to the Strategy Pattern in PHP"
seo_description: "A worked PHP example of refactoring to the strategy pattern: a growing if/elseif becomes clean classes, step by step. Refactor toward patterns, don't start there."
---

If patterns have to earn their place, how does one actually get *in* at the right moment? You
**refactor toward it**. Refactoring to the strategy pattern in PHP - our example here - never
begins with the pattern. It begins with the simplest code that works, and only when that code
starts to hurt do you reshape it into the strategy that removes the pain. We will walk the
full path, from a messy conditional to the
[strategy pattern](/course/design-patterns/behavioral-patterns/strategy).

## Where it starts (and that's fine)

You need shipping cost by carrier. The first version is a plain conditional, and for two
carriers this is *correct* - no pattern needed yet:

```php
class ShippingCalculator
{
    public function cost(string $carrier, float $weight): float
    {
        if ($carrier === 'inpost') {
            return 12.0;
        } elseif ($carrier === 'dhl') {
            return 18.0 + $weight * 2;
        }

        throw new InvalidArgumentException("Unknown carrier: $carrier");
    }
}
```

## The smell appears

Months later the conditional has five branches, each with its own quirks, and every new
carrier means editing this method again. That's the [code smell](/course/design-patterns/why-design-matters/what-are-code-smells)
of a growing conditional on a *type*, and it breaks the
[open/closed principle](/course/design-patterns/solid/open-closed): you keep modifying
existing code to add behavior. *Now* a pattern pays for itself.

## Step 1: name the abstraction

Each branch is one interchangeable algorithm - the definition of a strategy. Extract the
common shape into an interface:

```php
interface ShippingStrategy
{
    public function cost(float $weight): float;
}
```

## Step 2: move each branch into its own class

Each `if` branch becomes a small class implementing the interface. The logic doesn't change -
it just moves to where it belongs:

```php
class InPostShipping implements ShippingStrategy
{
    public function cost(float $weight): float
    {
        return 12.0;
    }
}

class DhlShipping implements ShippingStrategy
{
    public function cost(float $weight): float
    {
        return 18.0 + $weight * 2;
    }
}
```

## Step 3: select the strategy, then delegate

The calculator no longer contains the branches. It picks a strategy and delegates. A `match`
maps the carrier name to a class - the one place that still knows the list:

```php
class ShippingCalculator
{
    public function cost(string $carrier, float $weight): float
    {
        $strategy = match ($carrier) {
            'inpost' => new InPostShipping(),
            'dhl'    => new DhlShipping(),
            default  => throw new InvalidArgumentException("Unknown carrier: $carrier"),
        };

        return $strategy->cost($weight);
    }
}
```

Adding a carrier is now a new class plus one `match` arm - no existing *logic* touched. Be
honest about what the `match` is, though: it is still a list that grows with every carrier.
Moving it into the container (Laravel makes that easy) does not delete the list - it relocates
it to a binding. The win is not "zero edits ever"; it is that the branching lives in one small,
obvious place while the pricing logic stays sealed in classes you never reopen.

## The real lesson: direction matters

Notice what happened. You didn't *start* with an interface and three classes for two
carriers - that would have been the over-engineering from the last lesson. You started
simple, let the pain accumulate, and moved *toward* the pattern when the smell was real and
the payoff clear. That direction - simple first, pattern when it earns it - is how patterns
enter healthy codebases.

## A common mistake

Refactoring in one giant leap with no safety net. Reshaping code toward a pattern should be
a series of tiny, behavior-preserving steps, ideally with tests green the whole way. If you
rewrite everything at once, you can't tell whether the pattern or a fresh bug changed the
output. The next chapter covers these refactoring techniques in depth.

## FAQ

### When exactly should I refactor toward a pattern?

When the simple version starts to hurt: a conditional that keeps growing, logic duplicated
across files, or a change that forces edits in many places. Pain first, pattern second.

### Isn't it wasteful to write simple code I'll later replace?

No. Simple code that ships and works is never wasted, and most of it never needs replacing.
Refactoring toward a pattern is a small, safe step when the need arrives - cheaper than
guessing wrong up front.

### How do I refactor safely?

In tiny steps that don't change behavior, with tests to confirm the output stays the same
at each step. Extract the interface, move one branch, run the tests, repeat. Chapter 8 goes
deeper into these techniques.
