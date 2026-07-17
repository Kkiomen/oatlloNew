---
title: "What is Domain-Driven Design"
slug: what-is-domain-driven-design
seo_title: "What Is Domain-Driven Design (DDD)? A Plain Intro"
seo_description: "Domain-Driven Design explained for developers: shaping software around the business domain and its language, strategic vs tactical DDD, and when it is worth it."
---

**Domain-Driven Design (DDD)** is an approach to building software where the code is
shaped around the **business domain** it serves - the real activity the software exists
to support - and around the language the people in that business actually use. It was
described by Eric Evans in his 2003 book *Domain-Driven Design*, and later expanded by
Vaughn Vernon in *Implementing Domain-Driven Design*.

The core idea is simple. The hard part of most software is not the technology, it is
understanding the business correctly. DDD puts the domain at the center and treats
learning the domain as a first-class part of the work, not something you do once and
forget.

## What "domain" means

The **domain** is the subject area your software is about. For a bank it is accounts,
transfers and interest. For a shipping company it is cargo, routes and ports. For an
online shop it is orders, products, payments and delivery.

Inside a big domain there are smaller areas called **subdomains** - for a shop, things
like catalog, checkout, shipping and invoicing. You'll meet
[subdomains](/course/software-architecture/ddd-strategic-design/subdomains) properly later in
this chapter.

The people who understand the domain deeply - not the developers - are the **domain
experts**. A domain expert might be a warehouse manager, an accountant or a support lead.
Much of DDD is about learning from them and encoding what they know into the software.

## Strategic vs tactical DDD

DDD has two halves, and it helps to keep them separate in your head.

```text
Domain-Driven Design
+-- Strategic design  (the big picture: this chapter)
|     - ubiquitous language
|     - bounded contexts
|     - context mapping
|     - core / supporting / generic subdomains
|
+-- Tactical patterns (the code building blocks: next chapter)
      - entities, value objects, aggregates
      - domain events, repositories, services
```

**Strategic design** is about boundaries and language: how you split a large system into
areas that make sense, and how each area talks about the world. These are decisions you
make with a whiteboard and conversations, before much code exists.

**Tactical patterns** are the concrete building blocks you write in code -
[entities](/course/software-architecture/ddd-tactical-patterns/entities), value
objects, aggregates and so on. That is the next chapter; here we stay at the big-picture
level and do not write domain classes yet.

A common trap is to jump straight to the tactical patterns (people love the code parts)
while ignoring strategy. But entities and aggregates in the wrong boundaries, using the
wrong language, just organize the mess more neatly. Strategy comes first.

## When is DDD worth it

DDD is not free. It asks for time with domain experts, careful naming and extra structure.
That investment pays off when the **domain is complex** - lots of rules, subtle vocabulary,
behavior that changes as the business grows. Think insurance, logistics, trading, payroll.

It is usually **overkill** when the domain is simple. A blog, a basic CRUD admin, or a
form that saves data to a table does not have hidden business complexity to capture. There,
the full DDD machinery adds ceremony without buying you much. We look at this honestly in
Chapter 9 ([when not to use DDD](/course/software-architecture/evolving-the-architecture/when-not-to-use-ddd)).

A useful test: if you and the domain experts keep discovering rules you did not know
about, and the tricky part is *what the software should do* rather than *how to store it*,
DDD earns its keep.

One thing that trips up teams new to this: DDD rarely pays off in the first sprint. The
cost (conversations, naming, boundaries) is front-loaded, and the payoff shows up months
later when a change that would have rippled everywhere stays contained in one context. If
you judge it by week one, it always looks like overhead.

## Common mistake

The most common mistake is treating DDD as a folder layout or a set of base classes you
copy in. Teams create `Entity`, `Repository` and `ValueObject` classes, feel like they are
"doing DDD", and skip the actual work: talking to domain experts and getting the language
and boundaries right. DDD is a way of thinking about the domain first; the patterns are
only how you write that understanding down.

## FAQ

### What is Domain-Driven Design in simple terms

It is building software by modeling the real business it serves - its concepts, rules and
vocabulary - so the code matches how the business actually works, instead of being
organized around the database or the framework.

### What is the difference between strategic and tactical DDD

Strategic DDD is the big picture: shared language and the boundaries between areas of the
system. Tactical DDD is the code-level building blocks (entities, value objects,
aggregates) used inside a boundary. This chapter is strategic; the next one is tactical.

### Do I always need DDD

No. DDD shines when the domain is genuinely complex. For simple CRUD apps or a blog it
adds overhead without much benefit - the simplest design that works is the right one.
