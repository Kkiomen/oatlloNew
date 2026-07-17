---
title: "A map of architectural styles"
slug: a-map-of-architectural-styles
seo_title: "Architectural Styles Explained: A Map for Beginners"
seo_description: "A quick tour of common architectural styles: layered, hexagonal, event-driven and microservices, plus SOA, serverless and vertical slice."
---

There isn't one "right" architecture. There are **architectural styles** - recognisable
ways to structure a system, each with its own strengths and costs. This lesson is a map,
not a deep dive. Each style gets its own chapter later.

## Why name them at all

When you can name a style, you can talk about it, compare it, and choose it on purpose.
"We use a layered architecture with the domain kept framework-free" says more in one
sentence than a long tour of the code.

So before we go deep, here's the landscape. Read it to place things, not to master them.

## The styles this course teaches

**Layered architecture.** The classic. Code is split into horizontal layers -
presentation, application, domain, infrastructure - stacked so that each layer depends
only on the ones below it. It's the default most teams start from, and the next lesson but
one introduces it properly.

**[Hexagonal (ports and adapters)](/course/software-architecture/hexagonal-architecture/what-is-hexagonal-architecture).** Puts the business logic in the centre and pushes
everything external - the database, the web, message queues - to the edges, behind
interfaces called *ports*. The outside world plugs in through *adapters*. The goal is a
core that doesn't know or care what framework it runs in.

**[Event-driven architecture](/course/software-architecture/event-driven-architecture/what-is-event-driven-architecture).** Parts of the system communicate by emitting and reacting to
**events** ("an order was placed") rather than calling each other directly. This loosens
the coupling between parts and suits systems where many things react to one change.

**[Microservices](/course/software-architecture/monolith-and-beyond/microservices-overview).** The system is split into many small, independently deployable services,
each owning its own data and talking to the others over the network. Powerful for large
organisations, and expensive in ways that surprise people - a whole chapter is devoted to
that cost.

## Styles we'll mention but not centre on

You'll hear these names in the wild, so here's a one-line placement for each:

- **SOA (service-oriented architecture).** The older cousin of microservices - the system
  as a set of services, but usually larger and more centrally coordinated.
- **Serverless / cloud-native.** You write small functions and let a cloud platform run
  and scale them; you don't manage servers directly. More a deployment style than a way to
  shape your code, though it nudges you toward small, stateless pieces.
- **Vertical slice.** Instead of horizontal layers, you organise code by *feature* - each
  slice holds everything one feature needs, top to bottom. A useful counterpoint to
  layering, revisited later.

## A rough map

```text
        one deployable unit                many deployable units
        ---------------------------------  ---------------------------
inside  layered                            microservices
one      hexagonal (ports and adapters)    SOA
codebase vertical slice                    serverless functions
        event-driven (can be either)
```

Don't over-read this grid. Styles mix. A modular monolith can be layered inside and
event-driven between modules. The map is to orient you, not to box anything in.

## Common mistake: picking a style by fashion

Microservices are not "more advanced" than a monolith. Hexagonal is not automatically
better than layered. Each style trades one thing for another - simplicity for
flexibility, speed of development for independence of teams. The right choice depends on
your problem, your team size, and what you'll need to change later. Choosing by hype is
how teams end up with the costs of a style and none of its benefits.

One thing worth knowing early: the name a team gives its architecture is often aspirational.
"We do microservices" while every service shares one database is common - and it buys the
operational cost of many services with the coupling of one. Trust the dependency arrows in
the code, not the label on the whiteboard.

## FAQ

### Which architectural style is best?

There is no single best. Each trades simplicity against flexibility, or development speed
against independence. The best style is the one that fits your problem, team and expected
changes - a judgement you'll be able to make by the end of this course.

### Do I have to pick just one style?

No. Real systems mix styles. A single application can be layered inside, event-driven
between its parts, and deployed as one unit. The names describe tendencies, not walls.

### What is the difference between a style and a pattern?

A style shapes the whole system (layered, microservices). A pattern solves a local problem
inside a few classes. This lesson is about styles; patterns sit a level below them.
