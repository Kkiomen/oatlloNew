---
title: "What is SOLID?"
slug: what-is-solid
seo_title: "What Is SOLID? The Five OOP Principles Explained"
seo_description: "SOLID is five object-oriented design principles - SRP, OCP, LSP, ISP and DIP. Learn what each stands for and why they make code easier to change."
---

## What is SOLID?

**SOLID** is a set of five design principles for object-oriented code. The name is an
acronym, one letter per principle:

- **S** - Single Responsibility Principle (SRP)
- **O** - Open/Closed Principle (OCP)
- **L** - Liskov Substitution Principle (LSP)
- **I** - Interface Segregation Principle (ISP)
- **D** - Dependency Inversion Principle (DIP)

Robert C. Martin collected these principles in the early 2000s from ideas already circulating
in the object-oriented community. The catchy ordering came later: Michael Feathers arranged
the five into the word SOLID as a memory aid. That matters more than it looks. The sequence is
alphabetical convenience, not a ranking, so don't read "S" as more important than "D" or work
through them in order. They aren't laws, and they aren't specific to PHP - they hold in any
language with classes and interfaces.

## What problem do they solve?

Code that works is easy to write once. Code you can still change six months later is
harder. Most of the pain in a growing codebase comes from **change**: you touch one class
to add a feature and three others break, or a small tweak forces edits in ten files.

SOLID is a guide to writing code where change stays cheap. Each principle attacks a
different way that code becomes rigid and fragile.

## What each one is about, in one line

- **SRP** - a class should have one job, so it has one reason to change.
- **OCP** - you should be able to add behavior without editing existing code.
- **LSP** - a subtype must work anywhere its parent works, with no surprises.
- **ISP** - keep interfaces small, so a class isn't forced to implement methods it ignores.
- **DIP** - depend on abstractions (interfaces), not on concrete classes.

Don't worry if these feel abstract right now. Each has its own lesson with a concrete
"bad then good" example.

## Why they travel together

The five principles overlap and reinforce each other. When you split a class by
responsibility (SRP), you often end up with small interfaces (ISP). To follow open/closed
(OCP), you usually inject an abstraction (DIP) and rely on substitution working correctly
(LSP). They're less a checklist and more a single mindset: **build with change in mind,
using small pieces that depend on contracts rather than on each other's details.**

This chapter builds directly on ideas you've already met -
[coupling and cohesion](/course/design-patterns/why-design-matters/coupling-and-cohesion)
and [composition over inheritance](/course/design-patterns/core-principles/composition-over-inheritance).
SOLID is those ideas made specific.

## Common mistake

Treating SOLID as a scorecard - counting principles a class "passes" or forcing every one
onto every class. That leads to over-engineering: layers of interfaces around code that
never changes. SOLID pays off where code actually changes. In stable, simple code, a plain
class is fine. Apply the principles when you feel the pain they prevent, not before.

## FAQ

### What does SOLID stand for?

Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, and
Dependency Inversion - five principles for writing object-oriented code that's easier to
change.

### Do I have to apply all five principles at once?

No. They're guidelines, not rules. Reach for the one that fixes the problem in front of
you. They tend to appear together naturally as your design improves.

### Are SOLID principles only for PHP?

No. They apply to any object-oriented language - Java, C#, Python, TypeScript and others.
The PHP examples in this chapter just make them concrete.
