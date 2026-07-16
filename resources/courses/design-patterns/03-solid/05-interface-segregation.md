---
title: "Interface Segregation Principle (ISP)"
slug: interface-segregation
seo_title: "Interface Segregation Principle in PHP (ISP) Explained"
seo_description: "The Interface Segregation Principle says prefer many small interfaces over one fat one. Learn ISP in PHP so classes never implement methods they don't use."
---

## What is the Interface Segregation Principle?

The **Interface Segregation Principle** is the "I" in SOLID:

> No client should be forced to depend on methods it does not use.

Put simply: prefer several small, focused interfaces over one big "fat" interface that tries
to cover everything. When one interface bundles unrelated abilities, every class that
implements it is dragged into methods it has no business implementing.

## A fat interface

Here's an interface that assumes every worker can do everything an office worker can:

```php
interface Worker
{
    public function work(): void;
    public function eat(): void;
    public function attendMeeting(): void;
}
```

A human employee fits fine. But now model a robot on the same line:

```php
final class Robot implements Worker
{
    public function work(): void
    {
        // ... does real work
    }

    public function eat(): void
    {
        throw new \BadMethodCallException('Robots do not eat.');
    }

    public function attendMeeting(): void
    {
        throw new \BadMethodCallException('Robots do not attend meetings.');
    }
}
```

The `Robot` is forced to implement `eat()` and `attendMeeting()` even though they make no
sense. Those `throw`s are a warning sign: the class is depending on methods it doesn't use,
and any code that calls them on a robot breaks. (Notice this is also an LSP problem - the
robot can't safely stand in for a `Worker`.)

## Small, focused interfaces

Split the fat interface into capabilities, and let each class implement only what it can
actually do:

```php
interface Workable
{
    public function work(): void;
}

interface Eater
{
    public function eat(): void;
}

interface MeetingAttendee
{
    public function attendMeeting(): void;
}

final class HumanEmployee implements Workable, Eater, MeetingAttendee
{
    public function work(): void { /* ... */ }
    public function eat(): void { /* ... */ }
    public function attendMeeting(): void { /* ... */ }
}

final class Robot implements Workable
{
    public function work(): void { /* ... */ }
}
```

Now `Robot` only implements `Workable` - no fake methods, no exceptions. Code that just needs
something to `work()` accepts a `Workable` and doesn't care about eating or meetings. Each
client depends on exactly the slice of behavior it uses.

Two things worth carrying away here. First, ISP is really a rule about the *caller*, not the
implementer: type-hint a function against the narrowest interface it actually uses, and the
principle takes care of itself. Second, small interfaces don't force verbose call sites,
because PHP interfaces compose. If some code genuinely wants the full set, you can declare
`interface Worker extends Workable, Eater, MeetingAttendee {}` and hint against that - without
dragging `Robot` back into methods it can't honor. You get the convenience where it helps and
keep the narrow contracts where they matter.

## Common mistake

Swinging too far and making a separate interface for every single method. ISP is about
grouping methods that genuinely belong together and are used together, not about maximizing
the number of interfaces. If two methods are always implemented and called as a pair, keeping
them in one interface is fine - splitting them just adds noise.

## FAQ

### What is the Interface Segregation Principle?

It says clients shouldn't be forced to depend on methods they don't use. Instead of one large
interface, define several small ones so each class implements only the behavior it actually
supports.

### How is ISP different from the Single Responsibility Principle?

SRP is about a class having one reason to change; ISP is about interfaces staying small and
focused. They're related - a focused interface usually reflects a single responsibility - but
ISP is specifically about the contracts clients depend on.

### How do I spot a fat interface?

Look for classes that implement an interface but leave methods empty or throw exceptions from
them. That's a sign the interface bundles abilities that not every implementer has.
