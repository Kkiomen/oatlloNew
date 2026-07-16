---
title: "What is software architecture?"
slug: what-is-software-architecture
seo_title: "What Is Software Architecture? A Beginner's Guide"
seo_description: "What is software architecture? The high-level structure of a system and the decisions that are expensive to change - and how it differs from design patterns."
---

**Software architecture** is the high-level structure of a system, plus the decisions
that are expensive to change later. It is the shape of the whole, not the details of any
one class.

## Working code is not enough

You already know how to make a feature work. You can write a class, apply a design
pattern, and ship. At that scale the questions are small: which method goes where, what
to name a variable.

Architecture asks bigger questions. How is the system split into parts? Which part is
allowed to know about which other part? Where does the database live, and what happens
when you want to swap it? These decisions don't change a single feature. They shape every
feature for years.

## The test: what is expensive to change?

Here is a practical way to tell architecture from ordinary code.

Renaming a method is cheap. You do it in a minute, and if you get it wrong, you undo it.
That is not architecture.

Deciding that your whole application talks to the database directly from the controllers
is expensive. Once a hundred features are built that way, changing it means touching all
hundred. That decision *is* architecture, whether you made it on purpose or by accident.

Here's the tell from real projects: you rarely feel an architectural decision when you make
it. You feel it months later, the first time you try to change it and find a hundred call
sites standing in the way.

> Architecture is the set of decisions you wish you could get right early, because
> getting them wrong is costly to undo.

## A small picture

Think of a house. Architecture is where the load-bearing walls go, where the plumbing
runs, how many floors there are. The paint colour and the furniture are not architecture.
You repaint a wall in an afternoon; you don't move a load-bearing wall without a serious,
expensive project.

```text
Architecture  ->  walls, floors, plumbing   (costly to change)
Everyday code ->  paint, furniture, posters (cheap to change)
```

Same building, two very different levels of decision.

## How it differs from design patterns

If you've studied design patterns, this is the key distinction.

A **design pattern is local.** Strategy, Observer, Factory - each solves a problem inside
a small neighbourhood of classes. You apply it in one file or a handful of related ones,
and the rest of the system doesn't need to know.

**Architecture is system-wide.** It answers how the big pieces are arranged and how they
depend on each other across the entire codebase. A layered structure, a set of services,
a domain kept separate from the framework - these are decisions that touch everything.

A simple way to hold it: **patterns organise classes, architecture organises the whole
system.** Patterns live inside the boxes; architecture decides what the boxes are and how
they connect.

Neither is more important. You need both. But they operate at different scales, and
confusing them leads to two classic mistakes: trying to solve a system-wide problem with a
single clever class, or drowning a small problem in system-wide structure.

## Common mistake: thinking architecture means "big and complex"

Beginners often assume architecture only matters for huge systems with many teams. Not so.
Every application has an architecture, even a small one. The only question is whether you
chose it on purpose or let it happen by accident.

A tidy, deliberate structure in a small app is good architecture. A tangled mess in a
large app is bad architecture. Size doesn't decide quality; intention does.

## FAQ

### What is the difference between software architecture and design?

Design usually means the smaller scale - how you shape classes and methods. Architecture
is design at the big scale: how whole modules, layers and services fit together, and which
decisions are expensive to reverse. They're the same craft at different zoom levels.

### Do small projects need architecture?

Every project already has one. A small app just needs a simple, deliberate structure, not
a heavy one. Good architecture at small scale means clear boundaries, not many of them.

### Is architecture the same as design patterns?

No. A design pattern is a local solution inside a few classes. Architecture is the
system-wide arrangement of the big pieces and how they depend on each other. You use both,
but at different scales.
