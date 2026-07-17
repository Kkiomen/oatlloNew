---
title: "The anemic domain model"
slug: the-anemic-domain-model
seo_title: "The Anemic Domain Model: DDD's Most Common Anti-Pattern"
seo_description: "The anemic domain model is an anti-pattern: entities that are bags of getters and setters with the logic in services. Why it defeats DDD, with PHP 8.4 before and after."
---

The **anemic domain model** is the most common way Domain-Driven Design goes wrong. Your
entities carry data, getters and setters, but no behaviour. Every rule - "an order can't
be paid twice", "you can't ship an empty order" - lives in a service somewhere else. The
objects look object-oriented. They're really just data structures with a class keyword.

## The problem: data here, rules over there

Here's an anemic `Order`. It exposes everything and protects nothing.

```php
<?php
declare(strict_types=1);

final class Order
{
    public string $status = 'pending';
    /** @var string[] */
    public array $items = [];

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getItems(): array { return $this->items; }
    public function setItems(array $items): void { $this->items = $items; }
}
```

The rules have to go *somewhere*, so they end up in a service:

```php
final class OrderService
{
    public function pay(Order $order): void
    {
        if ($order->getStatus() === 'paid') {
            throw new \DomainException('Order already paid.');
        }
        if ($order->getItems() === []) {
            throw new \DomainException('Cannot pay for an empty order.');
        }
        $order->setStatus('paid');
    }
}
```

This works, but look at what it costs. The `Order` can be put into any state by anyone -
`$order->setStatus('shipped')` from any corner of the app, no checks. The rule about paying
twice lives in `OrderService::pay()`, but nothing forces the rest of the codebase to go
through it. A second service, a controller, a queued job - each can mutate the order
directly and skip every guard. The knowledge about what a valid order *is* has leaked out
of the `Order` class and scattered across the system.

## Why this defeats the point of DDD

The whole idea of an [entity](/course/software-architecture/ddd-tactical-patterns/entities)
is that it **owns and protects its own state**. When behaviour lives in services and data
lives in dumb objects, you've split the two things DDD works hard to keep together. You get
the ceremony of DDD (entities, value objects, repositories) with none of the payoff:

- Rules are duplicated, because every service that touches an order re-checks (or forgets
  to check) the same things.
- Invalid states are reachable, because the setters let anyone write anything.
- The [ubiquitous language](/course/software-architecture/ddd-strategic-design/ubiquitous-language)
  disappears. Nobody says "pay the order"; they say "set status to paid", which is a
  database thought, not a business one.

An anemic model is really a
[layered architecture](/course/software-architecture/what-is-software-architecture/domain-vs-infrastructure)
in disguise, with the domain layer left empty.

## The fix: move the logic into the entity

Push the rules back where the data lives. The setters go away; named operations replace
them, and each one guards the state it changes.

```php
<?php
declare(strict_types=1);

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
}

final class Order
{
    private OrderStatus $status = OrderStatus::Pending;
    /** @var string[] */
    private array $items = [];

    public function __construct(public readonly string $id) {}

    public function addItem(string $sku): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new \DomainException('Cannot change a paid order.');
        }
        $this->items[] = $sku;
    }

    public function pay(): void
    {
        if ($this->status === OrderStatus::Paid) {
            throw new \DomainException('Order already paid.');
        }
        if ($this->items === []) {
            throw new \DomainException('Cannot pay for an empty order.');
        }
        $this->status = OrderStatus::Paid;
    }
}
```

Now `pay()` is the *only* way to pay, and it always runs the checks. There's no setter to
sneak around, so the order can't reach an invalid state no matter who calls it. The service
shrinks to orchestration - load, call, save - which is exactly what the
[application layer](/course/software-architecture/application-layer-and-use-cases/the-application-layer)
is for:

```php
final class PayOrderHandler
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(string $orderId): void
    {
        $order = $this->orders->byId($orderId);
        $order->pay();          // the rule lives in the entity
        $this->orders->save($order);
    }
}
```

The service still exists, but it no longer *knows the rules*. It coordinates; the entity
decides. That's the line to hold.

One warning if you build on Laravel: Eloquent pushes you toward anemia by default. `$fillable`,
mass assignment and public magic attributes hand you a setter for every column for free, so
the frictionless path is a bag of fields. A rich entity there usually means a separate domain
object that Eloquent maps *to* - the model becomes a driven adapter, not the entity itself.

## When "anemic" is actually fine

Not every object needs behaviour. A
[DTO](/course/software-architecture/application-layer-and-use-cases/dtos-and-mapping)
carrying request data, or a read model built for a query, is *supposed* to be a plain bag
of fields - it has no rules to protect. The anti-pattern is specifically an **entity** that
should hold business rules but doesn't. Data-only is the goal for data; it's a smell for
domain objects.

## FAQ

### What is the anemic domain model?

It's an anti-pattern where domain entities hold only data (getters and setters) and all the
business logic lives in separate service classes. The objects look object-oriented but
behave like plain data structures, so the domain layer ends up empty.

### Why is the anemic domain model considered bad?

Because it splits data from the rules that protect it. Any code can put an entity into an
invalid state through its setters, rules get duplicated across services, and you pay for
DDD's structure without getting its main benefit: entities that guard their own invariants.

### How do I fix an anemic entity?

Replace setters with named operations (`pay()`, `addItem()`) that contain the rules, make
fields private, and move the checks out of the service and into the entity. The service is
left to orchestrate - load, call the method, save.
