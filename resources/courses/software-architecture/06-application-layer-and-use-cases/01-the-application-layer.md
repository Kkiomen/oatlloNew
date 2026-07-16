---
title: "The application layer"
slug: the-application-layer
seo_title: "The Application Layer in PHP: Use Cases Explained"
seo_description: "What the application layer is: a thin layer of use cases that orchestrates the domain but holds no business rules, with one handler per action in PHP."
---

Your domain model knows the business rules. A controller knows about HTTP. Neither is the
right home for "when a customer places an order, do these five steps in this order." That
coordination is a job on its own, and it belongs to the **application layer**.

The application layer sits between the outside world (HTTP, CLI, queue jobs) and the
domain. It answers one question: what can this system *do*? Place an order, register a
user, cancel a subscription. Then it runs those actions. It holds no business rules of its
own. It **orchestrates** - loads the right objects, calls domain methods, saves the result.

## One class per use case

The cleanest way to model this is: **one action = one class with one method**. Each such
class is a **use case** (also called an application service or a handler). No god-class
with twenty methods; a small, focused object per thing the system does.

```php
<?php

final class PlaceOrderHandler
{
    public function handle(PlaceOrderCommand $command): void
    {
        // 1. load what we need
        // 2. call the domain to do the actual work
        // 3. save the result
    }
}
```

The name says exactly what it does. When someone asks "where does placing an order
happen?", the answer is a file called `PlaceOrderHandler`. That is a feature, not an
accident - use cases become the readable table of contents of your application.

## It orchestrates, it does not decide

This is the line to hold. The handler wires collaborators together; the *rules* live in
the domain. Look at the difference:

```php
<?php

// WRONG - the rule is in the handler
public function handle(PlaceOrderCommand $command): void
{
    if ($command->total > 10000 && !$customer->isVerified()) {
        throw new OrderTooLargeException();
    }
    // ...
}

// RIGHT - the domain owns the rule; the handler just asks
public function handle(PlaceOrderCommand $command): void
{
    $order = $customer->placeOrder($command->items);
    $this->orders->save($order);
}
```

In the second version, whether a customer *may* place this order is decided inside
`placeOrder()` on the domain object, where the aggregate can enforce its invariants (see
[aggregates](/course/software-architecture/ddd-tactical-patterns/aggregates)). The handler
does not know or care what the rules are. If the rule changes, you edit the domain, not the
application layer.

## Where it sits in the layers

Recall the layered picture from
[the layered architecture](/course/software-architecture/what-is-software-architecture/the-layered-architecture)
and the hexagon from
[what is hexagonal architecture](/course/software-architecture/hexagonal-architecture/what-is-hexagonal-architecture).
The application layer is the ring just outside the domain:

```text
   HTTP / CLI / Queue   (driving adapters)
            |
   Application layer     <- use cases / handlers live here
            |
        Domain           (entities, aggregates, rules)
            |
   DB / APIs / email     (driven adapters)
```

A controller is a **driving adapter**: it turns an HTTP request into a call on a use case
and turns the result into a response. The use case then drives the domain. Keeping the
controller thin and the use case thin (but for different reasons) is the whole game.

One practical tell that you got the boundary right: the handler never type-hints
`Illuminate\Http\Request`. The moment it does, you cannot invoke the same action from a
queued job, an Artisan command or a test without faking an HTTP request. A use case takes a
plain command object, so every entry point can build one.

## Common mistake

The classic mistake is the **fat controller** - putting the orchestration *and* the rules
straight into the controller method. It works for one endpoint, then you need the same
action from a CLI command or a queue job and there is nothing to reuse, so you copy-paste.
Now the rule lives in two places and they drift apart. Extracting a `Handler` gives you one
home for the action that HTTP, CLI and queue can all call. The mirror-image mistake is
putting business rules *in* the handler; that just moves the fat controller one layer in.

## FAQ

### What is the application layer

A thin layer of use cases that coordinates the domain to carry out actions the system
supports. It loads objects, calls domain methods and saves - but it holds no business
rules of its own; those live in the domain.

### What is the difference between the application layer and the domain

The domain contains the business rules and the objects they act on. The application layer
contains the *use cases* that run those rules in the right order for a given action. The
domain decides; the application layer coordinates.

### Is a use case the same as an application service

Yes - "use case", "application service", "handler" and "interactor" all name the same
thing: a small class that carries out one action the system offers. This course uses "use
case" and "handler" interchangeably.
