---
title: "Commands and queries (CQRS)"
slug: commands-and-queries-cqrs
seo_title: "CQRS Explained: Commands and Queries in PHP"
seo_description: "Command-Query Separation and an intro to CQRS: commands change state and return nothing, queries read and never change state, with separate write and read models."
---

A single use case usually does one of two things: it *changes* something, or it *reads*
something. Placing an order changes state. Showing an order summary reads state. **CQRS**
(Command Query Responsibility Segregation) starts from the idea that these are not the same
kind of operation and should not share code as if they were. Treating them as one is where a
lot of confusing application code comes from. This lesson keeps them apart, building up from
the older method-level rule to the model-level split.

## Command-Query Separation

The older, smaller idea is **Command-Query Separation (CQS)**, from Bertrand Meyer. It says
every method should be one of two things:

- a **command** - changes state, returns nothing meaningful
- a **query** - returns data, changes nothing

Never both. A method that changes state *and* returns data hides a surprise: you cannot
call it to read without also causing a change.

```php
<?php

// Command: does something, returns nothing meaningful
public function handle(PlaceOrderCommand $command): void { /* ... */ }

// Query: returns data, changes nothing
public function handle(GetOrderSummaryQuery $query): OrderSummary { /* ... */ }
```

"Returns nothing meaningful" is deliberate. A command may return a generated id so the
caller knows what was created, but it should not return *the state you would have queried*.
The mental model stays clean: commands write, queries read.

## From CQS to CQRS

**CQRS** (Command Query Responsibility Segregation) takes that same split and applies it to
whole models, not just methods. The idea: the model you use to **write** and the model you
use to **read** do not have to be the same.

Why would you want two? Because writing and reading pull in opposite directions. Writing
needs a rich domain model that protects its rules - an `Order` aggregate that refuses to be
put in an invalid state. Reading needs the opposite: a flat, convenient shape for a screen,
often joining data from several places, with no rules to enforce at all.

```text
              Write side                     Read side
   +---------------------------+   +----------------------------+
   PlaceOrderCommand  ------->  |   GetOrderSummaryQuery ----->  |
   |  Order aggregate           |   |  OrderSummary (flat DTO)   |
   |  enforces invariants       |   |  built for the screen      |
   |  saved via repository      |   |  read straight from DB     |
   +---------------------------+   +----------------------------+
```

On the write side you load the `Order` aggregate through its
[repository](/course/software-architecture/ddd-tactical-patterns/repositories), call a
method, and save. On the read side you can skip the domain model entirely and query the
database directly into a shape built for display. There is no rule to protect when you are
only reading, so the aggregate would just be in the way.

## A minimal read query

A query handler does not need a repository or an aggregate. It reads and shapes:

```php
<?php

final class GetOrderSummaryHandler
{
    public function __construct(private OrderReadStore $reads) {}

    public function handle(GetOrderSummaryQuery $query): OrderSummary
    {
        return $this->reads->summaryFor($query->orderId);
    }
}
```

`OrderReadStore` can run a plain SQL query joining orders and customers into an
`OrderSummary` object. It never touches the write model. That is CQRS in its lightest form -
and it is enough for most applications.

A detail worth stealing: because the read side never writes, you can bind `OrderReadStore`
to a read-only database connection (Laravel's `read`/`write` config split, or a read
replica). A query then *physically cannot* mutate state, which turns the CQS rule into
something the infrastructure enforces for you instead of something you have to remember.

## How far to take it

CQRS is a spectrum. The light version - separate command and query *objects*, write side
through the domain, read side straight from the database - costs almost nothing and pays off
immediately. The heavy version keeps a *separate read database* kept in sync by events, so
reads never touch the write store at all. That, together with event sourcing, is the
subject of Chapter 7 (read models and CQRS); do not reach for it here. For now: separate
your commands from your queries, and let the read side skip the domain.

## Common mistake

The common mistake is forcing reads through the write model "for consistency". A team builds
a beautiful `Order` aggregate, then loads it just to display a list of orders - hydrating
full aggregates, running lazy loads, mapping back to arrays for the view. It is slow and
awkward, and it tempts people to add getters and display logic to the aggregate that have
nothing to do with its rules. Reading is allowed to be simple. Let queries take a shortcut.

## FAQ

### What is CQRS

CQRS (Command Query Responsibility Segregation) is separating the model you use to change
state from the model you use to read state. Commands go through a rich domain model that
protects its rules; queries read into a flat shape built for display.

### What is the difference between CQS and CQRS

CQS is the method-level rule: a method either changes state or returns data, never both.
CQRS applies the same split at the model level: a separate write model and read model. CQRS
is CQS taken up a level.

### Do commands really have to return nothing

They should not return the *state you could have queried*. Returning a new id or a
success/failure result is fine and common - the point is that a command's job is to change
things, not to answer questions.
