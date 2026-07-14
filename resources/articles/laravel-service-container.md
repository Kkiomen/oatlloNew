---
name: "Understanding the Laravel Service Container and Binding"
slug: laravel-service-container
short_description: "A practical guide to the Laravel service container: binding, singletons, autowiring, contextual binding and where to register it all."
language: en
published_at: 2026-09-21 09:00:00
is_published: true
tags: [laravel, dependency-injection, php, architecture]
---

The Laravel service container is the piece of the framework I ignored the longest and regretted ignoring the most. For about a year I typed `new SomeClass()` everywhere and treated the container as background magic that "just wired controllers up." Then a client asked me to swap a payment gateway, and I discovered that magic you don't understand is just debt you haven't paid yet.

So this is the guide I wish I'd read earlier. It covers what the container does, every binding style you'll realistically use, and the boring-but-important question of *where* bindings should live. Everything here is accurate for Laravel 11 and 12.

## What the service container really does

Two things, mostly: it resolves dependencies, and it stores instructions for how to build things.

When you type-hint a class in a controller method or a constructor, Laravel reads that type-hint, figures out what it needs to construct the object, and hands it to you already built. That resolution step is the whole point. You describe *what* you want; the container works out *how* to assemble it, including the dependencies of your dependencies.

You'll hear "IoC container" and "DI container" for the same thing. Inversion of control means your class no longer reaches out and grabs its collaborators. It declares what it needs and waits for them to arrive, and the container makes that arrival happen.

Here's the smallest example that shows the idea:

```php
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportGenerator $generator
    ) {}

    public function show()
    {
        return $this->generator->monthly();
    }
}
```

You never wrote `new ReportGenerator()`. Laravel saw the type-hint, built one, and injected it. If `ReportGenerator` itself needs a `Filesystem` and a `Clock`, the container builds those too, recursively. That recursive build is called **autowiring**, and it's the feature that makes the whole thing feel effortless once it clicks.

## Autowiring: the zero-config case

For concrete classes with no interface involved, you usually don't register anything at all. Ask for the class, get the class.

```php
class InvoicePdf
{
    public function __construct(private readonly PdfEngine $engine) {}
}

$pdf = app(InvoicePdf::class);
```

The container inspects the constructor via reflection, sees it needs a `PdfEngine`, builds that first (recursing if `PdfEngine` has its own dependencies), then builds `InvoicePdf`. No binding required. This is why a fresh Laravel app resolves most of your service classes without a single line of configuration.

Autowiring breaks down in exactly one spot: **interfaces**. The container can't run `new PaymentGateway()` when `PaymentGateway` is an interface, because there's nothing concrete to instantiate. That's the moment you have to step in and teach it. Which brings us to binding.

## Binding: teaching the container how to build things

You register bindings so the container knows what to do when a given key is requested. The key is usually a class name or an interface name.

### `bind`: a fresh instance every time

`bind` stores a recipe. Each time you resolve the key, the recipe runs again and you get a brand-new object.

```php
use App\Services\PaymentGateway;
use App\Services\StripeGateway;

$this->app->bind(PaymentGateway::class, StripeGateway::class);
```

Now anywhere in the app that type-hints `PaymentGateway`, the container hands over a `StripeGateway`. Resolve it three times, get three separate instances. Use `bind` for anything that carries request-specific state, or where sharing an instance would be a bug.

### `bind` with a closure: when construction is involved

If building the object takes more than "call the constructor," pass a closure. The closure receives the container itself, so you can pull other dependencies out of it.

```php
$this->app->bind(PaymentGateway::class, function ($app) {
    return new StripeGateway(
        apiKey: config('services.stripe.secret'),
        client: $app->make(HttpClient::class),
    );
});
```

I reach for the closure form whenever a dependency comes from config, from an env value, or from a factory that isn't a plain constructor. The `$app` argument is the running container, so `$app->make()` inside it resolves anything else you've registered.

### `singleton`: build once, share forever

`singleton` runs the recipe the first time the key is resolved, caches the result, and returns that same object for every later resolve within the request lifecycle.

```php
$this->app->singleton(PaymentGateway::class, function ($app) {
    return new StripeGateway(config('services.stripe.secret'));
});
```

Good candidates: an HTTP client with a connection pool, a config-heavy object that's expensive to build, anything that genuinely represents one thing (a logger, a metrics collector). Bad candidates: anything holding per-request state, because in queue workers and Octane the "request lifecycle" can stretch across many jobs and you'll leak state between them. That leak has burned me on an Octane app, and it's miserable to debug because it only surfaces under load.

### `instance`: register an object you already have

Sometimes you've built the object yourself and just want the container to hand it back.

```php
$gateway = new StripeGateway(config('services.stripe.secret'));

$this->app->instance(PaymentGateway::class, $gateway);
```

Every resolve returns that exact object. It behaves like a singleton, except you supplied the instance instead of a recipe. Handy in tests when you want to inject a pre-configured fake.

## Binding an interface to an implementation

This is the binding that earns its keep, and it's the reason the container matters for architecture rather than convenience.

Depend on an interface:

```php
interface PaymentGateway
{
    public function charge(int $amountInCents, string $token): ChargeResult;
}

class StripeGateway implements PaymentGateway { /* ... */ }
class PaddleGateway implements PaymentGateway { /* ... */ }
```

Bind the interface to whichever concrete class you want today:

```php
$this->app->bind(PaymentGateway::class, StripeGateway::class);
```

Your controllers, jobs and services type-hint `PaymentGateway` and never mention Stripe. The day you migrate to Paddle, you change one line in a service provider and the entire application follows. That's not a hypothetical for me. The gateway swap from the intro took ten minutes because the previous developer had done exactly this. It would have taken two days otherwise.

The **why** matters more than the mechanics:

- **Swapping implementations** becomes a config change, not a search-and-replace across the codebase.
- **Testing** gets easy. In a test you bind the interface to a fake that records calls or returns canned data, and the code under test never knows the difference.
- **Boundaries** stay honest. Your domain code talks to `PaymentGateway`, an abstraction you own, instead of a vendor SDK you don't.

If you're applying this to data access rather than external services, the same idea drives the [repository pattern](/blog/repository-pattern-laravel), where you bind a repository interface to a concrete Eloquent implementation.

## Contextual binding: different implementations for different consumers

Occasionally two classes ask for the same interface but need different implementations. A public upload handler writes to S3; an internal report exporter writes to a local disk. Contextual binding covers this without polluting the rest of the app.

```php
use App\Http\Controllers\UploadController;
use App\Reports\ReportExporter;
use Illuminate\Contracts\Filesystem\Filesystem;

$this->app->when(UploadController::class)
    ->needs(Filesystem::class)
    ->give(fn () => Storage::disk('s3'));

$this->app->when(ReportExporter::class)
    ->needs(Filesystem::class)
    ->give(fn () => Storage::disk('local'));
```

Read it as a sentence: *when* this class *needs* that dependency, *give* it this. Everything else in the app still gets the default `Filesystem` binding. You can also `give` a primitive such as a string or an int, which is the clean way to inject a config value into one specific class without a full closure binding.

## Tagging: resolving groups of related services

When you have a set of implementations that should be treated as a collection, tag them and pull the whole group at once.

```php
$this->app->bind(CsvReport::class);
$this->app->bind(PdfReport::class);
$this->app->bind(HtmlReport::class);

$this->app->tag(
    [CsvReport::class, PdfReport::class, HtmlReport::class],
    'reports'
);

// Later, resolve every service carrying that tag:
$this->app->bind(ReportManager::class, function ($app) {
    return new ReportManager(iterator_to_array($app->tagged('reports')));
});
```

`$app->tagged('reports')` returns an iterable of all tagged services, freshly resolved. I use this for plugin-style setups (notification channels, export formats, validation rule sets) where a new implementation joins the group just by being tagged, with no change to the consumer.

## Resolving out of the container: `make`, `resolve`, `app`

Most of the time resolution is automatic and you never touch these. When you do need to pull something manually, you have three doors into the same room:

```php
$gateway = $this->app->make(PaymentGateway::class);
$gateway = resolve(PaymentGateway::class);
$gateway = app(PaymentGateway::class);
```

They're equivalent for basic resolution, so pick whichever reads best where you are. `app()` with no argument returns the container itself. `make()` also accepts an array of extra constructor parameters when you need to pass runtime values the container can't know:

```php
$report = $this->app->make(MonthlyReport::class, ['month' => now()->month]);
```

One habit worth forming: prefer constructor injection over calling `app()` inside your methods. Reaching into the container by hand (the "service locator" style) hides a class's real dependencies, because nothing in the signature tells you what the class needs. Keep `make()` for the genuine edge cases: factories, runtime parameters, code that runs before the container can autowire.

## Where bindings belong: service providers

Bindings go in the `register()` method of a service provider. That's the container's configuration phase, and it's the answer to "where does all this live."

```php
namespace App\Providers;

use App\Services\PaymentGateway;
use App\Services\StripeGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function ($app) {
            return new StripeGateway(config('services.stripe.secret'));
        });
    }

    public function boot(): void
    {
        // Runs after every provider has registered.
        // Safe to resolve services here; do NOT bind here.
    }
}
```

The split matters and trips people up:

- **`register()`** is only for binding into the container. Never resolve a service here, because the provider that supplies it may not have registered yet.
- **`boot()`** runs after all providers have registered, so everything is available. This is where you resolve things, register event listeners, publish config, and so on.

In Laravel 11 and 12 there's no `config/app.php` providers array anymore. Register your custom providers in `bootstrap/providers.php`, or drop the file in `app/Providers` and let the `php artisan make:provider` command wire it up for you. For a one-off binding you don't even need a dedicated provider. The default `AppServiceProvider` is a perfectly fine home until it grows big enough to split out.

## Common pitfalls

A short list of things that have actually cost me time:

- **Using `singleton` for stateful services.** Fine on a normal request, quietly catastrophic under Octane or in queue workers where the instance survives across jobs and leaks state. When in doubt, use `bind`.
- **Resolving inside `register()`.** The dependency may not be bound yet. Move resolution to `boot()`.
- **Binding a concrete class you never needed to bind.** If there's no interface and no custom construction, autowiring already handles it, so the extra binding is noise.
- **Leaning on `app()` everywhere.** It works, but it hides dependencies and makes testing awkward. Constructor injection first, manual resolution only when there's a real reason.
- **Forgetting contextual bindings are per-consumer.** They don't change the global binding, so a class you forgot to configure still gets the default. Double-check every consumer that needs the special case.
- **Type-hinting a concrete class when you meant the interface.** You lose the swap-and-test benefit entirely, because now the concrete class is welded into the signature.

## FAQ

### What's the difference between `bind` and `singleton`?

`bind` runs its recipe on every resolve, so you get a new instance each time. `singleton` runs the recipe once, caches the result, and returns that same instance for the rest of the lifecycle. Reach for `singleton` when the object is expensive to build or genuinely represents one shared thing, and for `bind` when the object holds state that shouldn't be shared.

### Do I need to bind every class I want to inject?

No. Concrete classes are autowired, so the container reads the constructor and builds them for you with zero configuration. You only need explicit bindings for interfaces (the container can't guess which implementation you want) and for objects that need custom construction logic, like pulling values from config.

### When should I create a dedicated service provider versus using `AppServiceProvider`?

Start in `AppServiceProvider` for a handful of bindings. Once a feature accumulates several related bindings, or you want to group them by domain (payments, search, notifications), split them into their own provider for clarity. It's an organizational choice, not a functional one, and the container behaves identically either way.

### Is the service locator pattern (`app()` everywhere) bad?

It's not forbidden, but overusing it hides what a class depends on, since the dependencies no longer appear in the constructor signature. That makes the class harder to test and reason about. Prefer constructor injection and keep `make()`/`app()`/`resolve()` for the cases where injection genuinely can't reach — runtime parameters, factories, and code that runs outside the normal request flow.

## Wrapping up

The Laravel service container stops being mysterious the moment you see it as two jobs: it builds objects by reading their type-hints (autowiring), and it stores your instructions for the cases it can't figure out alone (binding). Concrete classes need nothing. Interfaces need a binding. Shared objects use `singleton`; per-use objects use `bind`. And it all belongs in the `register()` method of a service provider.

Here's a concrete next step: find one place in your codebase where you `new` up a vendor SDK directly, extract an interface for the two or three methods you actually call, bind that interface in a service provider, and type-hint the interface instead. That single refactor is where the container's value stops being theoretical: your tests get a seam to mock against, and your next vendor migration turns into a one-line change.