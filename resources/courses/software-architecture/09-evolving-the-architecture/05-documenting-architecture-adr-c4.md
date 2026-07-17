---
title: "Documenting architecture: ADRs and C4"
slug: documenting-architecture-adr-c4
seo_title: "Documenting Architecture with ADRs and the C4 Model"
seo_description: "Record decisions with Architecture Decision Records (context, decision, consequences) and communicate structure with the C4 model. Lightweight, durable docs."
---

Six months from now, someone - maybe you - will look at the codebase and ask "why on earth
is it built this way?" Without an answer, they'll assume it was a mistake and start
"fixing" a decision that was deliberate. Architecture documentation exists to answer that
question. But heavy documents rot: they're wrong within a sprint and nobody reads them. The
trick is to keep docs **lightweight and durable**. Two tools do most of the job:
**Architecture Decision Records (ADRs)** for the *why* and the **C4 model** for the *what*.

## The problem: decisions with no memory

Your team chose a modular monolith over microservices, put the domain behind ports, and
skipped an event bus for now. Each choice had good reasons - a small team, no scaling need
yet, a desire to stay simple. None of that is written down. A year later the reasons are
forgotten, a new hire "discovers" the monolith and proposes splitting it, and the team
re-argues a settled question from scratch because the *context* is gone.

Code shows *what* you did. It never shows *why*, or what you rejected. That's what
documentation has to capture.

## ADRs: record the decisions

An **Architecture Decision Record** is a short Markdown file, one per significant decision,
committed next to the code (for example in `docs/adr/`). It's small on purpose - a page or
less - and, crucially, **immutable**: you don't edit an old ADR, you write a new one that
supersedes it. That gives you a dated history of how the architecture's thinking evolved.

Three sections carry the weight: **context** (the forces at play), **decision** (what you
chose), **consequences** (what you now live with, good and bad).

```text
# ADR 007: Modular monolith instead of microservices

Status: Accepted (2026-05-14)

## Context
We are a team of three shipping an MVP. Load is low and there is no
independent-scaling need yet. Microservices would add deployment,
network and data-consistency cost we can't afford right now.

## Decision
We build a single deployable, split internally into modules by
bounded context (Ordering, Billing, Shipping), each behind a public
front door. No service is extracted until a concrete need appears.

## Consequences
+ One deploy, one database, easy local dev and testing.
+ Boundaries are in place, so extraction later is cheap.
- Everything scales together; a heavy module can't scale alone yet.
- We must enforce module boundaries in CI or the mud returns.
```

Two habits make ADRs work in the long run. The `Status` line is not decoration - it's what
lets a reader tell a live decision from a retired one; a superseded ADR keeps its text but
flips to `Superseded by 012`, so links never rot. And the section that earns its keep is
**Consequences**, especially the minus signs. People reach for an ADR to justify a choice,
so a consequences list with no downsides is marketing, not a record - if you can't name what
the decision costs you, you haven't understood it yet.

Anyone reading this later gets the *reasoning*, not just the result. If circumstances change
and you split Billing out, you write ADR 012 ("supersedes ADR 007 for Billing") - the
history stays intact. This is the written form of every trade-off this course discussed:
[monolith vs microservices](/course/software-architecture/monolith-and-beyond/microservices-overview),
the [cost of distributed systems](/course/software-architecture/monolith-and-beyond/the-cost-of-distributed-systems),
whether to go event-driven.

## C4: communicate the structure

Where ADRs capture *why*, the **C4 model** shows *what the system looks like*, at four zoom
levels so you can pick the detail you need. The name is the four Cs:

1. **Context** - the system as one box, surrounded by its users and the external systems it
   talks to (payment provider, email service). The view you'd show a non-technical
   stakeholder.
2. **Container** - the deployable/runnable pieces: the Laravel app, the database, the queue
   worker, the SPA. Not Docker containers specifically - "container" here means a separately
   running thing.
3. **Component** - inside one container, the major building blocks. For the Laravel app this
   is your [modules](/course/software-architecture/evolving-the-architecture/refactoring-a-monolith-into-modules)
   and their ports: Ordering, Billing, the repositories and adapters.
4. **Code** - the classes inside a component. This level is usually left to the IDE and the
   code itself; you rarely draw it.

The point of the levels is that you **zoom to the audience**. A stakeholder sees the context
diagram; a new engineer starts at container and component; nobody needs all four at once.
The diagrams can be simple boxes and arrows - clarity beats polish. Keep the top two levels
current (they change slowly) and don't bother maintaining the code level (it changes every
day and the IDE already draws it).

## Keeping docs durable

Documentation rots when it's detailed and separate from the code. Fight that:

- **Live in the repo.** ADRs and diagram sources sit next to the code and change in the same
  pull request, so review catches drift.
- **Favour the stable levels.** Record decisions (rarely change) and high-level structure
  (change slowly). Skip low-level docs that are stale by Friday.
- **Write less.** A one-page ADR that exists beats a twenty-page design doc that's ignored.
  If it's too long to keep updated, it will be wrong, which is worse than missing.

Good architecture docs are a small, steady habit, not a big up-front deliverable.

## FAQ

### What is an Architecture Decision Record?

An ADR is a short, dated Markdown file recording one significant architectural decision, with
three parts: context (the forces), decision (what you chose), and consequences (what you now
live with). ADRs are immutable - a new one supersedes an old one rather than editing it.

### What is the C4 model?

C4 is a way to diagram software structure at four zoom levels: Context (system and its
surroundings), Container (deployable pieces), Component (building blocks inside a container),
and Code (classes). You show the level that fits your audience instead of one giant diagram.

### Why not just keep architecture docs in a wiki?

Docs separated from the code drift out of date, because nothing forces them to change when
the code does. Keeping ADRs and diagram sources in the repository means they're reviewed in
the same pull requests, so drift is caught early and the docs stay trustworthy.
