---
title: "Composition over inheritance"
slug: composition-over-inheritance
seo_title: "Composition over Inheritance in PHP - Why It Scales"
seo_description: "Why composition over inheritance holds up as systems grow: has-a beats deep is-a, the fragile base class problem, and a PHP example swapping one for the other."
---

"Favor **composition over inheritance**" is one of the most repeated pieces of design
advice, and this lesson shows why it holds. Both are ways to reuse code, but they build very
different shapes. Inheritance says a class **is a** kind of another class. Composition says a
class **has a** collaborator it delegates work to. As systems grow, the "has-a" shape almost
always holds up better.

## The pull of inheritance

Inheritance looks like the obvious tool for reuse. You have a `Report`, you need a
`PdfReport`, so you extend it. Need styling? Add `StyledPdfReport`. Need caching? A few
levels down you have a tall tower of classes, and behavior for any one object is smeared
across the whole chain. To understand a leaf class you now have to read every parent above
it.

## The fragile base class

The deeper problem is coupling. A subclass depends on the *internals* of its parent, not
just its public surface. Change the base class and you can silently break subclasses that
looked completely unrelated. This is the **fragile base class** problem: the base can't be
edited safely because you can't see, from up there, everything the descendants below rely on.

Consider a base class where one method quietly calls another:

```php
class Collection
{
    protected array $items = [];

    public function add(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function addMany(array $items): void
    {
        foreach ($items as $item) {
            $this->add($item);   // relies on add()
        }
    }
}

class CountingCollection extends Collection
{
    public int $count = 0;

    public function add(mixed $item): void
    {
        parent::add($item);
        $this->count++;
    }
}
```

`CountingCollection` looks correct. But its count only stays right because `addMany` happens
to route through `add`. If a maintainer "optimizes" the base `addMany` to append directly to
`$this->items`, the subclass's count silently goes wrong - and nothing in the subclass
changed. The subclass depended on a private habit of the parent.

Notice what `protected $items` really did here. The moment a base class exposes a member as
`protected`, that member is part of a contract - not with the outside world, but with every
subclass that will ever extend it. You can rename a `private` field freely; rename a
`protected` one and you may break descendants you've never seen. Inheritance quietly turns
internal details into public API pointed at your own subclasses.

## The composition version

Instead of *being* a collection, *have* one:

```php
final class CountingCollection
{
    private array $items = [];
    public int $count = 0;

    public function add(mixed $item): void
    {
        $this->items[] = $item;
        $this->count++;
    }

    public function addMany(array $items): void
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }
}
```

Now this class owns its own behavior. It doesn't inherit anyone's internal habits, so nothing
in a distant parent can break it. It exposes only what it chooses to. If it needed a real
collection to store data, it would hold one as a private property and call its public methods
- a stable contract, not a shared inside.

## Why composition scales

Inheritance locks you into one axis of variation, fixed at class-definition time. Composition
lets you assemble behavior from small, independent parts and swap them out - even at runtime.
That flexibility is the engine behind many of the design patterns later in this course; the
strategy pattern, which you'll meet in a later chapter, is composition in its purest form.
You depend on a collaborator's public contract, not its guts, so each piece can change
without shattering the others - a direct payoff of the low coupling from
[chapter one](/course/design-patterns/why-design-matters/coupling-and-cohesion).

## Common mistake

The mistake is reaching for `extends` as the default reuse tool. Before you subclass, ask: is
this really an **is-a** relationship, or do I just want to reuse some code? If it's the
latter, hold the other object as a collaborator and delegate. Reserve inheritance for genuine
"is-a" cases with a small, stable base you fully control.

## FAQ

### Is inheritance always bad?

No. Inheritance is fine for true "is-a" relationships with a shallow, stable base - and
extending framework base classes is common and reasonable. The advice is to *favor*
composition, not to ban inheritance. Trouble starts with deep hierarchies and subclasses that
lean on their parent's internals.

### What does "has-a" actually look like in code?

A class holds another object as a property and calls its methods to get work done, instead of
extending it. The held object is a collaborator with a public contract. You can even pass it
in through the constructor, which lets you swap it for a different one later.

### How deep is "too deep" for an inheritance chain?

There's no hard number, but each level you add makes any single class harder to understand,
because its behavior is spread across every parent. If you're three or more levels deep, or
you find yourself reading parents to understand a child, that's a strong signal to switch to
composition.
