---
title: "What are design patterns?"
slug: what-are-design-patterns
seo_title: "What Are Design Patterns? A Beginner's Guide"
seo_description: "Learn what software design patterns are, where the Gang of Four came from, and the three categories: creational, structural and behavioral."
---

A **design pattern** is a named, reusable solution to a problem that keeps coming up when
you design software. It's not code you copy and paste - it's a proven shape for solving a
recurring problem, described so any developer can recognize and reuse it.

## The problem patterns solve

You've already met problems that show up again and again: code that's too tightly coupled,
a class doing too much, an object that's painful to build. Over decades, developers
noticed that the *good* solutions to these problems tend to look similar. Instead of
reinventing that solution every time, someone gave it a name and wrote down when and how
to use it. That's a design pattern.

The famous catalog comes from a 1994 book, *Design Patterns*, by four authors nicknamed
the **Gang of Four** (often shortened to "GoF"). They described 23 patterns that keep
appearing in well-designed object-oriented systems. Most of the patterns you'll hear about
at work come from that book.

## Patterns are vocabulary, not copy-paste code

The biggest value of a pattern is the shared name. When a teammate says "let's use a
**factory** here" or "that's a **strategy**", they've communicated a whole design idea in
one word - and you both know roughly what the code will look like. Patterns are a
*vocabulary* for talking about design.

That also means a pattern is not one exact snippet. The same pattern looks a little
different in every project, because it adapts to your classes, your names, and your needs.
Learn the *intent* - the problem it solves and the shape of the answer - not a fixed block
of code.

## What are the three types of design patterns?

The Gang of Four sorted their patterns into three groups, based on what each pattern is
about:

- **Creational** - about *creating objects*. How do you build an object without hard-wiring
  the exact class, or without a giant, error-prone constructor? This chapter covers these:
  factory method, abstract factory, builder, singleton and prototype.
- **Structural** - about *composing objects* into bigger structures. How do you make two
  incompatible classes work together, or add behavior without touching the original? (You'll
  meet adapter, decorator, facade and more in the next chapter.)
- **Behavioral** - about *how objects talk to each other* and split responsibilities. How do
  you swap an algorithm at runtime, or notify many objects when one changes? (Strategy,
  observer, command and others come later.)

## A common mistake

The most common beginner mistake is treating patterns as a goal. They aren't. A pattern is
a tool for a specific problem; using one where the problem doesn't exist just adds
complexity. The principles from the earlier chapters -
[KISS](/course/design-patterns/core-principles/kiss) and
[YAGNI](/course/design-patterns/core-principles/yagni) - still win. Reach for a pattern
when you feel the pain it removes, not because the name sounds professional.

## When to use them

Learn the patterns so you can *recognize* them - in this course, in framework source code,
and in your teammates' pull requests. Reach for one when you hit the exact problem it
solves. A note from experience: you almost never sit down deciding "I'll apply a design
pattern today." You write straightforward code, feel a specific pain, and realize the
shape that removes it already has a name. That recognition is the whole point. The rest of
this chapter walks through the creational patterns one at a time, each with the problem
first and then a small PHP example.

## FAQ

### Do I have to memorize all 23 patterns?

No. Aim to recognize the common ones and understand what problem each solves. Once you know
the intent, you can look up the details when you actually need them.

### Are design patterns specific to PHP?

No. The Gang of Four patterns apply to any object-oriented language - Java, C#, Python, PHP
and others. We use PHP for the examples, but the ideas transfer directly.

### Are patterns still relevant with modern frameworks?

Very much so. Frameworks like Laravel are *built* out of patterns - you already use many
without naming them. A [later chapter](/course/design-patterns/patterns-in-the-real-world/patterns-you-already-use-in-laravel) shows exactly which ones.

### What is the difference between creational, structural and behavioral patterns?

They answer three different questions. Creational patterns deal with *making* objects
(factory method, builder, singleton). Structural patterns deal with *arranging* objects
into larger structures (adapter, decorator, facade). Behavioral patterns deal with *how
objects communicate and divide work* (strategy, observer, command). This chapter covers the
creational group.
