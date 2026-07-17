---
title: "Microservices overview"
slug: microservices-overview
seo_title: "Microservices Overview: What They Buy and Cost"
seo_description: "Microservices are independently deployable services that own their data. Learn what they buy you - team autonomy and independent scaling - and what they really cost."
---

**Microservices** split an application into many small services, each running on its own,
each talking to the others over the network. Where a
[modular monolith](/course/software-architecture/monolith-and-beyond/the-modular-monolith)
has walls on the inside, microservices turn those walls into separate, independently
deployed programs.

## Independently deployable services that own their data

Two properties define a microservice, and both matter:

- **Independently deployable.** You can ship the `Orders` service without touching or
  redeploying `Billing`. If you cannot, you do not have microservices - you have a
  [distributed monolith](/course/software-architecture/monolith-and-beyond/what-is-a-monolith).
- **Owns its own data.** Each service has its **own database**, and no other service is
  allowed to touch it. `Orders` cannot query the `Billing` tables; it must call the
  `Billing` service and ask.

```text
   [ Orders ]        [ Billing ]        [ Catalog ]
       |                  |                  |
   own DB             own DB             own DB
       \                  |                  /
        +----- network calls / messages ----+

  Separate programs, separate databases, separate deploys.
```

That "owns its data" rule is the strict one. The instant two services share a database,
they are coupled through it - a schema change in one breaks the other - and you have lost
the independence that was the whole point.

## What microservices buy you

When a system and an organization are large enough, this shape pays off:

- **Team autonomy.** A team owns a service end to end - code, database, deploy schedule -
  and ships on its own cadence without coordinating a release with everyone else. This is
  the biggest real-world driver: microservices are as much about *org structure* as about
  code.
- **Independent scaling.** If only the `Search` service is under load, you run more copies
  of just that service, not the whole app. You spend compute where the traffic actually is.
- **Independent technology choices.** One service can use a different language or database
  that fits its job, without forcing that choice on everyone.
- **Fault isolation (if done well).** A crash in one service need not take down the others -
  provided you designed for that, which is harder than it sounds.

## What microservices cost

None of the above is free. You are trading in-process function calls for a distributed
system, and that bill is large:

- **The network is now in the middle of everything.** Calls can be slow, fail halfway, or
  time out. This is serious enough that it gets its own lesson:
  [the cost of distributed systems](/course/software-architecture/monolith-and-beyond/the-cost-of-distributed-systems).
- **No easy transactions.** You cannot wrap a change across `Orders` and `Billing` in one
  database transaction, because they are different databases. Keeping data consistent
  becomes real design work (eventual consistency, [sagas](/course/software-architecture/event-driven-architecture/the-saga-pattern) - named later in the course).
- **Operational overhead.** Many deploy pipelines, dashboards, log streams and alerts
  instead of one. You need real infrastructure maturity - monitoring, tracing, automated
  deploys - just to keep the lights on.
- **Harder debugging.** One user action can hop through five services; there is no single
  stack trace, so you need distributed tracing to follow it.

Most of that overhead is a fixed cost you pay before the first split earns anything. Tracing,
centralized logs, per-service pipelines and on-call all have to exist on day one, or the
first outage is unreadable. A monolith lets you defer that bill; microservices hand it to you
up front.

This is why microservices are not a starting point. You adopt them to solve a specific,
felt problem - usually "too many teams stepping on each other in one codebase" or "one
part needs to scale on its own" - not by default.

## Common mistake

Splitting by technical layer instead of by business capability - a "controllers service",
a "database service", a "validation service". Real microservices are sliced **vertically**
by domain (`Orders`, `Billing`, `Catalog`), each owning its full stack and data. Slice
horizontally and every feature change touches every service, which is coupling wearing a
microservices costume.

## FAQ

### How small should a microservice be?

Small enough that one team can own it and understand it fully, big enough to own a whole
business capability. "One service per class" is a common and painful over-split. Aim for a
meaningful slice of the domain, not the smallest possible unit.

### Do microservices make an app faster?

Usually the opposite for a single request: a call that was a function call is now a network
round trip, so latency goes up. What they improve is *scaling* and *team throughput*, not
raw per-request speed.

### Can I share one database between two services to keep it simple?

That is the fastest way to lose the benefits. A shared database couples the services'
schemas and deploys - it is a distributed monolith. If two services need the same data, one
should own it and expose it through an API or events.
