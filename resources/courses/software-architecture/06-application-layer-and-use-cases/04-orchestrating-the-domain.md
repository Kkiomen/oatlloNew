---
title: "Orchestrating the domain"
slug: orchestrating-the-domain
seo_title: "Orchestrating the Domain: A Use Case in PHP"
seo_description: "How a use case loads aggregates via a repository, calls domain methods and saves - wiring collaborators without holding business rules, with a worked PHP handler."
---

The application layer orchestrates - here is what that looks like in code. A use case
follows the same three-beat shape almost every time: **load**, **do**, **save**. Load the
aggregate you need through a repository, call a method on it to do the real work, save it
back. Everything else is wiring.

## The shape of a use case

```text
load  ->  the repository gives you an aggregate
do    ->  you call a domain method; the aggregate enforces its rules
save  ->  the repository persists the changed aggregate
```

The handler is the conductor. It does not play any instrument - it does not calculate
totals, check limits or decide what "valid" means. It points at the domain objects that do,
in the right order. If you find an `if` about business rules in a handler, that `if` is in
the wrong file.

## A worked handler

Here is a complete use case for adding an item to an order. Read it top to bottom - notice
how little it actually *decides*.

```php
<?php

final class AddItemToOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductCatalog $catalog,
    ) {}

    public function handle(AddItemToOrderCommand $command): void
    {
        // 1. LOAD - fetch the aggregate through its repository
        $order = $this->orders->byId(new OrderId($command->orderId));

        if ($order === null) {
            throw new OrderNotFound($command->orderId);
        }

        // a collaborator the domain method needs
        $price = $this->catalog->priceOf($command->sku);

        // 2. DO - the aggregate enforces its own rules here
        $order->addItem($command->sku, $command->quantity, $price);

        // 3. SAVE - persist the changed aggregate
        $this->orders->save($order);
    }
}
```

Walk through the responsibilities:

- **Loading** goes through `OrderRepository` - an interface (a
  [port](/course/software-architecture/hexagonal-architecture/ports)) the domain defines and
  the infrastructure implements. The handler depends on the interface, not on Eloquent.
- The **not-found check** is orchestration, not a business rule: "you cannot act on
  something that does not exist" is about wiring, so it is fine here. Compare that with
  "an order over its credit limit is rejected" - that belongs *inside* `addItem`.
- **Doing** is one line: `addItem`. Whether the quantity is allowed, whether the order is
  still open, how the total recalculates - all of that lives in the `Order`
  [aggregate](/course/software-architecture/ddd-tactical-patterns/aggregates), which
  protects its own invariants.
- **Saving** goes back through the repository. The handler never writes SQL.

## Wiring collaborators, not rules

Notice `ProductCatalog`. The domain method needs a price, but looking up a price is not the
order's job - it comes from another part of the system. The handler is the natural place to
fetch that collaborator and pass it in. This is the difference between *wiring* and
*deciding*: the handler gathers what the domain needs and hands it over; the domain uses it
to make the decision.

Sometimes the coordination between two aggregates is genuinely domain logic - then it goes
in a
[domain service](/course/software-architecture/ddd-tactical-patterns/domain-services-and-factories),
and the handler just calls that service. The rule of thumb holds: if it is a *rule*, push it
down into the domain; if it is *plumbing*, the handler keeps it.

## Keep handlers dependency-light

A handler takes its collaborators through the constructor - repositories, ports, maybe a
domain service - and nothing else. If a handler needs six dependencies, that is a smell: the
use case is probably doing too much, or rules that belong in the domain have crept up into
it. Split the use case or push logic down. A healthy handler reads like a short recipe.

There is a testing payoff to this discipline that is easy to miss. When the only things a
handler touches are interfaces passed to its constructor, you can test the whole use case
with in-memory fakes - an array-backed `OrderRepository`, a stub `ProductCatalog` - and no
database at all. A handler you cannot test without booting the framework is usually one that
reached past its constructor for something it should have been given.

## Common mistake

The common mistake is the handler that slowly grows a brain. It starts as load-do-save, then
someone adds "just a quick check" - a discount calculation, a status comparison, a loop that
sums line items. Each addition looks harmless; together they turn the handler into a second
domain model that lives outside the domain, untested by the domain's own tests and invisible
to anyone reading the aggregate. Every time you are about to add logic to a handler, ask:
"is this a business rule?" If yes, it goes in the domain. The handler only ever coordinates.

## FAQ

### What does a use case actually do

It loads the aggregate it needs through a repository, calls a domain method to do the real
work, and saves the result. It wires collaborators together in the right order but holds no
business rules of its own.

### Where does the not-found check belong

In the handler. "You cannot act on something that does not exist" is orchestration - it is
about whether there is anything to run the rule on. Business rules like limits and state
transitions belong inside the aggregate.

### Can a handler call more than one aggregate

Yes, but be careful. If it just loads two aggregates and saves them, that is orchestration.
If it starts *coordinating rules* between them, move that coordination into a domain service
and have the handler call the service instead.
