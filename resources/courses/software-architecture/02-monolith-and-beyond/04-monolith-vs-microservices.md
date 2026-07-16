---
title: "Monolith vs microservices"
slug: monolith-vs-microservices
seo_title: "Monolith vs Microservices: How to Actually Choose"
seo_description: "How to choose between a monolith and microservices using team size, domain complexity and operational maturity. The rule: start monolith, extract services on real pain."
---

Monolith vs microservices is the decision this chapter has been building toward. It gets
framed as a religious war, but it is really a trade-off against your own situation - and for
most teams the honest answer is "not microservices, not yet".

## There is no default winner - only fit

A monolith optimizes for **simplicity**: easy to build, deploy, debug, and to keep data
consistent. Microservices optimize for **independence**: teams and parts of the system that
can move and scale on their own, bought with a large jump in complexity.

So the question is never "which is better". It is "which trade-off fits *us*, right now".
Three factors decide it.

## Factor 1: team size

Microservices are, first and foremost, a way to let many teams work without colliding. Their
biggest payoff is organizational.

- **A few developers, one or two teams:** a monolith wins easily. There is no coordination
  problem to solve, so splitting into services adds cost with no matching benefit.
- **Many teams stepping on each other in one codebase** - merge conflicts, release trains,
  "we can't ship until their change is ready": this is the pain microservices actually
  relieve. Conway's law in short: your system tends to mirror your org chart, so many
  independent teams naturally push toward many independent services.

## Factor 2: domain complexity and maturity

You can only draw good service boundaries once you understand the domain, and boundaries are
the expensive thing to get wrong.

- **New product, unclear domain:** stay in a monolith. You will move the boundaries many
  times as you learn, and moving a folder is cheap while moving a service (with its own
  database) is not.
- **Mature, well-understood domain with stable seams:** the boundaries are known, so
  extracting a service is much lower risk.

A [modular monolith](/course/software-architecture/monolith-and-beyond/the-modular-monolith)
is how you find those seams cheaply first.

## Factor 3: operational maturity

Microservices demand infrastructure that a monolith does not: automated deploys per service,
centralized logging, distributed tracing, monitoring and alerting, and a team comfortable
running all of it. Ask honestly:

```text
Can we already, today, do all of this well?
  [ ] deploy any service in minutes, automatically
  [ ] trace one request across several services
  [ ] find and read logs from all services in one place
  [ ] get paged when a service is unhealthy

Mostly "no"? You are not ready to operate microservices.
Adding them now just multiplies problems you already have.
```

If you cannot run one app smoothly, running fifteen will not go better.

## The rule: start monolith, extract on real pain

Put the factors together and a simple heuristic falls out, and it is the one to remember:

> **Start with a monolith. Keep it modular. Extract a service only when you feel real,
> measured pain - not when you imagine future pain.**

"Real pain" is concrete and observable: a specific module needs to scale independently and
is dragging the rest down; two teams are blocked on each other in one part of the codebase;
one component needs a technology the rest cannot use. When that happens, a well-drawn module
lifts out into a service relatively cleanly. Until it happens, distribution is cost you are
paying for a problem you do not have.

One trap even at this stage: when the pain finally shows up, teams often extract the service
that is architecturally annoying rather than the one the measured pain points at. You get the
full cost of a split and none of the relief, because the thing that actually hurt is still
tangled in the core. Let the pain pick the seam.

The reverse path - discovering your premature microservices have the wrong boundaries and
stitching them back together - is far more painful than extracting a service from a tidy
monolith.

## Common mistake

Choosing microservices "to look serious" or because a famous company uses them. Netflix and
Amazon adopted microservices to solve problems of enormous scale and hundreds of teams -
problems you almost certainly do not have yet. Copying their architecture without their
constraints buys you their costs and none of their reasons.

## FAQ

### When should I actually split my monolith into services?

When there is a concrete, measured pain a split would relieve: a part that must scale on its
own, teams blocked on each other in one codebase, or one component needing a different tech
stack. No such pain means no split.

### Can I mix the two?

Yes, and it is common and sensible. Keep a monolith core and peel off just the one or two
pieces with a real reason to be separate (say, a heavy image-processing service). You do not
have to choose "all monolith" or "all microservices".

### Isn't starting with a monolith just building technical debt?

Only if it is a tangled monolith. A modular monolith with clear boundaries is not debt - it
is a clean architecture that also happens to be cheap to run and easy to split later if you
ever need to.
