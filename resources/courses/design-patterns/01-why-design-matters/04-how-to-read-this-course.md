---
title: "How to read this course"
slug: how-to-read-this-course
seo_title: "Design Principles vs Design Patterns: How to Read This Course"
seo_description: "The difference between design principles and design patterns, why the examples are PHP, and why patterns are tools, not goals. Read this before you dive in."
---

Before the real material starts, a short map. Knowing how the pieces fit - and how *not*
to use them - will save you from the most common beginner traps.

## Principles vs patterns

The course is built around two kinds of ideas, and it helps to keep them separate.

**Principles guide.** A principle is a general rule of thumb for good design - things
like "keep it simple" or "a class should have one responsibility". Principles don't tell
you exactly what to write. They tell you which direction is better. We cover these first,
in the chapters on core principles and [SOLID](/course/design-patterns/solid/what-is-solid).

**Patterns are named solutions.** A design pattern is a proven, named way to solve a
specific, recurring problem - for example, "I need to swap out an algorithm at runtime".
Patterns are more concrete than principles: they have a shape you can recognise and
reuse. We cover these in the later chapters.

A simple way to hold it: **principles are values, patterns are recipes.** You learn the
values first so that when you reach the recipes, you can tell a good use from a bad one.

## The examples are PHP, the ideas are not

Every code example in this course is written in modern **PHP 8.4**, because concrete code
teaches better than pseudocode. You'll see typed properties, constructor promotion,
interfaces and enums.

But the *ideas* are not about PHP. Coupling, cohesion, SOLID and the Gang of Four
patterns apply to Java, C#, Python, TypeScript and any other object-oriented language.
If you work in a different language, read the PHP as a clear illustration and carry the
idea across. Only the syntax is language-specific.

## Patterns are tools, not goals

This is the single most important thing to take from this lesson.

The goal is never "use a pattern". The goal is clean, readable, changeable code - the
qualities from the first lesson. Patterns are just tools that sometimes help you get
there.

Beginners who just learned patterns often do the opposite: they hunt for places to apply
a Factory or a Strategy to prove they know it. The result is code buried in indirection
that a plain function would have handled. That's called over-engineering, and it's a real
cost.

Notice what it costs, in the terms you've already met. A pattern forced where it isn't
needed usually *lowers* cohesion and *raises* coupling - the very things you're trying to
improve. It scatters one simple idea across three files, and now those files depend on
each other for no reason. A misplaced pattern doesn't just fail to help; it manufactures
the smell it was supposed to prevent.

So the honest rule is:

- Reach for a pattern when you feel the problem it solves.
- Not because the pattern is clever.
- The simplest thing that works and reads well usually wins.

We'll return to this directly in the chapter on patterns in the real world, in the lesson
on [when *not* to use a pattern](/course/design-patterns/patterns-in-the-real-world/when-not-to-use-a-pattern).

## How the chapters build

Each chapter assumes the ones before it. We never use a concept before it's been taught.
Principles come before patterns because they're the yardstick you'll use to judge whether
a pattern is earning its place.

Read in order, type the examples out yourself, and don't rush to the patterns. The
foundation you're building now - readability, coupling, cohesion, a nose for smells - is
what makes the rest click.

## FAQ

### What is the difference between a design principle and a design pattern?

A principle is a general guideline that points you toward good design (like "keep it
simple"). A pattern is a specific, named solution to a recurring problem (like Strategy or
Observer). Principles guide judgement; patterns give you a concrete shape to reuse.

### Do I need to memorise all the design patterns?

No. It's far more useful to recognise the *problems* patterns solve than to memorise
names. Once you feel the problem, you can look up the matching pattern. Understanding
beats recall.

### I don't use PHP - is this course still for me?

Yes. The examples are PHP, but coupling, cohesion, SOLID and the patterns apply in any
object-oriented language. Read the syntax as illustration and the concepts will transfer.
