---
title: "A worked module end to end"
slug: a-worked-module-end-to-end
seo_title: "Laravel Repository Pattern: A Worked Module Example"
seo_description: "A worked Laravel module end to end: an Invoice aggregate, a Money value object, the repository pattern as a port with an Eloquent adapter, a use-case handler, and a controller."
---

This lesson builds one small **Billing** module all the way from the domain to the HTTP
controller, so you can watch every piece from the last chapters connect in real Laravel -
including the **repository pattern** wired as a port over Eloquent. The feature is simple:
pay an invoice. Follow the data as it flows in from a request and out to the database.

## The shape we're building

```text
HTTP request
   |
   v
InvoiceController  (driving adapter)
   |
   v
PayInvoice handler (application layer)
   |            \
   v             v
Invoice          InvoiceRepository (port)
(aggregate)          |
   uses Money        v
                 EloquentInvoiceRepository (driven adapter)
                     |
                     v
                  database
```

Each box is a concept you already know. We'll write them from the center out.

## The domain: Money and the Invoice aggregate

`Money` is a [value object](/course/software-architecture/ddd-tactical-patterns/value-objects):
no identity, immutable, defined by its value.

```php
namespace App\Billing;

final class Money
{
    public function __construct(public readonly int $cents)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative.');
        }
    }
}
```

`Invoice` is the [aggregate](/course/software-architecture/ddd-tactical-patterns/aggregates):
it owns its rules and never lets itself reach an invalid state. Here the rule is "you cannot
pay an invoice twice".

```php
namespace App\Billing;

final class Invoice
{
    private bool $paid = false;

    public function __construct(
        public readonly string $id,
        public readonly Money $total,
    ) {}

    public function pay(): void
    {
        if ($this->paid) {
            throw new \DomainException('Invoice is already paid.');
        }

        $this->paid = true;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }
}
```

Notice: plain PHP, no `Illuminate`, and the rule lives *inside* the aggregate. The handler
below never checks `paid` itself - it asks the invoice to pay, and the invoice guards
itself.

## The port: a repository interface

The domain says what it needs to load and store an invoice, as an interface. This is the
[port](/course/software-architecture/hexagonal-architecture/ports).

```php
namespace App\Billing;

interface InvoiceRepository
{
    public function ofId(string $id): ?Invoice;
    public function save(Invoice $invoice): void;
}
```

## The driven adapter: Eloquent implementation

Now the database side. The Eloquent model (`InvoiceRow`) and the mapping between row and
aggregate both stay hidden in this adapter.

```php
namespace App\Billing\Persistence;

use App\Billing\Invoice;
use App\Billing\InvoiceRepository;
use App\Billing\Money;

class EloquentInvoiceRepository implements InvoiceRepository
{
    public function ofId(string $id): ?Invoice
    {
        $row = InvoiceRow::find($id);

        if ($row === null) {
            return null;
        }

        $invoice = new Invoice($row->id, new Money($row->total_cents));

        if ($row->paid) {
            $invoice->pay();
        }

        return $invoice;
    }

    public function save(Invoice $invoice): void
    {
        InvoiceRow::updateOrCreate(
            ['id' => $invoice->id],
            [
                'total_cents' => $invoice->total->cents,
                'paid' => $invoice->isPaid(),
            ],
        );
    }
}
```

`InvoiceRow` is an ordinary Eloquent model (`extends Model`) with a `$table` of `invoices`.
It exists only in this folder.

One thing to notice in `ofId`: rebuilding a paid invoice by calling `$invoice->pay()` reuses
a *behavior* method to restore *state*. It works here because a fresh invoice starts unpaid,
so the guard passes once - but it is a shortcut. As an aggregate grows more rules, that trick
starts firing side effects you did not want on load, and you switch to a dedicated
reconstitution path (a named constructor like `Invoice::fromStorage(...)`) that sets state
without running the rules again.

## The application layer: a use-case handler

One class, one action. The [handler](/course/software-architecture/application-layer-and-use-cases/orchestrating-the-domain)
orchestrates: load, call the domain, save. It puts no rules of its own in the middle.

```php
namespace App\Billing;

use Illuminate\Support\Facades\DB;

final class PayInvoice
{
    public function __construct(private InvoiceRepository $invoices) {}

    public function handle(string $invoiceId): void
    {
        DB::transaction(function () use ($invoiceId) {
            $invoice = $this->invoices->ofId($invoiceId);

            if ($invoice === null) {
                throw new \DomainException('No such invoice.');
            }

            $invoice->pay();
            $this->invoices->save($invoice);
        });
    }
}
```

The handler depends on the `InvoiceRepository` **interface**, not the Eloquent class. It
wraps the work in one [transaction](/course/software-architecture/application-layer-and-use-cases/transactions-and-unit-of-work),
committing once at the end. This is the one spot where a framework facade (`DB`) is fine -
the handler is application code, not the domain core.

## The driving adapter: a controller

The controller's only job is to translate HTTP into a use-case call and back. It holds no
business logic.

```php
namespace App\Billing\Http;

use App\Billing\PayInvoice;
use Illuminate\Http\JsonResponse;

class InvoiceController
{
    public function pay(string $invoice, PayInvoice $payInvoice): JsonResponse
    {
        $payInvoice->handle($invoice);

        return response()->json(['status' => 'paid']);
    }
}
```

## Wiring it together

Bind the port to its adapter in a service provider, and the container injects the right
class into both the handler and the controller:

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(
    \App\Billing\InvoiceRepository::class,
    \App\Billing\Persistence\EloquentInvoiceRepository::class,
);
```

```php
// routes/web.php
Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
```

That is the whole loop. A request hits the controller (driving adapter), which calls the
handler (application layer), which drives the `Invoice` aggregate (domain) and persists it
through the repository interface (port) implemented by Eloquent (driven adapter). Swap the
adapter for an in-memory one in a test and the domain never notices.

## Common mistake: putting the rule in the handler

The tempting shortcut is to write `if ($invoice->isPaid()) { throw ... }` in the *handler*
and leave `Invoice` as a dumb data bag. Do that and the "already paid" rule now lives in
the application layer, where the next feature that pays an invoice will forget to repeat it.
Keep the rule in `pay()` on the aggregate. The handler orchestrates; the domain decides.

## FAQ

### Where does the business rule go - the controller, handler, or model?

In the domain aggregate. Here, "an invoice cannot be paid twice" lives in `Invoice::pay()`.
The controller only translates HTTP, and the handler only orchestrates load-act-save. Rules
in the aggregate are enforced everywhere it is used, not just in one code path.

### Why inject an interface instead of the Eloquent repository directly?

So the application code depends on the port, not the database. In tests you bind an
in-memory `InvoiceRepository` and run the handler with no database at all; in production the
container supplies the Eloquent one. Same handler, swappable edges.

### Is all this structure worth it for one endpoint?

For a single "pay invoice" with one rule, honestly no - plain Eloquent would ship faster.
The layout earns its keep once billing grows several rules and other features start touching
invoices. The next lesson is exactly about telling those two situations apart.
