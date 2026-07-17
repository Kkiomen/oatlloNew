---
title: "CQRS in practice"
slug: cqrs-in-practice
seo_title: "CQRS in Practice: A PHP Example (No Event Sourcing)"
seo_description: "CQRS in practice: separate the write model from the read model, with a plain PHP example - and why CQRS does not require event sourcing or two databases."
---

**CQRS** (Command Query Responsibility Segregation) means using one model to change data
and a different model to read it. You met the idea in
[commands and queries](/course/software-architecture/application-layer-and-use-cases/commands-and-queries-cqrs);
this lesson is the practical version: a real split you can apply today, with the same
database, and without any of the advanced machinery people assume it needs.

## The problem one model creates

A single model that serves both writes and reads gets pulled in two directions. The
write side wants a rich domain model that guards invariants: an `Order` aggregate that
refuses to add a line to a shipped order. The read side wants something else entirely -
a flat "orders list" with the customer name, a total, and a status label, joined from
five tables and shaped exactly for one screen.

Force both through the same Eloquent model and you get an aggregate bloated with
presentation accessors, or a read that loads whole object graphs just to show a table.

## The split: a write side and a read side

CQRS separates the two paths. Commands go through the domain; queries skip it.

```php
// WRITE side: a command changes state through the domain
final class PlaceOrderHandler
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(PlaceOrder $command): void
    {
        $order = Order::place($command->customerId, $command->items);
        $this->orders->save($order); // aggregate enforces the rules
    }
}
```

```php
// READ side: a query reads a shape built for the screen, bypassing the domain
final class OrderSummaries
{
    public function __construct(private Connection $db) {}

    /** @return OrderSummary[] */
    public function forCustomer(string $customerId): array
    {
        $rows = $this->db->table('orders')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.customer_id', $customerId)
            ->select('orders.id', 'orders.total', 'orders.status', 'customers.name')
            ->get();

        return array_map(OrderSummary::fromRow(...), $rows->all());
    }
}
```

The write side loads and saves an `Order` aggregate. The read side runs a query tuned
for the view and returns plain `OrderSummary` DTOs. They share no model. Each side is
simple because it does one job.

## CQRS is not event sourcing

This is the misconception worth killing. CQRS only says "separate the read and write
models". It says nothing about *how* you store data. The example above uses one normal
database and the same `orders` table for both sides. No events, no event store.

[Event sourcing](/course/software-architecture/event-driven-architecture/event-sourcing) (the event-driven architecture chapter, later) is a separate choice that
happens to pair well with CQRS. You can do either without the other. Most CQRS in the
wild is the plain kind you just saw.

## How far should you take CQRS?

CQRS is a dial, not a switch:

- **Same tables, separate code paths** - the example above. Cheapest, and enough most of
  the time.
- **Same database, dedicated read models** - a denormalized table or a database view kept
  in sync, so the read is a single fast `SELECT`.
- **A separate [read database](/course/software-architecture/event-driven-architecture/read-models-and-cqrs)** - updated asynchronously from the write side, for
  read-heavy systems at scale. This is where eventual consistency enters.

Start at the top. Move down only when a real read problem pushes you there.

## Common mistake: applying CQRS everywhere

CQRS shines in a read-heavy area with a genuinely complex write model. Applied to plain
CRUD, it just doubles your classes for no gain - a settings screen does not need a command
bus and a separate read stack. Split the few areas that hurt; leave the boring ones as
simple Eloquent calls.

## FAQ

### What is CQRS?

CQRS stands for Command Query Responsibility Segregation. It means you use one model to
change state (commands, which go through your domain and enforce rules) and a different
model to read state (queries, shaped for the screen and free to bypass the domain).

### Is CQRS the same as event sourcing?

No. CQRS is only about separating the read and write models. Event sourcing is a separate
decision about storing data as a log of events. They combine well, but you can use CQRS
with one ordinary database and no events at all.

### Do I need two databases for CQRS?

No. Plenty of CQRS uses a single database and even the same tables for both sides - the
split is in the code, not the storage. A separate read database is an optional later step
for read-heavy systems, and it brings eventual consistency with it.

### When should I use CQRS?

When one part of the system has both a complex write model (real invariants) and demanding
reads (many joins, per-screen shapes) that fight each other. For simple CRUD it is
over-engineering - reach for it locally, in the areas that actually hurt, not everywhere.
