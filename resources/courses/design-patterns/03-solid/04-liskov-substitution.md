---
title: "Liskov Substitution Principle (LSP)"
slug: liskov-substitution
seo_title: "Liskov Substitution Principle in PHP (LSP) Explained"
seo_description: "The Liskov Substitution Principle says a subtype must work anywhere its base type works. Learn LSP with the classic Rectangle/Square PHP example."
---

## What is the Liskov Substitution Principle?

The **Liskov Substitution Principle** is the "L" in SOLID. Named after Barbara Liskov, the
short version is:

> Objects of a subtype must be usable anywhere the base type is expected, without changing
> the correctness of the program.

In other words: if code works with a `Base`, it must keep working when you hand it any
subclass of `Base` - no surprises, no special cases. Inheritance promises "a `Square` *is a*
`Rectangle`," and LSP is what keeps that promise honest.

## The classic Rectangle/Square trap

A square is a rectangle in math class, so it's tempting to model it with inheritance:

```php
class Rectangle
{
    public function __construct(
        protected int $width,
        protected int $height,
    ) {}

    public function setWidth(int $width): void { $this->width = $width; }
    public function setHeight(int $height): void { $this->height = $height; }
    public function area(): int { return $this->width * $this->height; }
}

class Square extends Rectangle
{
    public function setWidth(int $width): void
    {
        $this->width = $width;
        $this->height = $width; // a square must stay square
    }

    public function setHeight(int $height): void
    {
        $this->width = $height;
        $this->height = $height;
    }
}
```

Now imagine code written against `Rectangle`:

```php
function stretch(Rectangle $r): void
{
    $r->setWidth(5);
    $r->setHeight(4);
    // A rectangle's area should now be 20.
    assert($r->area() === 20);
}
```

Pass a `Rectangle` and the assertion holds. Pass a `Square` and it **fails** - `setHeight(4)`
also changed the width, so the area is 16. The subclass broke an expectation the base class
set. `Square` is *not* substitutable for `Rectangle`, so this inheritance violates LSP even
though "a square is a rectangle" sounds true.

## Fixing it

The honest fix is to stop pretending one *is a* kind of the other. Model the shared idea as
a contract and let each shape be immutable:

```php
interface Shape
{
    public function area(): int;
}

final class Rectangle implements Shape
{
    public function __construct(
        private int $width,
        private int $height,
    ) {}

    public function area(): int { return $this->width * $this->height; }
}

final class Square implements Shape
{
    public function __construct(private int $side) {}

    public function area(): int { return $this->side * $this->side; }
}
```

Both are `Shape`s, both report an `area()`, and neither carries setters that the other can't
honor. This is another case where
[composition over inheritance](/course/design-patterns/core-principles/composition-over-inheritance)
leads to a cleaner design.

## Other ways subclasses break LSP

The Rectangle/Square case is famous, but subtypes break substitutability in everyday ways
too:

- **Throwing on a method the base supports.** A `ReadOnlyList` that extends `List` but throws
  from `add()` surprises any code that expected `add()` to work.
- **Strengthening what the caller must provide.** If the base accepts any string but the
  subclass rejects empty strings, callers that were fine before now fail.
- **Returning less than promised.** If the base guarantees a non-null result, a subclass that
  sometimes returns null breaks callers that never checked.

Here's the part people miss: PHP's type system already enforces half of this for you. Since
7.4 an override cannot widen a return type or narrow a parameter type - the engine rejects it
at load time. So the *signature* side of LSP is checked by the language. What it can't check
is *behaviour*: the Rectangle/Square code above compiles cleanly and still breaks, because
"setting height leaves width alone" is a promise no type annotation captures. Those are the
violations that slip through, which is exactly why they bite in production.

## Common mistake

Using inheritance for code reuse alone - "this class has methods I want, so I'll extend it."
Inheritance is a promise of substitutability, not a shortcut for sharing code. If the child
can't stand in for the parent everywhere, reach for composition instead.

## FAQ

### What is the Liskov Substitution Principle?

It says any subclass must be usable wherever its parent type is expected, without breaking
the program. If substituting the child changes correctness, the inheritance is wrong.

### Why is Square extends Rectangle a bad example of inheritance?

Because a mutable rectangle lets you set width and height independently, but a square can't
keep that promise - setting one changes the other. Code that trusts the rectangle's behavior
breaks when given a square.

### How do I avoid violating LSP?

Only use inheritance when the subclass truly behaves like the parent in every situation the
parent is used. When it doesn't, model shared behavior with an interface and use composition.
