---
title: "Read models and CQRS"
slug: read-models-and-cqrs
seo_title: "Read Models and CQRS with Event Sourcing"
seo_description: "Read models and CQRS: build query-optimized projections from an event stream. This is full CQRS - separate write and read models, rebuildable by replay."
---

[Event sourcing](/course/software-architecture/event-driven-architecture/event-sourcing) gave you a great way to **write**: an append-only log of facts. But that log
is terrible to **query**. "Show me all orders over 100 from last month, sorted by date" is a
nightmare if every read has to replay thousands of events. The fix is to keep the event
stream for writing and build separate, query-friendly **read models** from it. Combined,
these two halves are **full CQRS**.

## Reads and writes want different shapes

Chapter 6 introduced [CQRS](/course/software-architecture/application-layer-and-use-cases/commands-and-queries-cqrs)
as separating commands (writes) from queries (reads). Event sourcing pushes that separation
all the way: the write side and the read side use **different data models entirely**.

- The **write model** is the event stream. It is optimized for recording facts correctly,
  one aggregate at a time.
- The **read model** is whatever shape answers your queries fastest - a flat table, a
  search index, a cache. It holds no business rules; it just serves data.

```text
        write side                          read side
  command -> [ aggregate ] -> events -> [ projection ] -> read model (flat table)
             enforces rules     |                              |
                                +--- queries never touch ------+
```

## A projection turns events into a read model

A **projection** is a small piece of code that subscribes to the events and updates a read
model to match. Each time an event happens, the projection writes the plain, denormalized
rows a query needs.

```php
// Listens to order events and keeps a flat "orders_summary" table
// that queries can hit directly - no event replay at read time.
final class OrderSummaryProjection
{
    public function __construct(private Connection $db) {}

    public function onOrderPlaced(OrderPlaced $e): void
    {
        $this->db->insert('orders_summary', [
            'order_id'  => $e->orderId,
            'total'     => $e->totalCents,
            'status'    => 'placed',
        ]);
    }

    public function onOrderShipped(OrderShipped $e): void
    {
        $this->db->update('orders_summary',
            ['status' => 'shipped'],
            ['order_id' => $e->orderId],
        );
    }
}
```

Now a query is just `SELECT * FROM orders_summary WHERE total > 10000`. No aggregate, no
replay, no business logic - the read model already has the answer in the exact shape you
want.

## Many read models from one stream

Because read models are derived, you can build **as many as you like** from the same events,
each tuned for one job: a summary table for the orders list, a search index for full-text
search, a dashboard rollup for analytics. They never fight each other, because none of them
is the source of truth - the event stream is. If a read model gets corrupted or you invent a
new one, you rebuild it by replaying the events. This is the practical superpower of pairing
event sourcing with CQRS.

That superpower has one condition, though. "Rebuild by replay" only stays clean if the
projection is idempotent - replaying `OrderPlaced` onto a table that already has that row
must not create a duplicate. The `insert` above is fine on a fresh table; for a live rebuild
you either truncate first or switch to an upsert (insert-or-update on `order_id`). Skip that
and your first rebuild doubles every row.

## Eventual consistency

There is a catch worth stating plainly. The read model updates **after** the event is
stored, so for a brief moment the write side knows something the read side has not caught up
to yet. This is **eventual consistency**. Usually the gap is milliseconds and harmless, but
you must design for it - for example, don't assume that immediately after a command the list
view already shows the change. If a screen needs the just-written value instantly, read it
from the write side for that one case.

## Common mistake

Putting business rules in the read model or the projection. The read side must stay dumb - it
only reshapes facts the write side already decided. If a projection starts making decisions
("if total over 100, mark as priority"), that rule now lives in two places and the two sides
drift apart. Decisions belong to the aggregate on the write side; the read model just mirrors
the resulting events.

## FAQ

### What is a read model in CQRS?

A data structure built purely for answering queries, kept separate from the write model. It
is filled by projections that react to events and store the data in whatever shape reads need,
so queries are fast and never touch the business logic.

### What is full CQRS?

CQRS where the read and write sides use entirely different data models - typically an
event-sourced write side and one or more projected read models - rather than just splitting
command and query methods over a shared model. The intro in Chapter 6 is the light version;
this is the full one.

### Can I have more than one read model?

Yes, and that is a key benefit. From a single event stream you can build many read models,
each optimized for a different query or view, and rebuild any of them by replaying the events.
