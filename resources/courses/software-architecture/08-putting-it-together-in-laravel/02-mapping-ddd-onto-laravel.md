---
title: "Mapping DDD onto Laravel"
slug: mapping-ddd-onto-laravel
seo_title: "Mapping DDD onto Laravel: Eloquent, Container, Jobs"
seo_description: "Mapping DDD onto Laravel 11: Eloquent as a persistence adapter behind a repository, the service container as wiring, and jobs and events for domain events."
---

The last five chapters used generic names - aggregate, repository, port, adapter, domain
event. Laravel ships the same ideas under its own labels. **Mapping DDD onto Laravel** is
mostly a translation exercise: line each concept up with the framework tool that plays its
role, and stay honest about where the fit is loose rather than exact.

## The mapping at a glance

```text
DDD / hexagonal concept        Laravel tool
---------------------------    --------------------------------
Repository (a port)            an interface you define
Persistence adapter            an Eloquent model + a class that uses it
Wiring (which adapter to use)  the service container
Application service / handler  a plain class, resolved from the container
Domain event                   a Laravel event, or a queued job
Driving adapter                a controller, console command, or listener
```

Each row is a place where a framework tool stands in for a concept you already know from
[hexagonal architecture](/course/software-architecture/hexagonal-architecture/what-is-hexagonal-architecture).

## Eloquent is an adapter, not your domain

Eloquent is the biggest temptation to get wrong. An Eloquent model *is not* your domain
entity - it is a **driven adapter** for the database. It knows about tables, columns and
SQL, which is exactly what [Chapter 5](/course/software-architecture/hexagonal-architecture/driving-vs-driven-adapters)
said belongs on the outside.

So the domain defines a **repository interface** (a port), and an Eloquent-backed class
implements it:

```php
namespace App\Billing;

interface InvoiceRepository
{
    public function save(Invoice $invoice): void;
    public function ofId(InvoiceId $id): ?Invoice;
}
```

```php
namespace App\Billing\Persistence;

use App\Billing\Invoice;
use App\Billing\InvoiceRepository;

class EloquentInvoiceRepository implements InvoiceRepository
{
    public function save(Invoice $invoice): void
    {
        InvoiceRow::updateOrCreate(
            ['id' => $invoice->id()->value()],
            ['total_cents' => $invoice->total()->cents()],
        );
    }

    public function ofId(InvoiceId $id): ?Invoice { /* map row -> Invoice */ }
}
```

The `InvoiceRow` Eloquent model stays inside the persistence adapter. The rest of the app
depends on the `InvoiceRepository` interface, never on Eloquent. This is the
[repository pattern](/course/software-architecture/ddd-tactical-patterns/repositories)
from Chapter 4, made concrete.

## The container is the wiring

Something has to decide that `InvoiceRepository` means `EloquentInvoiceRepository`. In
Laravel that is the **[service container](/course/design-patterns/patterns-in-the-real-world/dependency-injection-and-the-container)**, bound in a service provider:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Billing\InvoiceRepository::class,
        \App\Billing\Persistence\EloquentInvoiceRepository::class,
    );
}
```

Now any class that type-hints `InvoiceRepository` in its constructor gets the Eloquent
version, resolved automatically. In a test you bind an in-memory implementation instead.
The container is how ports meet adapters at runtime.

Reach for `bind` by default - it hands out a fresh adapter per resolve, which is what you
want for a stateless repository. Switch to `singleton` only when the adapter holds state
that should be shared within a request, such as an in-memory identity map. Picking
`singleton` out of habit is how a stale cached row survives longer than it should.

## Jobs and events for domain events

A [domain event](/course/software-architecture/ddd-tactical-patterns/domain-events) says
"something important happened". Laravel gives you two matching tools:

- **Events + listeners** when other parts of the app should react in-process
  (`InvoiceWasPaid` fires, a listener sends a receipt).
- **Queued jobs** when the reaction should happen later or out of the request
  (`dispatch(new GeneratePdf($invoiceId))`).

Both let the billing module announce a fact without knowing who listens - the loose
coupling that Chapter 7's
[event-driven](/course/software-architecture/event-driven-architecture/what-is-event-driven-architecture)
chapter was built on.

## The pragmatic view

This mapping is not one-to-one and you should not force it to be. Eloquent's Active Record
style pulls toward putting logic *on the model*; strict DDD pulls the other way. Most
Laravel teams land somewhere in the middle: a repository interface around persistence for
the parts that carry real rules, and plain Eloquent for the plain CRUD. The point is to
know *which* tool is playing *which* role, so you choose on purpose instead of letting the
framework decide for you. The next lesson pushes the strict version; the last lesson tells
you when not to.

## Common mistake: type-hinting the Eloquent model everywhere

If your handlers and controllers type-hint `InvoiceRow` (the Eloquent model) directly, the
repository interface buys you nothing - the whole app is still welded to Eloquent and the
database. Depend on the `InvoiceRepository` interface in application code, and let only the
adapter class touch the model. That single discipline is what makes the mapping real.

## FAQ

### Should Eloquent models be my domain entities?

They can be in a simple app, but treat that as a shortcut, not the ideal. An Eloquent model
is coupled to the database schema, so using it as your domain entity welds business rules to
persistence. For anything with real domain logic, keep a plain entity and use Eloquent only
inside a repository adapter.

### Where do I bind the repository interface to its implementation?

In a service provider's `register()` method, with `$this->app->bind(Interface::class,
Implementation::class)`. Any class that type-hints the interface then receives the bound
implementation via the container, and tests can rebind it to a fake.

### Are Laravel events the same as domain events?

They play the same role - announcing that something happened - but a Laravel event is a
framework class. A common approach is a plain domain event object inside the domain, which a
handler then translates into a Laravel event or queued job at the boundary.
