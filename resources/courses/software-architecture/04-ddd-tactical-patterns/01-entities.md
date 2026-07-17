---
title: "Entities: identity that survives change"
slug: entities
seo_title: "DDD Entities Explained: Identity vs Equality in PHP"
seo_description: "Learn what an entity is in Domain-Driven Design: an object with identity that persists through change. Identity vs equality, with a PHP 8.4 example."
---

An **entity** is a domain object defined by its **identity**, not by its data. An `Order` is
the same order after its status, total, and shipping address have all changed. The fields
move; the thing they describe stays put. That single idea - identity that survives change -
is what makes something an entity.

## What is an entity?

Picture an order in a shop. Today it holds two items and status `pending`. Tomorrow, three
items and status `paid`. Still the same order? Obviously. The customer, the accountant, and
the warehouse all agree it is order #4021, no matter how many fields moved along the way.

Now picture two orders with identical data: same items, same total, same day. The same order?
No. They are two different orders that happen to look alike. Ship one and the other stays
right where it is.

So "the same" has nothing to do with the values inside. It rests on **identity**: a stable
handle that stays constant for the whole life of the object. That is the heart of an entity.

## Identity vs equality

These two words get mixed up constantly, so pin them down:

- **Identity** answers "is this the *same* object over time?" - decided by an id.
- **Equality** answers "do these two objects hold the *same* values right now?" - decided by
  comparing fields.

For entities, identity wins. Two entities count as equal when their ids match, even if some
fields differ - one might just be a slightly staler copy of the same order. [Value objects](/course/software-architecture/ddd-tactical-patterns/value-objects),
which you'll meet in the next lesson, flip this around.

## An entity in PHP

The id is assigned once and never changes. Everything else can.

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

    // The id is set once and is read-only for the rest of the object's life.
    public function __construct(
        public readonly string $id,
    ) {}

    public function addItem(string $sku): void
    {
        $this->items[] = $sku;
    }

    public function markPaid(): void
    {
        $this->status = OrderStatus::Paid;
    }

    // Identity, not data: same order = same id.
    public function sameAs(self $other): bool
    {
        return $this->id === $other->id;
    }
}

$order = new Order(id: 'ord_4021');
$order->addItem('BOOK-1');
$order->markPaid();
// The items and status changed, but $order->id is still 'ord_4021'.
```

The id is `readonly`: change anything you like, but never the identity.

One practical note. Within a single request, an ORM with an identity map hands you the *same*
PHP object for a given id, so `===` happens to work. Load that order again next week, in
another process, and you get a fresh object - `===` is false even though it is the same order.
That gap is exactly why `sameAs()` compares ids instead of object references.

## Common mistake: the anemic entity

The usual trap is an entity that's just a bag of public getters and setters with no behaviour
- an **anemic** entity. All the rules ("an order can't be paid twice", "you can't add items
after shipping") then leak into services elsewhere, and the entity can no longer protect
itself. An entity should own the operations that change it (`markPaid()`, `addItem()`), so the
rules sit next to the data they guard. The course returns to this trap by name later, in the
[anemic domain model lesson](/course/software-architecture/evolving-the-architecture/the-anemic-domain-model).

## FAQ

### Entity vs value object?

An **entity** has an identity that persists through change (an `Order`, a `User`). A **value
object** has no identity and is compared by its values (`Money`, `Email`); replace it and
nothing is "lost". Rule of thumb: if you'd track it over time and ask "which one?", it's an
entity. The next lesson covers value objects in full.

### Is the database primary key the entity's identity?

Not necessarily. The identity is a domain concept - an order number, a customer id. It's
often stored as the primary key, but the domain shouldn't lean on the database to generate it;
you can assign an id (a UUID, say) the moment you create the entity, before it's ever saved.

### Can an entity have no behaviour at all?

It compiles, but it usually shouldn't. An entity with only data and no methods is the anemic
model. Put the rules that protect the entity's state inside the entity itself.
