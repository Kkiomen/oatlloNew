---
title: "Domain events: recording what happened"
slug: domain-events
seo_title: "Domain Events in DDD: OrderPlaced Explained (PHP)"
seo_description: "Learn what a domain event is in Domain-Driven Design: a past-tense record of what happened, raised by aggregates to decouple side effects. PHP example."
---

A **domain event** is a record of something that already happened in the domain -
`OrderPlaced`, `PaymentReceived`, `InvoiceIssued`. It's named in the **past tense** because it
describes a fact, not a request. Aggregates raise these events as they change, and other parts
of the system react. That's how you decouple the thing that happened from the things that
should follow.

## What is a domain event?

When an order is placed, plenty needs to happen: send a confirmation email, reserve stock,
notify the warehouse, update analytics. The naive version crams all of it into one method:

```php
public function place(): void
{
    $this->status = OrderStatus::Placed;
    $this->mailer->sendConfirmation($this);   // now Order needs a mailer
    $this->warehouse->notify($this);          // and a warehouse client
    $this->analytics->track('order_placed');  // and an analytics client
}
```

The `Order` now depends on email, warehouse, and analytics services. Its real job - order
rules - is buried under plumbing, it's miserable to test, and every new side effect means
editing the aggregate again. The order shouldn't have to know who cares that it was placed.

## Record the fact, don't do the work

Instead, the aggregate **records that something happened** and moves on. The event is a tiny,
immutable value object describing the fact.

```php
<?php
declare(strict_types=1);

final class OrderPlaced
{
    public function __construct(
        public readonly string $orderId,
        public readonly DateTimeImmutable $occurredOn,
    ) {}
}

final class Order
{
    private OrderStatus $status = OrderStatus::Pending;

    /** @var object[] */
    private array $events = [];

    public function __construct(
        public readonly string $id,
    ) {}

    public function place(): void
    {
        $this->status = OrderStatus::Placed;

        // Just record the fact. No emails, no warehouse, no analytics here.
        $this->events[] = new OrderPlaced($this->id, new DateTimeImmutable());
    }

    /** @return object[] Hand the recorded events to whoever will dispatch them. */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}

enum OrderStatus: string
{
    case Pending = 'pending';
    case Placed = 'placed';
}
```

The `Order` knows nothing about email or stock. It records `OrderPlaced` and forgets. Later,
after the aggregate is saved, something collects the events with `releaseEvents()` and lets
interested handlers react - one sends the email, another reserves stock. Add a fifth side
effect tomorrow and `Order` doesn't change at all.

Two ordering details matter here. Record the event *after* the state change succeeds, in the
same method, so you never emit `OrderPlaced` for an order that tripped a later check. And note
that events sit on the aggregate until something calls `releaseEvents()` - forget that call
after saving and they pile up silently and never fire.

## Why past tense matters

The name isn't cosmetic. `PlaceOrder` (imperative) is a **[command](/course/software-architecture/event-driven-architecture/events-vs-commands)** - a request that might
still be rejected. `OrderPlaced` (past tense) is an **event** - a fact that already happened
and can't be undone. Handlers react to facts. Past-tense naming keeps that line sharp and
stops you sneaking "please do X" logic into something meant to say "X happened".

## Common mistake: doing the side effect inside the aggregate

The mistake is drifting back to calling the mailer or the warehouse from inside `place()`.
That re-couples the aggregate to infrastructure and defeats the point. The aggregate's job
ends at recording the event. Dispatching it and reacting to it happen outside, after the
aggregate is saved.

This lesson covers only the DDD **building block** - a plain object that records a fact.
Turning events into a full messaging system (dispatchers, queues, choreography, event
sourcing) is its own subject, covered later in the **[event-driven architecture](/course/software-architecture/event-driven-architecture/what-is-event-driven-architecture)** chapter. Here,
an event is just an immutable object an aggregate produces.

## FAQ

### Domain event vs command?

A **command** is a request to do something, named as an instruction: `PlaceOrder`,
`ShipOrder`. It can be rejected. A **domain event** is a record that something already
happened, named in the past tense: `OrderPlaced`, `OrderShipped`. It's a fact, so handlers only
react to it - they don't get to say no.

### Should the event carry the whole aggregate?

No - keep it small. Carry the id and the few facts a handler needs (`orderId`, `occurredOn`,
maybe a total). A handler that needs the full aggregate can load it by id. A fat event becomes
a hidden coupling to the aggregate's internal shape.

### When do the events actually get dispatched?

Usually right after the aggregate is saved, so you never announce something that didn't
persist. The aggregate records the events; the layer that saves it releases and dispatches
them. That ordering belongs to the **[application layer](/course/software-architecture/application-layer-and-use-cases/the-application-layer)**, which a later chapter covers in full.
