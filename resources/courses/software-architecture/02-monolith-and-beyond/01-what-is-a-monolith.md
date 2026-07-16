---
title: "What is a monolith"
slug: what-is-a-monolith
seo_title: "What Is a Monolith? The Underrated Default"
seo_description: "What is a monolith? One deployable application - and often the smartest default. Learn its real strengths and the distributed-monolith trap that costs you both ways."
---

A **monolith** is one application you build, ship and run as a single unit. All the code
lives in one codebase, compiles or deploys together, and runs in one process (or a few
identical copies behind a load balancer). It has a bad reputation it mostly does not
deserve.

## One deployable application

The defining trait is not size and not "messy code". It is that everything ships
**together**. One `git push`, one deploy, one running thing. Users, orders, billing and
emails are all modules inside the same app, calling each other with ordinary function
calls.

```text
+--------------------------------------+
|            One application           |
|  +--------+ +--------+ +----------+   |
|  | Users  | | Orders | | Billing  |   |
|  +--------+ +--------+ +----------+   |
|         one database, one deploy      |
+--------------------------------------+
```

A default Laravel app is a monolith: controllers, models, jobs and mailers in one
project, deployed to one server, talking to one database.

## Why the monolith is underrated

For most teams and most products, the monolith is the correct starting point, because
the things you do every day are simple:

- **Simple to build.** A method calls another method. No network, no serialization, no
  service discovery. Your IDE can follow every call.
- **Simple to deploy.** One artifact goes out. There is no ordering problem where service
  A must ship before service B.
- **Simple to debug.** One stack trace crosses the whole request. One log stream. You can
  step through the entire flow in a debugger.
- **Simple transactions.** Because there is one database, a single database transaction
  keeps your data consistent for free (more on why that is a big deal in
  [the cost of distributed systems](/course/software-architecture/monolith-and-beyond/the-cost-of-distributed-systems)).
- **Cheap refactoring across boundaries.** Moving a responsibility from one module to
  another is a single commit, with the compiler or your tests checking every caller at
  once. The same change spread across services is a versioned API migration you roll out
  in stages.

"Monolith" is not a synonym for "legacy" or "spaghetti". A well-structured monolith with
clear internal boundaries - the [modular monolith](/course/software-architecture/monolith-and-beyond/the-modular-monolith)
of the next lesson - is a genuinely good architecture, not a phase to grow out of.

## The distributed monolith warning

There is one failure mode worse than a plain monolith: the **distributed monolith**. That
is when you split the app into separate services, but they are still so tightly tangled
that you cannot deploy one without the others.

You paid the full price of distribution - network calls, separate deploys, harder
debugging - and got none of the benefit, because the services still change together. A
tell-tale sign: every "microservice" release is actually a coordinated release of five
services at once.

```text
Looks like microservices, behaves like one big app:

  [Orders] --calls--> [Users] --calls--> [Billing]
      ^                                       |
      +---------------- calls ----------------+

  Change one, redeploy all three. Worst of both worlds.
```

The lesson: distribution is not free, and splitting badly is worse than not splitting at
all. Get the boundaries right first (which is exactly what a modular monolith practices),
and only then consider crossing the network.

## Common mistake

Reaching for microservices at the start "so we can scale later". Early on you do not yet
understand your own domain, so you will draw the service boundaries in the wrong places -
and boundaries are the hardest thing to move once services own separate databases. Start
with a monolith, learn the domain, and split only when a real, measured pain forces it.

## FAQ

### Is a monolith the same as bad code?

No. A monolith describes how the app is *deployed* (as one unit), not how well it is
organized. You can have a clean, modular monolith or a tangled one, just as you can have
clean or tangled microservices.

### Can a monolith scale?

Yes, further than most apps ever need. You run multiple identical copies of the app behind
a load balancer (horizontal scaling). The limit you eventually hit is usually the shared
database, not the app itself.

### Monolith vs microservices - which should I start with?

Almost always the monolith. It is simpler to build, deploy and debug, and it lets you
learn the domain before committing to hard-to-move service boundaries. We compare them
directly in [monolith vs microservices](/course/software-architecture/monolith-and-beyond/monolith-vs-microservices).
