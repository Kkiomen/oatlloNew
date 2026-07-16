---
title: "When not to use a pattern"
slug: when-not-to-use-a-pattern
seo_title: "When Not to Use a Design Pattern (Avoid Overuse)"
seo_description: "Knowing when not to use a design pattern beats knowing the patterns. Avoid over-engineering, the golden hammer, and speculative abstraction in your code."
---

You now know a lot of patterns, and that is quietly dangerous. A fresh tool in hand makes
every problem start to look like a job for it. Consider this lesson the counterweight. Most
of the time, the right move is *not* to reach for a pattern - and knowing when to hold back is
a sharper skill than knowing the patterns themselves.

## Every pattern adds indirection

A pattern always costs something. It adds a layer - an interface, an extra class, one more
hop before you reach the code that does the work. That indirection is the *price* you pay
for a benefit like flexibility or decoupling. The rule is simple:

> A pattern must pay for itself. If the benefit is smaller than the added complexity, skip
> the pattern.

When a factory just calls `new`, or a strategy has one implementation, or a repository
forwards to Eloquent, you have paid the price and bought nothing.

The cost is not only the extra file, either. Every layer is one more stop your debugger steps
through, and an interface with a single implementer quietly breaks "go to definition" - your
editor jumps to the interface, never the code that runs. Indirection you did not need makes
the codebase slower to *read*, which is where developers spend most of their time.

## Over-engineering and the golden hammer

**Over-engineering** is building for problems you don't have. A tiny script grows five
interfaces, an abstract factory and an event system - for a job a plain function would do.

The **golden hammer** is its cause: "when all you have is a hammer, everything looks like a
nail." You just learned strategy, so now every `if` looks like it needs strategy. You're
solving for the pattern, not the problem. The fix is to start from the problem and ask
whether it's actually painful yet.

```php
// Golden hammer: a "strategy" with exactly one strategy
interface TaxStrategy { public function rate(): float; }
class PolishTax implements TaxStrategy { public function rate(): float { return 0.23; } }

// The whole thing you needed:
$rate = 0.23;
```

## Speculative abstraction

The most seductive trap is **speculative abstraction**: adding flexibility for a future that
may never come. "We might support other payment providers one day, so let's build the
interface now." Maybe you will - but you're paying today, in real complexity, for a maybe.

This is exactly what [YAGNI](/course/design-patterns/core-principles/yagni) - *you aren't
gonna need it* - warns against. The honest move is to build for today's requirement and add
the abstraction when the second case *actually arrives*. Adding it later is cheap; the
[open/closed principle](/course/design-patterns/solid/open-closed) exists so you can extend
without a rewrite when you genuinely need to.

## Prefer the simplest thing that works

[KISS](/course/design-patterns/core-principles/kiss) is the tie-breaker. When a plain
function, a match expression, or a direct Eloquent call solves the problem, that *is* the
right design - not a fallback you'll "clean up later". Simple code is not unfinished code.
A pattern is justified only when the simple version has started to hurt: duplicated logic,
a growing conditional, a change that forces edits in ten files.

## A common mistake

Adding a pattern "to be safe" or "to look professional" during a code review. Reviewers and
juniors alike reach for patterns to signal competence. It backfires: the reader now has more
indirection to trace for no benefit. The professional move is often to *remove* a layer, not
add one.

## FAQ

### How do I know if I'm over-engineering?

Ask what concrete problem the pattern solves *right now*. If the answer is "in case we need
it later" or "it's cleaner", that's a warning sign. Real problems sound like "this
conditional has changed three times this month".

### Isn't it expensive to add a pattern later?

Rarely as expensive as you fear. Refactoring toward a pattern (the next lesson) is a small,
safe step once the need is real - and far cheaper than maintaining an abstraction nobody
uses. Add it when the second case shows up.

### So should I avoid patterns?

No - use them when they pay for themselves. The point isn't "patterns are bad", it's
"patterns are a cost as well as a benefit". Reach for one when you feel the pain it removes,
and not a moment sooner.
