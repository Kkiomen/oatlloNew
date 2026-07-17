---
title: "Domain services and factories"
slug: domain-services-and-factories
seo_title: "Domain Services and Factories in DDD (PHP Examples)"
seo_description: "Learn domain services for logic that fits no single entity, and factories that build complex aggregates in a valid state. Clear PHP 8.4 examples in DDD."
---

Most domain logic belongs inside an entity or value object. But some operations fit no single
one of them, and some aggregates take more than a constructor to build correctly. Those two
gaps are what **domain services** and **factories** fill - rounding out the tactical toolkit
without watering down your entities.

## What is a domain service?

Some operations touch several objects at once and don't clearly belong to any of them. Take
transferring money between two accounts. It isn't really the *source* account's job (why should
it know about the destination?), nor the *destination* account's. Force it into either and that
entity reaches into the other, bending the model.

That's the signal for a **domain service**: a stateless object holding a piece of domain logic
that spans more than one entity.

```php
<?php
declare(strict_types=1);

final class MoneyTransferService
{
    // Domain logic, but it belongs to neither account alone.
    public function transfer(Account $from, Account $to, Money $amount): void
    {
        // Each account still guards its own rules...
        $from->withdraw($amount); // throws if insufficient funds
        $to->deposit($amount);
    }
}
```

Two things make this a proper *domain* service. It's **stateless** - it holds no data of its
own, it just coordinates. And it holds **domain** logic, not plumbing: it knows the rule
"withdraw from one, deposit to the other", but it sends no emails, writes to no database, calls
no API. A useful tell here: a domain service takes entities and value objects as arguments and
returns them - the moment it reaches for a repository or the network itself, it has quietly
become an [application service](/course/software-architecture/application-layer-and-use-cases/the-application-layer), which a later chapter covers. Like an entity, a domain service
is named in the [ubiquitous language](/course/software-architecture/ddd-strategic-design/ubiquitous-language).

## Don't reach for a service too soon

The domain service is easy to overuse. Every time you're about to write one, ask first: does
this logic actually belong inside an entity or value object? A rule about a single order goes
in `Order`, not in an `OrderService`. Reach for a domain service only when the logic genuinely
spans several aggregates and has no natural home. Otherwise you're back to the [anemic model](/course/software-architecture/evolving-the-architecture/the-anemic-domain-model) -
entities with no behaviour and services doing everything.

## What is a factory in DDD?

A constructor is fine for a simple object. But some aggregates are born only after several
steps: generate an id, create the root, add initial parts, attach a value object, record a
creation event. Scatter that across the calling code and every caller can get it slightly
wrong - and now half-built aggregates roam the wild.

A **factory** centralises that assembly so an aggregate can only be created **fully formed and
valid**.

```php
<?php
declare(strict_types=1);

final class OrderFactory
{
    public function __construct(
        private readonly IdGenerator $ids,
    ) {}

    // One place that knows how to build a valid Order from scratch.
    public function startForCustomer(string $customerId, Money $openingItem): Order
    {
        $order = new Order(
            id: $this->ids->next(),
            customerId: $customerId,
        );

        $order->addLine($openingItem);   // enforces the aggregate's invariants
        $order->placeInitialHold();      // any required setup step

        return $order; // guaranteed to leave here in a valid state
    }
}

$order = $orderFactory->startForCustomer('cust_7', new Money(1000, 'EUR'));
```

Every caller now gets a correct `Order` the same way, and the messy assembly lives in one
place. Mind the line between this and a repository: [a
repository](/course/software-architecture/ddd-tactical-patterns/repositories) *retrieves* an
aggregate that already exists in storage; a **factory** *creates* a brand-new one.
Reconstituting a saved aggregate from a database row is the repository's job, not the
factory's.

## Common mistake: mislabelling and misplacing them

Two mistakes turn up constantly. The first is calling something a *domain* service when it's
really infrastructure - if it sends email or touches the database, it isn't one. The second is
stuffing so much into services and factories that entities decay into empty data bags with
getters and setters. The goal is the opposite: keep behaviour in entities and value objects,
and use domain services and factories only for the gaps those can't fill. When in doubt, push
logic *down* into the aggregate, not *out* into a service.

## FAQ

### Domain service vs application service?

A **domain service** holds domain logic that spans several entities (transferring money between
accounts) and knows nothing about databases, HTTP, or email. An **application service**
orchestrates a use case - it loads aggregates from a repository, calls the domain, and saves
the result. The application service is covered in a later chapter; here we only mean the domain
kind.

### When do I actually need a factory?

When building a valid aggregate takes more than a plain constructor - several steps, an id
generator, initial parts, a creation event. If `new Order(...)` alone gives you a valid object,
you don't need a factory yet. Add one when the assembly turns complex or gets repeated across
several callers.

### Isn't a stateless domain service just a bag of functions?

Almost - and that's fine. It's stateless on purpose. The point is that it carries *domain*
meaning (named in the ubiquitous language, enforcing a domain rule) and that it's the right
home only when the logic truly belongs to no single entity. If the logic fits one entity, put
it there instead.
