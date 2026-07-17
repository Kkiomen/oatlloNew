---
title: "Iterator and Mediator Patterns"
slug: iterator-and-mediator
seo_title: "Iterator Pattern in PHP - and the Mediator Pattern"
seo_description: "Learn the Iterator pattern in PHP: traverse a collection without exposing its internals, plus the Mediator pattern for taming many-to-many coupling."
---

Two more behavioral patterns, shorter than the rest. **Iterator** lets you walk through a
collection without knowing how it stores its items. **Mediator** puts a middleman between
objects so they stop referencing each other directly.

## What is the Iterator pattern?

You have a collection and you want callers to loop over it - but you don't want them poking
at the array (or linked list, or tree) inside. If callers touch the internal structure, you
can never change it. The Iterator pattern gives them a way to step through items while the
storage stays private.

PHP has this built in. Implement the `Iterator` interface, or the simpler `IteratorAggregate`,
and your object works with a normal `foreach`:

```php
final class Playlist implements IteratorAggregate
{
    private array $songs = [];

    public function add(string $song): void
    {
        $this->songs[] = $song;
    }

    public function getIterator(): Iterator
    {
        // hand back an iterator - callers never see $songs directly
        return new ArrayIterator($this->songs);
    }
}

$playlist = new Playlist();
$playlist->add('One');
$playlist->add('Two');

foreach ($playlist as $song) {
    echo $song . "\n";
}
```

Callers write plain `foreach` and never learn that `Playlist` stores an array. Swap the
array for a database cursor or a generator later and no caller changes. Using a `Generator`
(a function with `yield`) is the lightest way to build one:

```php
public function getIterator(): Generator
{
    foreach ($this->songs as $song) {
        yield $song;
    }
}
```

This keeps [encapsulation](/course/design-patterns/why-design-matters/coupling-and-cohesion)
intact: the collection controls how it's traversed, callers just consume it.

One PHP-specific catch with the generator form: a generator is one-shot. Return it from
`getIterator()` and `foreach` is fine, because `foreach` asks for a fresh iterator each time.
But if a caller grabs the generator once and loops it twice, the second loop is empty - it's
already exhausted. `ArrayIterator` rewinds; a bare generator does not. When in doubt, return a
new iterator per call rather than caching one.

## What is the Mediator pattern?

When several objects all talk to each other directly, you get a web of references - each one
knows about many others. This is the many-to-many coupling that makes a system hard to
change: touch one object and several others feel it. The Mediator pattern routes all that
communication through a central object, so peers only know the mediator.

Picture form fields that affect each other - checking a box enables a button, typing in a
field clears an error. Without a mediator, every field holds references to the others. With
one, they report to the mediator and it decides what happens:

```php
interface Mediator
{
    public function notify(object $sender, string $event): void;
}

final class SignupForm implements Mediator
{
    public function __construct(
        private Checkbox $terms,
        private Button $submit,
    ) {}

    public function notify(object $sender, string $event): void
    {
        if ($sender === $this->terms && $event === 'toggled') {
            // one place decides how peers react to each other
            $this->submit->setEnabled($this->terms->isChecked());
        }
    }
}
```

Each widget calls `$mediator->notify($this, 'toggled')` instead of reaching into its
neighbors. The rules for how components interact live in one class you can read and change,
rather than being spread across every component.

## When to use them

Reach for **Iterator** whenever a collection has non-trivial storage and you want clean
`foreach` access without leaking the structure - in PHP that usually just means implementing
`IteratorAggregate`. Reach for **Mediator** when a group of objects has grown tangled with
direct references to each other; the mediator trades many-to-many wiring for a single hub.
Don't add a mediator to two objects that talk cleanly - it only pays off once the web is
real.

## Common mistake

For Iterator, hand-rolling traversal when PHP already offers `IteratorAggregate`,
`ArrayIterator` and generators - reinventing it is wasted effort and more bug surface. For
Mediator, letting the hub swell into a god object that knows every rule in the system.
A mediator should coordinate a defined group of peers, not become the place where all logic
ends up.

## FAQ

### What is the difference between the mediator and observer pattern?

[Observer](/course/design-patterns/behavioral-patterns/observer) is one-to-many and one-directional: a subject broadcasts to listeners that don't
talk back through it. Mediator is many-to-many and bidirectional: peers send messages to the
hub and the hub coordinates them in every direction. Observer decouples an event from its
reactions; Mediator decouples peers from each other.

### Do I ever need to implement the full Iterator interface?

Rarely. `IteratorAggregate` with `ArrayIterator` or a generator covers almost every case and
is far less code. Implement the full `Iterator` (`current`, `key`, `next`, `rewind`, `valid`)
only when you need fine control over traversal state that a generator can't express cleanly.

### Isn't a mediator just another form of coupling?

The peers do couple to the mediator - but that replaces many-to-many links with many-to-one,
which is far easier to reason about and change. The risk is the mediator growing too big; keep
its scope to one cohesive group of collaborators and it stays a net win.
