---
name: "Dependency Injection in PHP Without a Framework"
slug: php-dependency-injection-from-scratch
short_description: "How constructor injection and a small autowiring container work in plain PHP, using Reflection, interface bindings, and PSR-11 - no framework."
language: en
published_at: 2027-05-26 09:00:00
is_published: true
tags: [php, architecture, testing]
---

A junior on my team once opened a class, saw a constructor asking for six objects, and asked me why we didn't "just create them inside." Fair question. His version was shorter. It also couldn't be tested without hitting a real payment gateway. That gap - between code that reads simpler and code that survives contact with a test suite - is the whole reason dependency injection exists, and you don't need Laravel or Symfony to get it.

This walks through the idea in plain PHP, then builds a working autowiring container in about 60 lines so the term stops being magic.

## Two things people call "DI"

There are two separate concepts hiding under one acronym, and conflating them causes most of the confusion.

**Dependency injection is a technique.** A class receives its collaborators from the outside instead of building them itself. That's it. No library required - you can do it with a constructor and nothing else.

**A DI container is a tool.** It's an object that knows how to build other objects for you, so you don't wire the whole graph by hand at the top of your app. You can practice injection forever without a container. The container only earns its place once wiring becomes tedious.

Keep them separate in your head. The technique is the valuable part; the container is a convenience that automates it.

## Inverting the `new`

Here's the version the junior wrote:

```php
class OrderService
{
    private StripeGateway $gateway;

    public function __construct()
    {
        $this->gateway = new StripeGateway(config('stripe.key'));
    }

    public function charge(Order $order): void
    {
        $this->gateway->pay($order->total());
    }
}
```

It works. But `OrderService` now decides, permanently, that payments go through Stripe. Want to test `charge()` without a live API call? You can't - the constructor reaches out the moment you instantiate it. Want to swap Stripe for a fake in one environment? You're editing the class.

Injection flips the direction:

```php
class OrderService
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {}

    public function charge(Order $order): void
    {
        $this->gateway->pay($order->total());
    }
}
```

The class no longer names a concrete gateway. It asks for a `PaymentGateway` and lets the caller decide which one shows up. In a test that's a two-line fake:

```php
$fake = new class implements PaymentGateway {
    public array $charged = [];
    public function pay(int $amount): void { $this->charged[] = $amount; }
};

$service = new OrderService($fake);
$service->charge($order);

// assert against $fake->charged - no network, no Stripe account
```

That's the payoff. Not architectural purity - the ability to substitute. The same seam that lets a test pass a fake lets production pass a real gateway, and lets staging pass a sandbox one, without the class ever knowing the difference.

If testability specifically is your goal, I wrote more on the mechanics of it in [testable PHP code](/testable-php-code) - the injection pattern is the foundation the rest of it stands on.

## "Why not just `new` everywhere?"

Because `new` is a hard-coded decision, and hard-coded decisions accumulate. Every `new StripeGateway(...)` buried inside a method is a place you'd have to find and change to swap implementations, and a place a test can't reach past.

The rule I actually follow: a class may `new` its own **data** (value objects, DTOs, collections, a `DateTimeImmutable`) freely. It should not `new` its own **services** - the things with behavior, side effects, or configuration. Those come through the constructor.

The cost is real, though. Constructor injection pushes object creation up to the edges of your app, and eventually something has to assemble the whole graph. Without help, that "something" is a wall of manual wiring:

```php
$config   = new Config();
$logger   = new FileLogger($config->get('log_path'));
$gateway  = new StripeGateway($config->get('stripe.key'), $logger);
$mailer   = new SmtpMailer($config->get('smtp'), $logger);
$orders   = new OrderService($gateway, $mailer, $logger);
```

For a small script that's completely fine - honestly, ship that. But as the graph grows, this list turns into a maintenance chore where adding one constructor argument means editing every call site. That chore is exactly what a container removes.

## Building a container with Reflection

A container's core trick is autowiring: given a class name, it reads the constructor's type-hints and resolves each one recursively, all the way down. PHP's [Reflection API](https://www.php.net/manual/en/book.reflection.php) makes this a couple dozen lines.

```php
class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id): object
    {
        // Explicit factory wins.
        if (isset($this->bindings[$id])) {
            return $this->bindings[$id]($this);
        }

        return $this->resolve($id);
    }

    private function resolve(string $id): object
    {
        $reflector = new ReflectionClass($id);

        if (! $reflector->isInstantiable()) {
            throw new RuntimeException("$id is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor - nothing to inject.
        if ($constructor === null) {
            return new $id();
        }

        $args = array_map(
            fn (ReflectionParameter $p) => $this->resolveParameter($p, $id),
            $constructor->getParameters(),
        );

        return $reflector->newInstanceArgs($args);
    }

    private function resolveParameter(ReflectionParameter $param, string $owner): mixed
    {
        $type = $param->getType();

        // A class type-hint we can recurse into.
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return $this->get($type->getName());
        }

        // Scalars: fall back to the default value if there is one.
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException(
            "Cannot resolve \${$param->getName()} for $owner - no type-hint or default.",
        );
    }
}
```

Now the wall of wiring collapses to one line:

```php
$container = new Container();
$orders = $container->get(OrderService::class);
```

The container walks the constructor of `OrderService`, sees it needs a `PaymentGateway`, tries to build that, and so on down the tree. Anything with a plain class type-hint just works. This is the exact behavior frameworks give you - Laravel's container does precisely this, plus a lot of production hardening you'd rather not write yourself.

Two things to notice. `isBuiltin()` is what separates `PaymentGateway` (recurse) from `int $amount` (can't recurse - needs a default or an explicit binding). And the `RuntimeException` on unresolvable scalars matters: without it you get a silent `TypeError` deep in a stack trace instead of a message that names the parameter and the class.

## Binding interfaces to implementations

The container above breaks the moment it hits an interface. `PaymentGateway` isn't instantiable - `new PaymentGateway()` is meaningless - so `resolve()` throws. That's correct behavior, and it's where `bind()` comes in. You tell the container which concrete class satisfies which contract:

```php
$container->bind(PaymentGateway::class, fn () => new StripeGateway(
    getenv('STRIPE_KEY'),
));
```

Now when autowiring reaches a `PaymentGateway` argument, the explicit binding answers first and hands back a `StripeGateway`. Swapping to a different provider - or a fake in tests - is one line at the composition root, and not a single service class changes:

```php
// In a test bootstrap:
$container->bind(PaymentGateway::class, fn () => new FakeGateway());
```

This is the single most useful thing a container does. Your code depends on abstractions, one place decides the concretions, and that place is trivial to override per environment.

## Singletons vs transient

By default the container above builds a **fresh** object every time you call `get()`. That's "transient" - each request gets its own instance. Usually fine. Sometimes wrong.

A database connection, a config object, an in-memory cache - you want *one* of those shared across the whole request, not a new one per injection point. That's a **singleton**. Here's the addition:

```php
public function singleton(string $id, callable $factory): void
{
    $this->bindings[$id] = function (Container $c) use ($id, $factory) {
        return $this->instances[$id] ??= $factory($c);
    };
}
```

The `??=` operator does the caching: build it once, hand back the same object forever after. Now:

```php
$container->singleton(Database::class, fn () => new Database(getenv('DB_DSN')));

$a = $container->get(Database::class);
$b = $container->get(Database::class);
// $a === $b  →  true
```

Pick deliberately. My default is transient, and I only reach for a singleton when sharing state is the *point* - a connection pool, a config bag, a request-scoped cache. Making a stateful service a singleton by accident is a classic bug: one caller mutates it, another caller inherits the mutation, and you spend an afternoon on a "random" test failure that's actually shared state.

| | Transient | Singleton |
|---|---|---|
| New instance per `get()` | Yes | No - cached |
| Good for | Stateless services, per-use objects | Connections, config, shared caches |
| Risk | Rebuilding something expensive | Leaking state between callers |

## PSR-11: the standard interface

There's an agreed-upon interface for containers so libraries can accept *any* of them without depending on a specific one. It's [PSR-11](https://www.php-fig.org/psr/psr-11/), and it's deliberately tiny:

```php
namespace Psr\Container;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

Two methods. Install `psr/container`, implement them, and your homemade container drops into anything that expects a PSR-11 container - PHP-DI, Laravel's, PHP-League's, and yours all look identical to a consumer:

```php
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    // ...existing code...

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || class_exists($id);
    }
}
```

One caveat people miss: PSR-11 standardizes *reading* from a container (`get`/`has`), not *configuring* it. There's no standard `bind()` or `singleton()` - every implementation invents its own. So the wiring code stays library-specific; only the consumption is portable. That's by design, and it's why you can't blind-swap containers without rewriting the bindings.

## When a container is overkill

I've watched people reach for a container in a 200-line CLI script and turn a readable `main()` into a config-file scavenger hunt. Don't.

Skip the container when the object graph is small enough to assemble by hand in one glance, when there's exactly one composition point, or when the indirection would hide more than it saves. Manual wiring is *more* honest at small scale - you can read the whole graph top to bottom.

Reach for one when you're assembling the same deep graphs in many entry points, when you want per-environment swapping without touching call sites, or when a framework already hands you one. If Laravel is in the project, use its container - it does everything here plus contextual bindings, method injection, and tagged services, all battle-tested. I dug into how its resolution actually works in [the Laravel service container](/laravel-service-container). Rolling your own is a great way to *understand* it; it's rarely the right thing to *ship* over a mature one.

The technique - injecting dependencies through the constructor - carries its weight at every scale. The container is the part you should be picky about.

## FAQ

### Do I need a DI container to use dependency injection in PHP?

No. Injection is just passing collaborators through the constructor - plain PHP, no library. A container only automates the *wiring* once building the object graph by hand gets tedious. Small apps often never need one.

### How does autowiring actually resolve dependencies?

It uses Reflection. The container reads a class's constructor with `ReflectionClass::getConstructor()`, inspects each parameter's type-hint via `getType()`, and for every non-builtin class type it recursively resolves that class too - walking the whole dependency tree until it hits classes with no dependencies.

### What's the difference between `bind` and `singleton`?

`bind` registers a factory that runs on every `get()`, so each caller gets a fresh instance (transient). `singleton` runs the factory once and caches the result, so every caller shares the same object. Use singletons for connections and config; use transient for stateless services.

### Is PSR-11's ContainerInterface enough to configure a container?

No. PSR-11 only standardizes `get()` and `has()` - reading from the container. Registering bindings and singletons is implementation-specific, so wiring code stays tied to whichever container you chose even though consumers can treat any container the same.

## Where to go from here

Write injection into your next class by default: services in the constructor, data built inline. That habit alone makes the code testable and swappable, container or not. Then, if the wiring starts to hurt, build the ~60-line container above once so the framework version stops feeling like a black box - because now you know it's just Reflection reading a constructor. Everything past that is convenience layered on the same idea.
