---
title: "The modular monolith"
slug: the-modular-monolith
seo_title: "The Modular Monolith: A Pragmatic Default"
seo_description: "A modular monolith is one deployment with strong internal module boundaries. Learn why it is the sweet spot for most teams and how modules actually enforce those walls."
---

The [plain monolith](/course/software-architecture/monolith-and-beyond/what-is-a-monolith)
is simple to run but tends to rot: over years, everything ends up calling everything, and
you get the ["big ball of mud"](/course/software-architecture/what-is-software-architecture/boundaries-and-coupling). The **modular monolith** keeps the simple deployment and
adds discipline inside.

## One deployment, strong internal boundaries

A modular monolith still ships as **one application** and usually uses **one database**.
The difference is that the code is split into **modules** with clear edges - each owning a
piece of the business - and modules talk to each other only through small, deliberate
interfaces, not by reaching into each other's internals.

```text
+-----------------------------------------------+
|                One application                |
|                                               |
|  [ Catalog ]     [ Orders ]     [ Billing ]   |
|      |  \___public API___/  \___public API_/  |
|      |                                        |
|   private tables      private tables          |
+-----------------------------------------------+
        Still one deploy. Walls on the inside.
```

Think of it as microservice-style boundaries with monolith-style simplicity. You get the
clean separation without paying the network tax.

## Why it is the pragmatic default

For most teams this is the sweet spot:

- You keep everything easy - one deploy, one debugger, real database transactions.
- You gain **replaceability**: because a module hides its internals, you can rewrite it, or
  even extract it into a separate service later, without touching its callers.
- You get an honest map of your domain. The module boundaries are a first draft of where
  future service boundaries would go, learned cheaply and moved cheaply.

If you later decide a module truly needs to be its own service, a well-drawn module is
already most of the way there. Boundaries are the expensive part of
[microservices](/course/software-architecture/monolith-and-beyond/microservices-overview),
and a modular monolith lets you practice them where mistakes are cheap to fix.

## How modules enforce boundaries

Boundaries only help if they are actually enforced. A few practical mechanisms:

- **A public entry point per module.** Callers use one facade or service class; everything
  else in the module is internal. In Laravel this is often a single application service the
  other modules are allowed to call.
- **No reaching into another module's models or tables.** The `Orders` module never queries
  the `Billing` tables directly. It asks the `Billing` module.
- **Communicate through interfaces and simple data ([DTOs](/course/software-architecture/application-layer-and-use-cases/dtos-and-mapping)).** Modules pass plain data, not
  live Eloquent models loaded from another module's tables.

```php
// Orders module needs to charge a customer.
// It depends on an interface the Billing module provides,
// not on Billing's internal models or tables.

interface Billing
{
    public function charge(string $customerId, int $amountCents): void;
}

final class PlaceOrder
{
    public function __construct(private Billing $billing) {}

    public function handle(Order $order): void
    {
        // ...save the order in the Orders module...
        $this->billing->charge($order->customerId, $order->totalCents);
    }
}
```

`Orders` knows nothing about how `Billing` stores data or talks to a payment provider. If
`Billing` becomes its own service tomorrow, only the class *implementing* `Billing`
changes - `PlaceOrder` does not.

Because tooling will not stop you from breaking a boundary, teams back it up with folder
structure, static analysis rules (for example forbidding one namespace from importing
another's internals), and code review. In practice the boundary that survives is the one a
machine checks: folder conventions and review discipline quietly erode under deadline
pressure, but a static-analysis rule that fails the build does not.

Passing a live Eloquent model across a module edge is a common way the wall leaks without
anyone noticing. The receiving module can walk its relations and lazy-load straight into
the owner's tables, so you are coupled to a schema you were supposed to hide. A plain DTO is
what actually stops that - it carries data, not a door back into the other module.

## Common mistake

Sharing the database freely across modules. The moment `Orders` runs a JOIN straight into
`Billing`'s tables, the wall is gone even if the folders still look separate - you are back
to a big ball of mud with extra steps. Let each module own its tables and expose data
through its public API instead.

## FAQ

### Modular monolith vs microservices - what is the real difference?

Deployment. A modular monolith has strong boundaries but ships as one unit over ordinary
function calls; microservices ship the pieces separately and talk over the network. Same
boundary discipline, very different operational cost.

### Do modules each need their own database?

Not necessarily. Many modular monoliths use one database where each module owns its own
tables and does not touch others'. Separate schemas or databases make the wall stricter,
at the cost of losing cross-module transactions.

### Is a modular monolith just a step toward microservices?

It can be, but it does not have to be. For many products it is the final destination - a
clean, maintainable app. If a module ever needs independent scaling or deployment, the
boundary is already there to extract.
