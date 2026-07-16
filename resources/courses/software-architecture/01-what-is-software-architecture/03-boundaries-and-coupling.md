---
title: "Boundaries and coupling"
slug: boundaries-and-coupling
seo_title: "Boundaries and Coupling in Software Architecture"
seo_description: "Coupling at the architecture level: draw good boundaries between modules, keep cohesion high inside and coupling low across them."
---

Coupling is how much one part of a system depends on another. You've likely met it between
classes. At the architecture level, the same idea applies to bigger parts - **modules,
layers and services** - and it's the single biggest lever over how a system ages.

## Coupling, one level up

Between two classes, coupling means one class needs to know about the other to work. Change
one and you may have to change the other.

Now zoom out. Replace "class" with "module". If your billing module reaches directly into
the tables and internals of your shipping module, the two are coupled. You can't change
shipping without risking billing. You can't understand one without the other. You can't
give them to two different people without constant collisions.

Architecture-level coupling is the same problem you already know, scaled up - and scaled
up, it hurts far more.

## Boundaries are where you cut

A **boundary** is a line you draw around a part of the system that says: inside is one
thing, outside is another, and they talk through a narrow, agreed-upon opening.

```text
   +----------------+          +----------------+
   |    Billing     |          |    Shipping    |
   |                | -- ? --> |                |
   |  (internals    |  narrow  |  (internals    |
   |   hidden)      |  opening |   hidden)      |
   +----------------+          +----------------+
```

The billing module asks shipping for something through a small, clear opening - a method,
an interface, a message. It does not reach inside. The narrow opening is the boundary.

Good boundaries are the whole game. Almost every style in this course is, at heart, a
particular way of deciding where the boundaries go and how the parts talk across them.

## Two goals: high cohesion, low coupling

Two words carry most of the wisdom here.

**Cohesion** is how well the things inside a boundary belong together. High cohesion means
a module does one clear job and everything in it serves that job. Everything about billing
lives in billing.

**Coupling** is how much boundaries depend on each other. Low coupling means a module leans
on its neighbours as little as possible, and only through their narrow openings.

The aim is always the same pairing:

- **High cohesion inside** a boundary - related things together.
- **Low coupling across** boundaries - unrelated things kept apart, talking narrowly.

Get this pair right and the system stays understandable and changeable. Get it wrong and
you get the two classic failure shapes below.

## The two failure shapes

**Low cohesion:** a module that does five unrelated things. Nobody can name it. Changing
one of its jobs risks the other four, because they're tangled together for no reason.

**High coupling:** modules so entangled that touching one breaks three others. You can't
reason about a part on its own, and every change ripples outward. This is often called a
*big ball of mud* - no real boundaries, everything reaching into everything.

Both make change expensive, which is exactly what good architecture is trying to avoid.

A word of caution the other way, though: low coupling is a goal, not an absolute. Chase it
too hard and you wrap every call in an interface and an event until the codebase is all
indirection and no thread to follow. Two things that genuinely change together are allowed
to sit close. The skill is deciding what actually belongs on opposite sides of a boundary,
not multiplying boundaries for their own sake.

## Common mistake: boundaries that leak

A boundary only helps if it's real. If your "opening" is a method that hands back the
module's raw internal objects, the outside now depends on those internals anyway - the
boundary is decorative. A good boundary hides what's behind it and exposes only what
callers actually need. If a change inside a module forces changes in its neighbours, the
boundary was leaking.

## FAQ

### What is the difference between coupling and cohesion?

Cohesion is about the inside of a boundary - how well its parts belong together. Coupling
is about the outside - how much one boundary depends on another. You want cohesion high and
coupling low.

### Why is loose coupling good in architecture?

Loosely coupled parts can be understood, changed, tested and deployed on their own. When a
change in one part doesn't ripple into others, the system stays cheap to evolve - the whole
point of thinking about architecture.

### What is a "big ball of mud"?

An informal name for a system with no real boundaries, where everything depends on
everything else. It's the natural result of high coupling and low cohesion left unchecked,
and it makes every change risky.
