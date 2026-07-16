---
title: "What is hexagonal architecture?"
slug: what-is-hexagonal-architecture
seo_title: "What Is Hexagonal Architecture? Ports & Adapters"
seo_description: "Hexagonal architecture (ports and adapters) puts the domain in the center, isolated from the database, HTTP and the framework. A PHP beginner's guide."
---

**Hexagonal architecture** puts your business logic in the center and pushes every
technical detail - the database, the web framework, external APIs - to the outside. The
center talks to the outside only through interfaces, so you can swap the technical parts
without touching the business rules. It's also called **ports and adapters**, and that
second name describes it better.

## The problem it solves

In many apps, the business logic is tangled with the framework. Order-total math lives
inside a controller. A price rule sits in the middle of a database query. It works, but
the important part - the rules that make your business your business - is now welded to
Laravel, to MySQL, to HTTP.

That welding hurts later. You cannot test a rule without booting the framework. You cannot
switch from MySQL to something else without rewriting business code. You cannot even find
the rules, because they're scattered across controllers and models. Chapter 1 called this
the domain-vs-infrastructure split; hexagonal architecture is a concrete way to enforce it.

## The idea: a center and an edge

Alistair Cockburn described it in 2005. Picture your application as a shape with an inside
and an outside.

```text
        driving side                     driven side
   (things that call us)            (things we call)

   HTTP  ---\                          /---  MySQL
   CLI   ----[   D O M A I N   ]----      Mail
   Tests ---/     (the core)      \---  Queue
```

The **domain** sits in the middle. It holds the business rules and nothing else - no SQL,
no HTTP, no framework classes. Around it is the outside world: the web, the command line,
databases, mail servers, message queues.

The center never reaches out to the edge directly. Instead, it defines **ports** (plain
interfaces) that say what it needs and what it offers. The outside world provides
**adapters** that plug into those ports. Ports are covered in lesson 3, adapters in lesson
4; for now just hold the shape.

## Why a hexagon?

The shape is not special. Cockburn picked a hexagon because it has several sides, which
lets you draw several different ways in and out without implying "top" and "bottom" like a
layer diagram does. A web request enters through one side, a scheduled job through another,
the database hangs off a third.

> The hexagon means "many sides, many ways in and out." It is not six of anything. A
> pentagon or an octagon would carry the same meaning.

Do not count the sides or look for six components. The lesson of the shape is only this:
**the core has one job, and there are many independent things attached to its edges.**

## Common mistake: thinking the hexagon is a new pattern to memorize

Hexagonal architecture is not a checklist of six layers or a library you install. It is
one rule dressed up in a picture: keep the business core clean, and let it depend on
interfaces instead of on concrete technology. If you already keep your domain separate
from infrastructure, you are most of the way there. The rest of this chapter just gives
that instinct precise names.

One tell that a team has misread the diagram: a folder literally named `Hexagon/`, or a
`Ports/` directory holding one interface per class in the codebase. The pattern is not
about directory names or interface count. It is about which way the dependencies point.

## FAQ

### Is hexagonal architecture the same as ports and adapters?

Yes. "Ports and adapters" is the original, more descriptive name Cockburn preferred later.
"Hexagonal" refers to the drawing. They mean exactly the same architecture.

### Do I need six sides or six layers?

No. The hexagon is just a shape with room for many connections. There is no rule about six
of anything - the number of ports and adapters depends entirely on your application.

### Is this only for large systems?

No. The core idea - business logic behind interfaces, technology on the outside - scales
down fine. A small app might have three or four ports. You add structure in proportion to
the app, not because the diagram has six sides.
