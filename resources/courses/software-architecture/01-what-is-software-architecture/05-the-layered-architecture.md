---
title: "The layered architecture"
slug: the-layered-architecture
seo_title: "Layered Architecture Explained: Layers & Dependencies"
seo_description: "The classic layered architecture: presentation, application, domain and infrastructure, the dependency direction, and the common domain-database leak."
---

The **layered architecture** is the classic starting structure, and the one most teams use
without naming it. It's the natural first way to act on the
[domain-versus-infrastructure split](/course/software-architecture/what-is-software-architecture/domain-vs-infrastructure)
you just learned, so it's where we begin.

## The idea: horizontal layers

You split the code into horizontal layers, each with one job, stacked on top of each other.
A request enters at the top and travels down; data comes back up.

```text
   Presentation     controllers, views, API responses
   -------------------------------------------------
   Application      use cases, coordinating a request
   -------------------------------------------------
   Domain           business rules and concepts
   -------------------------------------------------
   Infrastructure   database, HTTP clients, framework
```

Four layers you'll see again and again:

- **Presentation** - how the outside world talks to your app. Controllers, views, JSON
  responses. It takes input and shows output; it holds no business rules.
- **Application** - the thin coordinator. It receives a request from presentation and
  drives the steps needed to fulfil it, calling the domain to do the actual thinking.
- **Domain** - the business rules, exactly as in the last lesson. The valuable core.
- **Infrastructure** - the technical details that make it all run: the database, external
  services, the framework itself.

## The dependency direction

The layers alone aren't the point. **Which way the dependencies point** is the point.

The rule: **each layer depends only on the layer below it.** Presentation knows about the
application layer. The application layer knows about the domain. Nothing points upward - the
domain never reaches up into presentation, and it never reaches into infrastructure either.

```text
   Presentation  --depends on-->  Application
   Application   --depends on-->  Domain
   Infrastructure --depends on--> Domain
```

Notice the last line. Infrastructure depends on the domain, not the reverse - the same
one-way arrow from the previous lesson. The database code knows how to save an `Order`
because it's built to serve the domain. The `Order` itself knows nothing about the
database. Keep the arrows flowing toward the domain and the core stays clean.

## A quick walk-through

A request to place an order flows down the stack, each layer doing only its job:

```text
   HTTP request
       |
   [Presentation]  OrderController reads the request
       |
   [Application]   PlaceOrder coordinates the steps
       |
   [Domain]        Order decides its own rules (total, shipping)
       |
   [Infrastructure] OrderRepository saves it to the database
```

The controller doesn't know SQL. The `Order` doesn't know HTTP. Each layer has one
responsibility and trusts the next to do its part. That separation is what makes any single
piece easy to change.

## The common leak: domain depending on the database

Here's the mistake that quietly undoes the whole thing, and it's extremely common.

The domain layer starts depending on infrastructure. An `Order` object reaches into the
database itself, or a business rule is written as a raw query. The arrow flips: now the
domain depends on the layer below the one it should.

```php
// Leak: a domain object reaching into infrastructure
final class Order
{
    public function markShipped(): void
    {
        // A business object should not know the database exists
        DB::table('orders')->where('id', $this->id)->update(['shipped' => true]);
    }
}
```

The moment this happens, your domain can't be understood or tested without a database, and
you can't change the database without touching business rules. The valuable core is welded
to a technical detail - the exact problem layering was meant to prevent. The domain should
hold the rule ("this order is now shipped") and let infrastructure handle the saving.

In practice the leak is rarely as loud as a raw query in a business method. The quiet
version is inheritance: a domain `Order extends Model` is already welded to the framework
and the database before you write a single rule, because the base class carries all of it.
Spotting a stray `DB::` call is easy; spotting the dependency you inherited takes a second
look.

The later chapters on hexagonal architecture exist largely to make this leak impossible.
For now, just learn to spot it.

## Common mistake: skipping the application layer

Beginners often collapse presentation and application together - controllers that both
handle the HTTP request *and* coordinate all the business steps. It works for a while, then
controllers balloon into thousand-line files doing everything. Keeping a thin application
layer between the web and the domain gives each request a clear home that isn't tangled up
with HTTP.

## FAQ

### What are the layers in a layered architecture?

Most commonly four: presentation (controllers, views), application (coordinating a
request), domain (business rules) and infrastructure (database, framework, external
services). Each has one job, and requests flow down through them.

### Which way should dependencies point in a layered architecture?

Downward, toward the domain. Each layer depends only on the layer below, and infrastructure
depends on the domain rather than the reverse. The domain should never depend on the
database or the framework.

### What is the most common mistake in layered architecture?

Letting the domain depend on infrastructure - a business object running its own queries, or
a rule written as raw SQL. It welds the valuable core to a technical detail and defeats the
purpose of separating the layers.
