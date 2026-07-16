---
name: "The Anti-Corruption Layer Pattern"
slug: anti-corruption-layer
short_description: "How to stop a messy third-party or legacy API from leaking its field names and concepts into your clean domain model, with a Laravel example."
language: en
published_at: 2027-06-02 09:00:00
is_published: true
tags: [architecture, php, laravel, ddd, patterns]
---

We integrated a payment provider whose API returned a field called `txn_st` that could be `"0"`, `"1"`, `"P"`, or the string `"CANCELLED_BY_MERCHANT_OR_TIMEOUT"`. All four meant something my code had to react to. Within a month, `txn_st === '1'` checks were scattered across three controllers, a job, and a Blade view. When the provider renamed the field in v2 of their API, we changed it in eleven places and still missed one.

That is the exact failure the Anti-Corruption Layer prevents. It is a translation boundary you put between your domain and someone else's model, so their concepts never touch your code. Below I'll pin down what it actually is, why it's more than a plain adapter, and how to wire one up in Laravel against an API that fights you.

## The problem: a foreign model leaking into yours

Every external system has a model. A CRM thinks in "leads" and "opportunities" with a `stage_id` lookup table. A payment gateway thinks in `txn_st` codes and settlement batches. A legacy monolith thinks in whatever the DBA decided in 2011.

The trouble starts when that model creeps into yours by osmosis. You call the API, get back an array, and it feels wasteful to remap it, so you pass the raw payload around. Now your `Order` carries a `txn_st` property. Your invoice template reads `$response['customer']['billing']['addr_line_1']`. Your business rules are quietly encoded in another company's naming decisions.

This is fine right up until it isn't. The foreign model changes, or you need a second provider, or a new developer asks "what does `st` mean here?" and nobody remembers. The coupling was invisible because it never looked like coupling. It looked like convenience.

## What an Anti-Corruption Layer actually is

An Anti-Corruption Layer (ACL) is a set of classes whose only job is to translate between the external model and your domain model, in both directions. Nothing on your side of the layer knows the external system exists. Nothing crosses the boundary except your own types.

It usually has three moving parts:

- **An adapter** that talks to the foreign system — HTTP calls, SDK, SOAP, whatever the ugliness requires.
- **A translator** that maps the foreign payload to your domain objects and your commands back to the foreign format.
- **Your own domain model or DTOs** — the clean types the rest of the app is allowed to see.

The term comes from Eric Evans' *Domain-Driven Design*, where it protects a bounded context from a foreign one. But you do not need to run full DDD to benefit. If you have ever wrapped a horrible API in a nice interface, you were reaching for this idea; the ACL just says the translation is the point, not an afterthought.

## Why it isn't "just an adapter"

This trips people up, so let me be precise. A plain Adapter (the Gang of Four one) converts one *interface* to another. It makes a square peg call a round hole. The classic example is wrapping a legacy logger so it satisfies your `LoggerInterface` — same data, different method signatures.

An Anti-Corruption Layer protects a *model*, not an interface. It doesn't just rename methods; it refuses to let foreign concepts through at all. The adapter is often one component *inside* an ACL, but the ACL also owns the domain vocabulary and the semantic translation.

Concretely: an adapter might turn `getTxnStatus()` into `status()` and hand you back the string `"1"`. An ACL turns `"1"` into `PaymentStatus::Captured`, a value your domain defined, and your code never learns that `"1"` was ever involved. The difference is whether the foreign model's *meaning* leaks, not just its method names.

| | Plain adapter | Anti-Corruption Layer |
|---|---|---|
| Protects | An interface | A whole domain model |
| Translates | Method signatures | Concepts, vocabulary, invariants |
| Foreign data types | Often pass through | Never cross the boundary |
| Typical size | One class | Adapter + translator + your DTOs |

## Building one in Laravel

Let's wrap a payment API that is genuinely unpleasant. It returns snake-cased JSON, encodes status as those cryptic strings, gives amounts in major units as floats (a bug waiting to happen), and puts the useful error under `meta.reason`.

Start with what your domain wants to see. This is the whole point — you design the clean side first, then bend the foreign data to fit.

```php
<?php

namespace App\Payments;

enum PaymentStatus
{
    case Pending;
    case Captured;
    case Failed;
    case Cancelled;
}

final readonly class PaymentResult
{
    public function __construct(
        public string $reference,
        public PaymentStatus $status,
        public int $amountMinor,   // cents, always an integer
        public string $currency,
        public ?string $failureReason = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Captured;
    }
}
```

Notice `amountMinor` is an `int`. We never let the provider's float reach us — money as a float is how you end up charging someone $9.999999999. The translation is where we fix that.

Next, define the contract the rest of the app depends on. Your controllers and jobs type-hint *this*, never the concrete provider.

```php
<?php

namespace App\Payments;

interface PaymentGateway
{
    public function charge(int $amountMinor, string $currency, string $cardToken): PaymentResult;

    public function status(string $reference): PaymentResult;
}
```

Now the translator. It is deliberately boring — a pure function from foreign shape to domain shape. Boring is the goal; this is the one place that knows the ugly truth, and it has no other responsibilities to hide bugs behind.

```php
<?php

namespace App\Payments\Acme;

use App\Payments\PaymentResult;
use App\Payments\PaymentStatus;

final class AcmePaymentTranslator
{
    public function toDomain(array $payload): PaymentResult
    {
        return new PaymentResult(
            reference: $payload['transaction_id'],
            status: $this->mapStatus($payload['txn_st']),
            amountMinor: (int) round($payload['amount'] * 100),
            currency: strtoupper($payload['ccy']),
            failureReason: $payload['meta']['reason'] ?? null,
        );
    }

    private function mapStatus(string $raw): PaymentStatus
    {
        return match ($raw) {
            '1', 'CAPTURED'  => PaymentStatus::Captured,
            '0', 'PENDING'   => PaymentStatus::Pending,
            'P'              => PaymentStatus::Pending,
            'CANCELLED_BY_MERCHANT_OR_TIMEOUT' => PaymentStatus::Cancelled,
            default          => PaymentStatus::Failed,
        };
    }
}
```

The `match` is where the corruption stops. Every weird code the provider can emit is handled here, once. If they add a fifth status next year, you edit one arm of one `match`, and the failing test tells you exactly where.

Finally the adapter that implements your interface, does the HTTP, and hands the payload to the translator. It never returns raw data.

```php
<?php

namespace App\Payments\Acme;

use App\Payments\PaymentGateway;
use App\Payments\PaymentResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AcmePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly AcmePaymentTranslator $translator,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    public function charge(int $amountMinor, string $currency, string $cardToken): PaymentResult
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/v1/charges", [
                'amount'     => $amountMinor / 100,   // they want major units; contain the sin here
                'ccy'        => strtolower($currency),
                'card_token' => $cardToken,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Acme charge failed: {$response->status()}");
        }

        return $this->translator->toDomain($response->json());
    }

    public function status(string $reference): PaymentResult
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/v1/charges/{$reference}");

        return $this->translator->toDomain($response->json());
    }
}
```

Bind it in a service provider so the rest of the app depends only on the interface. If you want a refresher on how this wiring works, the [Laravel service container](/laravel-service-container) does the heavy lifting here.

```php
public function register(): void
{
    $this->app->bind(PaymentGateway::class, function ($app) {
        return new AcmePaymentGateway(
            new AcmePaymentTranslator(),
            config('services.acme.url'),
            config('services.acme.key'),
        );
    });
}
```

That's the whole layer. Everything above the interface deals in `PaymentResult` and `PaymentStatus`. Everything below deals in `txn_st` and floats. The seam between them is one translator class you can point at and read.

## Testing against the ACL with fakes

The best payoff shows up in tests. Because your app depends on `PaymentGateway`, you can swap in a fake that returns domain objects directly — no HTTP, no fixtures full of `txn_st`, no mocking the provider's quirks in every test.

```php
<?php

namespace Tests\Fakes;

use App\Payments\PaymentGateway;
use App\Payments\PaymentResult;
use App\Payments\PaymentStatus;

final class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private PaymentStatus $nextStatus = PaymentStatus::Captured) {}

    public function charge(int $amountMinor, string $currency, string $cardToken): PaymentResult
    {
        return new PaymentResult('fake_ref_1', $this->nextStatus, $amountMinor, $currency);
    }

    public function status(string $reference): PaymentResult
    {
        return new PaymentResult($reference, $this->nextStatus, 0, 'USD');
    }
}
```

```php
public function test_order_is_marked_paid_on_capture(): void
{
    $this->app->instance(
        PaymentGateway::class,
        new FakePaymentGateway(PaymentStatus::Captured),
    );

    $order = Order::factory()->create();
    app(CheckoutService::class)->pay($order);

    $this->assertTrue($order->fresh()->isPaid());
}
```

The translator gets its own tests — feed it real captured payloads (including that `CANCELLED_BY_MERCHANT_OR_TIMEOUT` monster) and assert the domain object. Then everything else in your suite runs against clean fakes. Two hundred tests never touch the provider's model. Contrast that with mocking the HTTP client everywhere: you'd be pasting foreign JSON into test after test, and every one becomes a place the leak can re-enter.

## The cost, and when it's worth paying

An ACL is not free. You are writing an extra layer — an interface, a translator, DTOs — for data you could have used raw. For a one-off script that reads an API once and throws the result away, that's over-engineering. Don't build a layer to protect a model you don't have.

It earns its keep when at least one of these is true:

- The foreign model is **ugly or unstable** — cryptic fields, breaking changes, values that don't map to your concepts.
- The integration is **used in many places**, so a leak would spread.
- You might **swap providers** or run two at once (a second gateway is just a second adapter + translator behind the same interface).
- The system you're wrapping is a **legacy monolith you're migrating away from**. Here the ACL pairs naturally with the [strangler fig pattern](/strangler-fig-pattern-legacy-migration): the new code talks to a clean interface while the ACL absorbs the old system's model, and when the legacy piece finally dies you delete one translator instead of untangling your whole codebase.

My rule of thumb: if I catch myself about to write a foreign field name inside a controller or a domain entity, that's the signal. The ACL costs a few classes up front. Skipping it costs the eleven-places-and-still-missed-one afternoon I described at the top, except you pay it under deadline.

## FAQ

### Isn't an Anti-Corruption Layer the same as the repository pattern?

They overlap but answer different questions. A repository abstracts *where data lives* — usually your own database. An ACL abstracts *whose model the data is in* — a foreign system's. You can absolutely use both: a repository for your persistence and an ACL for a third-party API. If you're wrapping your own database access, you want the [repository pattern](/repository-pattern-laravel), not this.

### Where should the ACL live in a Laravel app?

Keep it in its own namespace, like `App\Payments`, with the interface and DTOs at the top level and the provider-specific adapter/translator in a subfolder (`App\Payments\Acme`). The rule that matters is dependency direction: the rest of the app depends inward on the interface and DTOs, never on the `Acme` subfolder.

### How thin should the translator be?

As thin as the mapping is honest. If the foreign field maps one-to-one, it's a straight assignment. Where meaning differs — status codes, units, nested-to-flat — put that logic in the translator and test it directly. What the translator must never do is reach out to the database or make its own HTTP calls; keep it a pure function so it stays trivially testable.

### Do I need full Domain-Driven Design to use this?

No. The pattern comes from DDD, but the value — foreign concepts not leaking into your code — is useful in any codebase with an ugly integration. You can adopt just the ACL without bounded contexts, aggregates, or the rest of the vocabulary.

## Where to draw the line

The Anti-Corruption Layer is one of those patterns that looks like ceremony until the day a provider renames a field and you change it in one place, run your tests, and ship before lunch. Start with the interface and the DTO you *wish* the API returned. Write the translator to make reality match that wish. Then never let `txn_st` cross the border again.

If you already have foreign field names scattered through a controller, don't rewrite everything at once. Introduce the interface, route new code through it, and back-fill the old call sites as you touch them. The layer pays for itself the first time the world on the other side of it changes.
