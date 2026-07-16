---
title: "Anti-patterns and why they happen"
slug: anti-patterns
seo_title: "Software Anti-Patterns Explained - God Object & More"
seo_description: "The classic anti-patterns - God Object, Spaghetti Code, Golden Hammer, Big Ball of Mud, Copy-Paste Programming - why they emerge and what to do instead."
---

A **pattern** is a proven solution to a recurring problem. A **software anti-pattern** is the
mirror image: a solution that looks reasonable, gets reached for constantly, and reliably makes
things worse. Where a
[code smell](/course/design-patterns/refactoring-and-anti-patterns/code-smells-catalog) is a
small local warning, an anti-pattern is the large-scale shape a project settles into when those
smells are left to grow. None of the ones below arrive by decision. They accrete. Knowing them
by name is what lets you feel a project drifting toward one while the fix is still cheap.

## God Object

A single class that knows and does almost everything - `Manager`, `Helper`, `Utils`,
`Application` - with dozens of methods and fields touching every part of the system.

**Why it emerges:** it starts small and convenient. Every new feature has an obvious home
("just add it to `AppManager`"), so nobody ever creates a second class. Convenience compounds
into a monster.

**What to do instead:** apply the
[Single Responsibility Principle](/course/design-patterns/solid/single-responsibility) and
split by responsibility. This is the large-class smell taken to its limit; the fix is the same,
just bigger - **Extract Class**, repeated, until each class has one job.

The name is usually the earliest warning. A vague suffix like `Manager` or `Helper` doesn't
describe a job, so nothing you add to it ever looks out of place - which is exactly why it grows
without resistance. When you can't name a class without "and", the split is already overdue.

## Spaghetti Code

Control flow you can't follow: deeply nested conditionals, functions that jump around, shared
mutable state, no clear boundaries. Changing one part unexpectedly breaks another.

**Why it emerges:** features get bolted on under deadline with no design step. Each `if` seems
harmless on its own, but a hundred of them with no structure make an untraceable tangle.

**What to do instead:** introduce structure. Separate concerns
([separation of concerns](/course/design-patterns/core-principles/separation-of-concerns)),
flatten nesting with guard clauses and **Extract Method**, and replace sprawling conditionals
with [polymorphism](/course/design-patterns/behavioral-patterns/strategy). The
[case study](/course/design-patterns/refactoring-and-anti-patterns/a-refactoring-case-study)
untangles exactly this.

## Golden Hammer

"When all you have is a hammer, everything looks like a nail." One familiar tool - a favorite
pattern, an ORM, inheritance, an event bus - gets forced onto every problem whether it fits or
not.

**Why it emerges:** people reach for what they know. A tool that solved one problem well feels
safe, so it gets reused far past where it belongs. Freshly learned design patterns are a common
culprit - suddenly everything "needs" a factory.

**What to do instead:** choose the tool for the problem, not the habit. This is the whole point
of [when not to use a pattern](/course/design-patterns/patterns-in-the-real-world/when-not-to-use-a-pattern):
the simplest thing that works, per
[KISS](/course/design-patterns/core-principles/kiss) and
[YAGNI](/course/design-patterns/core-principles/yagni), usually wins.

## Big Ball of Mud

The whole system version of spaghetti: no discernible architecture, everything depends on
everything, and no boundary is respected. It's the most common architecture in the wild
precisely because it's what you get by default.

**Why it emerges:** growth without maintenance. Boundaries erode one shortcut at a time -
"I'll just call this directly for now" - until nothing is isolated and every change ripples
everywhere ([shotgun surgery](/course/design-patterns/refactoring-and-anti-patterns/code-smells-catalog)
at project scale).

**What to do instead:** restore boundaries deliberately. Reduce
[coupling](/course/design-patterns/why-design-matters/coupling-and-cohesion), depend on
abstractions ([dependency inversion](/course/design-patterns/solid/dependency-inversion)), and
carve out modules with clear responsibilities. Mud is reversible, but only one boundary at a
time - never a big rewrite.

## Copy-Paste Programming

Building new features by duplicating an existing block and tweaking it, over and over, until
the same logic (and the same bug) lives in twenty slightly different copies.

**Why it emerges:** it's the fastest thing in the moment. Copying feels safer than changing
shared code - until a rule changes and you have to find all twenty copies, and you miss three.

**What to do instead:** extract the shared knowledge into one place, following
[DRY](/course/design-patterns/core-principles/dry). Keep its warning in mind: unify only what
truly changes together, so you don't trade duplication for a wrong abstraction.

## The common thread

Every anti-pattern here comes from the same root: **short-term convenience with no maintenance
step**. Each individual shortcut is defensible; the damage is cumulative. That's also the good
news - the fixes are the ordinary principles and refactorings from this course, applied a little
at a time. The next lesson gives you the concrete
[techniques](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques).

## FAQ

### What's the difference between a code smell and an anti-pattern?

Scale. A smell is a small, local warning sign in one method or class. An anti-pattern is the
large-scale structure a whole class or system settles into when smells are ignored. Smells are
symptoms; anti-patterns are the disease.

### Are anti-patterns always the result of bad developers?

No. Most emerge on good teams under deadline pressure, one reasonable-looking shortcut at a
time. That's why naming them matters - it lets you spot the drift early, before any single
decision looks wrong.

### Is using a design pattern everywhere also an anti-pattern?

Yes - that's the Golden Hammer. Over-applying patterns adds complexity you don't need. The
skill is knowing [when not to use one](/course/design-patterns/patterns-in-the-real-world/when-not-to-use-a-pattern).
