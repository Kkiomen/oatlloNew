---
title: "Transactions and unit of work"
slug: transactions-and-unit-of-work
seo_title: "Transactions and Unit of Work in a Use Case"
seo_description: "Why a use case is the natural transaction boundary: commit once at the end, the Unit of Work pattern, and when to dispatch domain events relative to the commit."
---

A use case usually changes more than one thing: it saves an order, decrements stock, writes
a log row. If the order saves but the stock update fails, you are left with a half-done
operation and corrupt data. The fix is to make the whole use case succeed or fail as one
unit - the **Unit of Work** pattern, backed by a database **transaction**. And the use case
is exactly the right place to draw that boundary.

## The use case is the transaction boundary

One use case = one transaction. It maps perfectly: a use case is "one thing the system
does", and either that thing happened or it did not. Begin a transaction when the handler
starts its work, commit **once** at the very end. If anything throws in between, roll the
whole thing back and the database is untouched.

```php
<?php

final class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Inventory $inventory,
        private Database $db,
    ) {}

    public function handle(PlaceOrderCommand $command): void
    {
        $this->db->transaction(function () use ($command) {
            $order = Order::place($command->customerId, $command->items);

            $this->orders->save($order);
            $this->inventory->reserve($command->items);
            // commit happens once, here, when the closure returns
        });
    }
}
```

In Laravel that closure is `DB::transaction()` - it commits if the closure returns and rolls
back if it throws. The important discipline is that there is **one** commit for the whole use
case, not a commit after each `save()`. Saving the order and reserving the inventory are two
halves of one business action; splitting them across two transactions is how you get an order
with no stock reserved.

## The Unit of Work idea

Behind this is a pattern called **Unit of Work**. The idea: while a use case runs, track all
the changes it makes to objects, and write them to the database together, at the end, in a
single transaction. Instead of hitting the database every time an object changes, you collect
the changes and flush them once.

```text
   start use case
      |
      +-- change order      -+
      +-- reserve stock       |  tracked, not yet written
      +-- record adjustment  -+
      |
   commit once  ->  all changes written together (or none)
```

You often get a Unit of Work for free. An ORM's entity manager is one; a database
transaction wrapping the handler gives you the same all-or-nothing guarantee even with a
simpler active-record setup like Eloquent. The point to remember is conceptual: **the use
case decides the boundary; the persistence tool provides the mechanism**. Do not scatter
commits through your domain - the domain should not even know a database exists. Let the
application layer own the transaction.

## Where domain events fit

Aggregates raise
[domain events](/course/software-architecture/ddd-tactical-patterns/domain-events) - things
like `OrderWasPlaced` - to announce that something happened. The question is *when* to
dispatch them, and it hinges on the commit.

Dispatch **after** the commit succeeds. If you dispatch `OrderWasPlaced` before the
transaction commits, a handler might email the customer "your order is confirmed" and then
the transaction rolls back - now there is an email for an order that does not exist. The safe
order is: run the work, commit, *then* release the events.

```text
   1. run the use case, aggregates collect their events
   2. commit the transaction
   3. dispatch the collected events   <- only after commit succeeds
```

So the aggregate *records* events during step 1, but nothing acts on them until the write is
durable. A common way to arrange this is to pull the recorded events off the aggregates after
`save()` and dispatch them once the transaction has committed.

Laravel gives you a lever for this without hand-rolling the timing: set `after_commit` on the
queue connection (or `$afterCommit = true` on a queued listener), and dispatched jobs wait
for the surrounding transaction to commit before they run. It is the framework's version of
the same rule - and a reminder that "dispatch after commit" is a promise you have to arrange,
not something a naive `event()` call gives you by default. The mechanics of building whole
systems on those events - and the harder guarantees around them - are Chapter 7's job
(event-driven architecture); here the rule is simply: **commit first, dispatch after**.

## Common mistake

The most common mistake is committing too early or too often - a `save()` that commits, then
another `save()` that commits, inside one use case. The first write lands, the second fails,
and you own a half-finished operation with no way to undo the first part. The mirror mistake
is dispatching events before the commit, so side effects fire for changes that then roll
back. Both come from losing sight of the boundary. Wrap the use case in one transaction,
commit once at the end, dispatch events only after that commit.

## FAQ

### Why is the use case the transaction boundary

Because a use case is one complete business action - it either happened or it did not. That
matches exactly what a transaction gives you: all the changes commit together, or none do.
One use case, one transaction, one commit at the end.

### What is the Unit of Work pattern

It is tracking all the changes a use case makes and writing them to the database together in
a single transaction, instead of writing each change as it happens. A database transaction or
an ORM entity manager gives you this all-or-nothing behavior.

### Should I dispatch domain events before or after committing

After. Dispatch events only once the transaction has committed successfully. Dispatching
before means a side effect (an email, a downstream update) can fire for a change that then
rolls back, leaving the system inconsistent.
