---
title: "A catalog of common code smells"
slug: code-smells-catalog
seo_title: "PHP Code Smells Catalog - Spot the Tell, Apply the Fix"
seo_description: "A catalog of PHP code smells - long method, god class, duplicated code, feature envy, primitive obsession - each with a one-line tell and its fix."
---

A **code smell** is a surface sign that something deeper is off. The code runs fine; that's
the trap. What it warns you about is the *next* change - the design is going to fight you. We
introduced the idea back in
[what are code smells](/course/design-patterns/why-design-matters/what-are-code-smells); this
lesson is the working catalog of PHP code smells, the one to scan when a class starts to feel
wrong under your hands. Each entry gives you a one-line **tell** you can spot in seconds, plus
a pointer to the principle or pattern that removes it.

## Long method

**Tell:** you have to scroll to read a single method, or you'd add a comment like `// now
validate` halfway down.

A method that does five things is hard to name, hard to test and hard to reuse. Break it up
with **Extract Method** (see
[refactoring techniques](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques)),
pulling each chunk into a well-named helper. This is
[KISS](/course/design-patterns/core-principles/kiss) at the method level.

## Large class / God class

**Tell:** the class has a dozen fields and unrelated method groups - "user stuff" up top,
"email stuff" below, "reporting stuff" at the bottom.

The class has taken on several jobs. Split it by responsibility using **Extract Class**,
guided by the
[Single Responsibility Principle](/course/design-patterns/solid/single-responsibility).
Its extreme form is a full anti-pattern, the
[God Object](/course/design-patterns/refactoring-and-anti-patterns/anti-patterns).

## Long parameter list

**Tell:** a method signature with five or more parameters, and callers keep passing `null`
for the ones they don't care about.

Long lists are easy to get wrong - swap two arguments of the same type and nothing complains.
Group the parameters that always travel together into a small object with **Introduce
Parameter Object**.

## Duplicated code

**Tell:** you fix a bug, then think "I've seen this exact block somewhere else."

The same knowledge lives in two places, so it drifts. Pull it into one home - a method, a
class, a constant. This is the
[DRY principle](/course/design-patterns/core-principles/dry), but remember its warning: unify
only code that changes for the *same reason*, not code that merely looks alike.

The dangerous copies aren't the identical ones. Those you find with a grep. The ones that bite
are the same rule wearing different variable names in two files - a search finds neither, and
you patch one while the other keeps the old behavior for another six months.

## Feature envy

**Tell:** a method in class A spends most of its time reaching into class B's data -
`$b->getX()`, `$b->getY()`, `$b->getZ()` - to make a decision.

The method is envious of another object's data; it probably belongs *on* that object. Move the
behavior next to the data it uses. This is the
[Law of Demeter](/course/design-patterns/core-principles/law-of-demeter) turned into a design
fix: tell the object what to do instead of pulling out its insides. A long chain of getters is
often a second, quieter tell - if nobody outside the class ever needs `getX()` once the logic
moves home, the getter was only there to feed this one envious method, and it can go too.

## Primitive obsession

**Tell:** an email is a `string`, money is a `float`, a status is an `int`, and validation for
each is copy-pasted everywhere they're used.

Important concepts are being modeled with bare primitives, so the rules about them have no
home. Wrap them in small value objects (`Email`, `Money`, `Status`) that guard their own
invariants. It removes duplication and improves
[cohesion](/course/design-patterns/why-design-matters/coupling-and-cohesion).

## Shotgun surgery

**Tell:** one small change - say, adding a payment method - forces edits in seven different
files.

Related knowledge is scattered, so a single decision is spread across the codebase. Gather it
into one place. Often the right tool is a pattern that isolates the varying part, like
[Strategy](/course/design-patterns/behavioral-patterns/strategy) or a
[factory](/course/design-patterns/creational-patterns/factory-method), so the next change
touches exactly one file.

## Data clumps

**Tell:** the same little group of variables appears together again and again - `$street`,
`$city`, `$zip` passed side by side into method after method.

If three values always travel together, they're really one concept wearing three names. Give
the clump an object (`Address`) with **Extract Class** or **Introduce Parameter Object**. This
is often the cure for a long parameter list too.

## Using the catalog

You don't fix smells for their own sake - a little smell in stable code that never changes can
be left alone. Smells earn attention when they sit in code you keep having to touch. The rest
of this chapter turns these tells into concrete moves: the named
[anti-patterns](/course/design-patterns/refactoring-and-anti-patterns/anti-patterns) they grow
into, the
[refactoring techniques](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques)
that remove them, and a
[full case study](/course/design-patterns/refactoring-and-anti-patterns/a-refactoring-case-study)
that clears several at once.

## FAQ

### Is a code smell the same as a bug?

No. A bug means the code produces wrong results. A smell means the code works but is structured
in a way that will make future changes harder or riskier. Smells predict pain; bugs are pain.

### Do I have to fix every smell I find?

No. Fix smells in code you actively change. Chasing smells in stable, rarely-touched code adds
risk for little reward - the point is easier maintenance, not a perfect score.

### How do I remember all of these?

You don't memorize the list; you learn the tells. Once "this method needs a scroll bar" or
"these three variables always travel together" jumps out at you, the matching fix follows.
