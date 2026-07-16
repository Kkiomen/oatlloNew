---
title: "Aggregates and the aggregate root"
slug: aggregates
seo_title: "Aggregate Root in DDD Explained (with PHP Example)"
seo_description: "Learn what an aggregate and aggregate root are in Domain-Driven Design: one consistency boundary, one entry point that enforces invariants. PHP example."
---

An **aggregate** is a cluster of related objects you treat as a single unit. One object in the
cluster is the **aggregate root** - the only door in. Outside code talks to the root, the root
guards the rules, and everything inside stays consistent. An `Order` with its line items is
the classic case: you change the order through the `Order`, never by grabbing a line item.

## What is an aggregate root?

Say an order must never exceed 10 items, and its total must always equal the sum of its lines.
If any part of the codebase can grab a line and edit it, that rule has no owner. One place adds
a line and updates the total; another forgets. Now the order's total is wrong and no single
object is to blame.

The aggregate fixes this by drawing a boundary. Inside it, one object - the root - is the only
way in. Every change goes through the root, so the root enforces the rules (the
**invariants**) every single time.

## The aggregate root is the only entry point

```php
<?php
declare(strict_types=1);

// Inside the aggregate - not accessed from outside directly.
final class OrderLine
{
    public function __construct(
        public readonly string $sku,
        public readonly int $quantity,
    ) {}
}

final class Order
{
    private const MAX_LINES = 10;

    /** @var OrderLine[] */
    private array $lines = [];

    public function __construct(
        public readonly string $id,
    ) {}

    // The ONLY way to change what's inside the aggregate.
    public function addLine(string $sku, int $quantity): void
    {
        // The root enforces the invariant, every time.
        if (count($this->lines) >= self::MAX_LINES) {
            throw new DomainException('An order cannot have more than 10 lines.');
        }
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->lines[] = new OrderLine($sku, $quantity);
    }

    public function totalQuantity(): int
    {
        return array_sum(array_map(fn (OrderLine $l) => $l->quantity, $this->lines));
    }
}

$order = new Order(id: 'ord_4021');
$order->addLine('BOOK-1', 2);
$order->addLine('PEN-9', 1);
// You never do: $order->lines[] = ...  - the array is private on purpose.
```

`OrderLine` has no public setters and `$lines` is private, so there's no way to add a line
except through `Order::addLine()` - the "max 10 lines" rule can't be bypassed. The root guards
the whole cluster.

Private isn't quite the whole story, though. If you later add a getter that returns `$lines`
by reference, or hands out live `OrderLine` objects with setters, the boundary leaks anyway
and outside code edits the guts again. Return copies, or make the inner objects immutable, so
the only *mutating* path stays the root.

## Keep aggregates small, reference others by id

It's tempting to pull everything related into one big aggregate - order, customer, catalogue,
invoices, shipments - because they're "connected". Resist. An aggregate is loaded and locked
as a unit, so a fat one is slow, contends when many users touch it, and mixes rules that don't
belong together. Prefer many small aggregates, each guarding one tight set of invariants. A
reliable test: if two objects can be saved in separate transactions without breaking a rule,
they belong to separate aggregates.

When one aggregate needs another, hold the other's **id**, not the whole object.

```php
final class Order
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerId, // reference by id, not a Customer object
    ) {}
}
```

Storing a `Customer` object inside `Order` drags the whole customer aggregate into memory on
every load, blurs the boundary, and tempts code into editing a customer through an order. A
plain `customerId` keeps each aggregate independent - load the customer through its own root,
only when you actually need it.

## FAQ

### What's the difference between an aggregate and an aggregate root?

The **aggregate** is the whole cluster (the `Order` plus its `OrderLine`s). The **aggregate
root** is the single entity that is the entry point to that cluster (the `Order`). Outside code
holds a reference to the root only, never to the parts inside.

### Why can't I just edit the inner objects directly?

Because then the rules have no owner. If any code can change an `OrderLine`, nothing guarantees
the order stays valid. Routing every change through the root lets the root enforce the
invariants each time.

### How do I decide where the aggregate boundary goes?

Group things that must stay consistent together in the same transaction. If two objects can be
saved independently and don't have to be correct at the same instant, put them in separate
aggregates and link by id. This ties back to [bounded
contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts): aggregates
live inside a context, never across it.
