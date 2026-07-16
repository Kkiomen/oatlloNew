---
title: "Ports"
slug: ports
seo_title: "Ports in Hexagonal Architecture Explained (PHP)"
seo_description: "A port is an interface the domain defines: driven ports for what it needs (OrderRepository) and driving ports for what it offers. With a PHP 8.4 example."
---

A **port** is an interface that the domain owns. It describes a need or an offer in the
domain's own words, without saying how it is fulfilled. Ports are the only doors in and
out of the hexagon. Because they are interfaces, the domain can depend on them while the
real technology stays outside.

## Why the domain defines the interface

The trick that makes the dependency rule work is where the interface lives. The interface
belongs to the **domain**, not to the database code. The domain says "I need something
that can save orders" by declaring `OrderRepository`. The database code outside then
implements that interface. The arrow points inward: the database depends on the domain's
interface, not the reverse.

This is the difference between a port and an ordinary interface: a port is defined by the
side that needs it, in the language of the business.

## Two kinds of port

Ports come in two directions.

```text
   driving port                            driven port
   "what the app offers"                   "what the app needs"

   PlaceOrder (use case)  --> DOMAIN -->  OrderRepository
                                          PaymentGateway
```

- A **driven port** describes what the domain needs from the outside - saving data,
  sending mail, charging a card. Example: `OrderRepository`. The domain calls it; an
  adapter implements it. (Also called a secondary port.)
- A **driving port** describes what the domain offers to the outside - a use case the app
  can perform. Example: a `PlaceOrder` interface. The outside calls it; the application
  implements it. (Also called a primary port.)

Both are just PHP interfaces. The direction is about who calls whom, which the next lesson
on driving vs driven adapters makes concrete.

## A driven port in PHP

Here is the port for saving and loading orders. It is phrased in domain terms - orders and
IDs - with no hint of SQL.

```php
<?php

interface OrderRepository
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;
}
```

The domain uses this interface without ever knowing what is behind it:

```php
<?php

final class CancelOrder
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(OrderId $id): void
    {
        $order = $this->orders->findById($id);

        $order?->cancel();

        if ($order !== null) {
            $this->orders->save($order);
        }
    }
}
```

`CancelOrder` depends on the `OrderRepository` port, not on MySQL. Swap what implements the
port and this class does not change a line.

A worry that comes up in review: if there is only ever one real implementation, is the port
just ceremony? Usually not. The second implementation is the test double - an in-memory fake
that lets you exercise `CancelOrder` without a database. A port that earns nothing but
faster tests has already earned its place.

## A driving port in PHP

A driving port names a thing the application can do, so callers depend on an interface
rather than a concrete class:

```php
<?php

interface PlaceOrder
{
    public function handle(PlaceOrderCommand $command): OrderId;
}
```

A controller or a CLI command calls `PlaceOrder` without knowing how orders are placed.
The full shape of these use cases - commands, the application layer that implements them -
is the subject of Chapter 6; here the point is only that the entry point is an interface
the domain side defines.

## Common mistake: putting framework types in the port

A port must speak the domain's language. The moment its signature mentions
`Illuminate\Http\Request`, an Eloquent model, or a `Collection`, the interface has dragged
the framework into the center and the port is no longer a clean door. Keep port signatures
built from domain types (`Order`, `OrderId`, `Money`) and plain values. Translating
framework types into domain types is the adapter's job, not the port's.

## FAQ

### What is the difference between a port and an adapter?

A port is the interface (the socket); an adapter is the implementation that plugs into it
(the plug). The domain defines ports; the outside provides adapters. Adapters are the next
lesson.

### What is the difference between a driving and a driven port?

A driving port is something the app offers and the outside calls (a use case). A driven
port is something the app needs and calls itself (a repository, a mailer). Driving is "in",
driven is "out."

### Is a repository interface a port?

Yes. A repository interface like `OrderRepository`, defined by the domain and implemented
by the database code, is a textbook driven port. You met repositories in Chapter 4; naming
them ports is the same idea seen from the architecture side.
