---
title: "How to read this course"
slug: how-to-read-this-course
seo_title: "Software Architecture: Principles vs Styles & Trade-offs"
seo_description: "Get the most from this software architecture course: principles vs styles, why examples are PHP and Laravel, and why architecture is trade-offs, not rules."
---

Before the real material starts, here's how this software architecture course is built -
and how *not* to read it, which is where most people trip. Two kinds of idea run through
every chapter, and telling them apart saves you from the usual traps.

## Principles vs styles

The course is built around two kinds of ideas, and it helps to keep them apart.

**Principles guide.** A principle is a general truth about good structure - things you've
already begun meeting, like "keep coupling low" or "don't let the domain depend on
infrastructure". Principles don't tell you exactly what to build. They tell you which
direction is better, at any scale, in any style.

**Styles are shapes.** A style is a concrete, named way to structure a system - layered,
hexagonal, event-driven, microservices. Styles are recipes: recognisable shapes you can
choose and apply. The [map lesson](/course/software-architecture/what-is-software-architecture/a-map-of-architectural-styles) named them; later chapters teach them one by one.

A simple way to hold it: **principles are values, styles are shapes.** You learn the values
first so that when you reach the shapes, you can judge which one earns its place.

## The examples are PHP and Laravel, the ideas are not

Code examples in this course are written in modern **PHP 8.4**, and where a framework helps,
in **Laravel** - because concrete code teaches better than pseudocode, and Laravel is a
system many readers already know.

But the *ideas* are not about PHP or Laravel. Boundaries, coupling, the domain-versus-
infrastructure split, layering, hexagonal, event-driven design - these apply just as much in
Java, C#, Python, Go or TypeScript. If you work elsewhere, read the PHP as a clear
illustration and carry the idea across. Only the syntax is language-specific; the structure
is universal.

We'll even make a point, in a later chapter, of [keeping the domain *framework-free*](/course/software-architecture/putting-it-together-in-laravel/keeping-the-domain-framework-free) - so the
Laravel in these examples is the servant, never the owner, of the architecture.

## Architecture is trade-offs, not rules

This is the single most important thing to take from this lesson.

There is no architecture that is simply "correct". Every style buys you something and
charges you for it. Layering is simple but can feel rigid. Microservices give teams
independence and hand you a distributed system to operate. Hexagonal protects your domain
and asks you to write more interfaces. **Every choice is a trade.**

So when this course teaches a style, it will teach the cost as well as the benefit. And when
you design something yourself, the honest question is never "what's the right architecture?"
It's:

- What does this choice make easy?
- What does it make hard?
- Is that trade worth it *for my problem, my team, my likely changes?*

Of those three, the second is the one people skip. Every proposal - a library, a pattern, a
whole architecture - advertises what it makes easy and stays quiet about the cost. The
fastest way to sound like you've done this before is to ask, out loud, what a design makes
*harder*. There's always an answer.

Beginners often want a checklist of rules to always follow. Architecture doesn't work that
way. The skill you're building is judgement - the ability to weigh a trade-off on purpose,
not a set of commandments to obey.

## How the chapters build

Each chapter assumes the ones before it, and we never use a concept before it's been taught.
This foundation chapter comes first for a reason: what architecture is, boundaries and
coupling, domain versus infrastructure, and the layered structure are the yardsticks you'll
use to judge every style that follows.

Read in order, type the examples out yourself, and don't rush ahead to microservices or
event sourcing. The plain ideas you're meeting now are what make the advanced chapters
click.

## FAQ

### What is the difference between an architectural principle and a style?

A principle is a general guideline that points toward good structure at any scale (like
"keep coupling low"). A style is a concrete, named way to shape a whole system (like layered
or hexagonal). Principles guide judgement; styles give you a shape to apply.

### Do I need to know Laravel to take this course?

No. The examples use PHP and sometimes Laravel because they're concrete and widely known,
but the ideas apply in any language or framework. Read the code as illustration and the
concepts transfer directly.

### Is there one correct software architecture?

No. Every architecture trades some benefits for some costs. The right choice depends on your
problem, team and expected changes. This course teaches you to weigh those trade-offs on
purpose rather than follow fixed rules.
