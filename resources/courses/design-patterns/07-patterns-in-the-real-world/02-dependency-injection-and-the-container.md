---
title: "Dependency injection and the container"
slug: dependency-injection-and-the-container
seo_title: "Dependency Injection in Laravel: Service Container"
seo_description: "How dependency injection in Laravel works: type-hint an interface, bind it once, and the service container auto-resolves it via constructor injection."
---

Dependency injection in Laravel is dependency inversion you can actually see running. In
[the dependency inversion lesson](/course/design-patterns/solid/dependency-inversion) you
met the principle - depend on abstractions, not concrete classes - and it stayed abstract
until something wired it up. That something is the **service container**. This lesson walks
the whole loop end to end: an interface, one binding, and Laravel building the object for you.

## The problem: hard-wired dependencies

Say a controller needs to send SMS messages. The naive version creates the sender itself:

```php
class OrderController
{
    public function store()
    {
        $sms = new TwilioSmsSender('sid', 'token'); // hard-wired to Twilio
        $sms->send('+48...', 'Order received');
    }
}
```

Now `OrderController` depends on `TwilioSmsSender` directly. Swapping providers, or using a
fake sender in a test, means editing the controller. That's the tight coupling the
principle warns about.

## Step 1: depend on an interface

Define the abstraction - what you need, not who provides it:

```php
interface SmsSender
{
    public function send(string $to, string $message): void;
}
```

Then have the controller **type-hint the interface** in its constructor. This is
*constructor injection*: the dependency arrives from outside instead of being created
inside.

```php
class OrderController
{
    public function __construct(
        private SmsSender $sms, // ask for the abstraction
    ) {}

    public function store()
    {
        $this->sms->send('+48...', 'Order received');
    }
}
```

## Step 2: bind the interface in the container

The controller asks for a `SmsSender`, but an interface can't be instantiated. You tell the
container which concrete class to hand over. Do it once in the `register` method of
`app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(SmsSender::class, TwilioSmsSender::class);
}
```

One thing worth knowing early: `bind` hands back a *fresh* instance every time the container
resolves it. If the sender is expensive to build or holds a connection you want to reuse,
reach for `singleton` instead - same call, one shared instance for the whole request.

## Step 3: Laravel auto-resolves

You never call `new OrderController` yourself - the router does, through the container. The
container reads the constructor's type-hints, sees `SmsSender`, looks up the binding, builds
a `TwilioSmsSender`, and injects it. This is **auto-resolution**: for concrete
classes the container can even build dependencies without any binding at all, just from the
type-hints.

The payoff is real. To switch providers, change one line in the provider - no controller
edit. In a test, bind a fake and the same controller runs against it:

```php
$this->app->bind(SmsSender::class, FakeSmsSender::class);
```

## Why this is dependency inversion made real

The high-level code (`OrderController`) depends on an abstraction (`SmsSender`). The
low-level detail (`TwilioSmsSender`) also depends on that abstraction by implementing it.
Neither points at the other directly - the container sits in the middle and connects them.
That is exactly the shape dependency inversion describes, and Laravel gives it to you for
free. It's also the same move that
[keeps the domain at the center](/course/software-architecture/hexagonal-architecture/the-domain-at-the-center)
in hexagonal architecture: the core owns the contract, and the infrastructure plugs into it.

## A common mistake

Reaching for the `app()` helper or `App::make()` *inside* your classes to fetch dependencies:

```php
$sms = app(SmsSender::class); // works, but hides the dependency
```

This is the *service locator* [anti-pattern](/course/design-patterns/refactoring-and-anti-patterns/anti-patterns). It works, but the class's real dependencies are
now hidden inside methods instead of declared in the constructor. Prefer constructor
injection: it makes dependencies visible, and it's what makes testing easy.

## FAQ

### What's the difference between dependency injection and the container?

Dependency injection is the *idea* (pass dependencies in from outside). The container is
the *tool* Laravel uses to do it automatically by reading your type-hints.

### Do I have to bind every class?

No. The container auto-resolves concrete classes from their type-hints without any binding.
You only add a binding when you type-hint an **interface** (so it knows which
implementation) or need custom construction logic.

### Where should bindings go?

In a service provider's `register` method - `AppServiceProvider` is fine to start. Keep
binding logic out of controllers and models.
