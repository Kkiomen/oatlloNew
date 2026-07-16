---
title: "Adapters"
slug: adapters
seo_title: "Adapters in Hexagonal Architecture (PHP Example)"
seo_description: "An adapter implements a port: a MysqlOrderRepository or a controller. Swap adapters without touching the domain. Ports and adapters explained in PHP."
---

An **adapter** is the code that plugs into a port. Where a port is an interface the domain
owns, an adapter is a concrete class that implements it, living on the outside and holding
all the technical detail the domain refused to know about. Adapters are where MySQL, HTTP
and mail servers finally appear.

## An adapter for a driven port

Recall the `OrderRepository` [port from the last lesson](/course/software-architecture/hexagonal-architecture/ports). It said *what* the domain needs -
save an order, find one by id - in domain language. The adapter says *how*, using a real
database:

```php
<?php

final class MysqlOrderRepository implements OrderRepository
{
    public function __construct(
        private PDO $db,
    ) {}

    public function save(Order $order): void
    {
        $stmt = $this->db->prepare(
            'REPLACE INTO orders (id, total_cents) VALUES (:id, :total)'
        );

        $stmt->execute([
            'id' => $order->id()->toString(),
            'total' => $order->total()->cents(),
        ]);
    }

    public function findById(OrderId $id): ?Order
    {
        // load a row and rebuild an Order object...
    }
}
```

All the SQL lives here, on the outside, behind the port. The domain calls `save()` and
`findById()` through the `OrderRepository` interface and never learns that a `PDO` and a
`REPLACE INTO` are doing the work.

## Swapping adapters without touching the domain

The payoff is that a port can have more than one adapter, and the domain cannot tell the
difference. Testing against a database is slow, so tests use an in-memory adapter:

```php
<?php

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id()->toString()] = $order;
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->orders[$id->toString()] ?? null;
    }
}
```

Same port, different adapter. Production wires in `MysqlOrderRepository`; tests wire in
`InMemoryOrderRepository`. The `CancelOrder` class from the previous lesson runs unchanged
against either, because it only ever knew the interface. Move to PostgreSQL later and you
write one new adapter - the domain and every use case stay exactly as they are.

## Adapters for driving ports too

Adapters exist on the driving side as well. A controller is an adapter: it turns an HTTP
request into a call on a driving port, then turns the result back into a response.

```php
<?php

final class PlaceOrderController
{
    public function __construct(
        private PlaceOrder $placeOrder,
    ) {}

    public function __invoke(Request $request): Response
    {
        $command = new PlaceOrderCommand(
            customerId: $request->input('customer_id'),
        );

        $orderId = $this->placeOrder->handle($command);

        return new JsonResponse(['id' => $orderId->toString()], 201);
    }
}
```

The controller holds the HTTP knowledge - reading input, status codes, JSON. A CLI command
that placed an order would be a different adapter calling the same `PlaceOrder` port. The
domain offers one use case; many adapters can drive it.

## How the wiring happens

Ports and adapters only meet at one place: where the app starts up and decides which
adapter fills each port. In Laravel that is a service provider binding an interface to an
implementation. This "assembly point" is the only code that knows both sides at once,
which is exactly why the domain can stay ignorant of the adapters.

Forgetting this binding is the classic first-run stumble. Type-hint `OrderRepository`
somewhere, skip the `$this->app->bind(...)` line, and Laravel throws `Target
[OrderRepository] is not instantiable` - the container hit an interface and had no adapter
to build. The error is really telling you the assembly point is missing an entry.

## Common mistake: leaking database types out of the adapter

An adapter's job is to fully translate between the outside and the domain. If
`findById()` returns an Eloquent model or a raw array instead of a rebuilt `Order`, the
database's shape escapes through the port and every caller starts depending on it. Keep the
translation complete: an adapter takes domain objects in and hands domain objects back, so
the technical world stops at the adapter's edge.

## FAQ

### What is the difference between a port and an adapter?

A port is the interface the domain defines; an adapter is the concrete class that
implements it on the outside. One port can have many adapters (MySQL, in-memory, a fake),
and the domain treats them all the same.

### Is a controller an adapter?

Yes. A controller is a driving adapter: it adapts an HTTP request into a call on a driving
port and adapts the result back into a response. A CLI command doing the same is another
driving adapter.

### How do adapters get connected to ports?

At startup, in one wiring place - a Laravel service provider that binds each port interface
to a chosen adapter. That is the only spot that knows both the port and the concrete
adapter, keeping the knowledge out of the domain.
