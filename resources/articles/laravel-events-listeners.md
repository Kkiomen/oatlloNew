---
name: "How to Use Laravel Events and Listeners Effectively"
slug: laravel-events-listeners
short_description: "A practical guide to Laravel events listeners: auto-discovery in Laravel 11+, queued listeners, real trade-offs, and the pitfalls to avoid."
language: en
published_at: 2026-09-07 09:00:00
is_published: true
tags: [laravel, events, php, architecture]
---

The first time I reached for Laravel events listeners was to fix a bloated controller. A user registered, and that single action had to send a welcome email, notify Slack, seed a trial workspace, and push a row to our analytics warehouse. The `store` method was 60 lines long and every new "when a user signs up, also..." request made it worse.

Events fixed that. They also, later, caused me a two-hour debugging session where I couldn't figure out why an email was being sent twice. So this is not a love letter. It's a guide to when Laravel events and listeners genuinely help, how they work in Laravel 11 and 12, and where they quietly hurt.

## What events and listeners actually are

The mental model is simple. An **event** is a plain object announcing that something happened: `UserRegistered`, `OrderShipped`, `InvoicePaid`. A **listener** is a class that reacts to it. One event can have many listeners, and the code that fires the event knows nothing about who's listening.

That last part is the whole point. Your controller says "a user registered" and moves on. Whether that triggers one side effect or six is somebody else's problem.

Generate the pair with Artisan:

```bash
php artisan make:event UserRegistered
php artisan make:listener SendWelcomeEmail --event=UserRegistered
```

The `--event` flag type-hints the event in the listener's `handle` method for you, which saves a small amount of boilerplate.

## The big change in Laravel 11+: auto-discovery

If you learned Laravel a few years ago, you remember registering listeners in an array inside `EventServiceProvider`:

```php
// The old way (Laravel 10 and earlier)
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmail::class,
    ],
];
```

You don't need that anymore. Since Laravel 11, listeners are **auto-discovered**. The framework scans your `app/Listeners` directory, reads the type-hint on each listener's `handle` method, and wires it to the matching event automatically. No `$listen` array, no `EventServiceProvider` to maintain. In fact, the default application skeleton no longer ships with an `EventServiceProvider` at all.

So a listener like this is all you write:

```php
namespace App\Listeners;

use App\Events\UserRegistered;

class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        // $event->user is available here
    }
}
```

Laravel sees the `UserRegistered` type-hint and connects the two. That's it.

### When you still want manual registration

Auto-discovery is convenient, but it's not the only option, and sometimes you want the explicit version. You can register listeners manually in the `boot` method of `AppServiceProvider` using the `Event` facade:

```php
use Illuminate\Support\Facades\Event;
use App\Events\UserRegistered;

public function boot(): void
{
    Event::listen(function (UserRegistered $event) {
        // closure-based listener, handy for tiny reactions
    });
}
```

I use closures for trivial, one-off reactions and for listeners that don't deserve their own file. For anything with real logic, a dedicated listener class wins on testability every time.

One practical note: if you rely heavily on auto-discovery, run `php artisan event:cache` in production. Discovery works by reflection, and caching the event-listener map avoids that scan on every request.

## Firing an event

Two ways, and they're equivalent. The helper reads better in most code:

```php
use App\Events\UserRegistered;

// In your controller or action
event(new UserRegistered($user));

// Identical, via the facade
use Illuminate\Support\Facades\Event;
Event::dispatch(new UserRegistered($user));
```

The event object is a normal PHP class. Give it a constructor that accepts whatever context the listeners need:

```php
namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
```

`make:event` scaffolds both traits for you. `Dispatchable` adds a static `dispatch` method, so `UserRegistered::dispatch($user)` works if you prefer that style. `SerializesModels` matters the moment a listener is queued, which I cover below.

## Queued listeners: the feature that earns its keep

Here's where events stop being a code-organization trick and start being an architecture decision.

That welcome email? Sending it inline means the user's HTTP request waits on your mail provider's API. Analytics push? Now they're waiting on that too. Every synchronous side effect you bolt onto registration makes the signup response slower.

Implement `ShouldQueue` on a listener and Laravel pushes it onto your queue instead of running it inline:

```php
namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $backoff = 30; // seconds between retries

    public function handle(UserRegistered $event): void
    {
        // Runs on a queue worker, not in the web request
        Mail::to($event->user)->send(new WelcomeMail($event->user));
    }

    public function failed(UserRegistered $event, \Throwable $e): void
    {
        // Called after the final failed attempt
        report($e);
    }
}
```

The request returns fast; the email goes out a moment later on a worker. The `$tries` and `$backoff` properties give you retry behaviour for free, and `failed()` runs once the listener has exhausted its attempts. If you want more control over retry logic across your whole app, I've written separately about [retrying failed jobs in Laravel](/blog/laravel-retry-failed-jobs), and the same principles apply to queued listeners since they run through the same queue machinery.

A nice detail: you can mix queued and synchronous listeners on the same event. The welcome email can queue while an audit-log listener runs inline. Each listener decides for itself.

## Model events and observers

You don't always have to fire events by hand. Eloquent already fires them: `creating`, `created`, `updating`, `updated`, `deleting`, `deleted`, and more. An **observer** groups handlers for these into one class:

```bash
php artisan make:observer UserObserver --model=User
```

```php
namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        // fires automatically after any User is saved for the first time
    }
}
```

In Laravel 11+ you can attach it with the `#[ObservedBy]` attribute right on the model, which I find cleaner than registering in a provider:

```php
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([UserObserver::class])]
class User extends Model
{
    //
}
```

Observers are great for model-lifecycle concerns like slug generation or cache invalidation. But be careful: model events only fire on Eloquent operations. A `User::where(...)->update([...])` mass update or a raw query **bypasses observers entirely**. I've been burned by assuming an observer ran when it didn't, because a bulk update skipped it.

## When events help, and when they hurt

I'll be blunt about the trade-off, because most tutorials skip it.

**Events shine when:**

- You have multiple, independent side effects reacting to one action.
- Those side effects belong to different domains (email, billing, analytics) and you don't want them tangled together.
- You want to defer slow work off the request via `ShouldQueue`.
- A feature module wants to hook into core behaviour without core code knowing it exists.

**Events hurt when:**

- There's exactly one reaction and it always happens. An event here is just indirection. Call the method.
- Order matters and is subtle. Listener execution order is not something you want load-bearing logic to depend on.
- You need the result back. Events are fire-and-forget by design; if the caller needs a return value, use a service or an action class instead.

The real cost is **traceability**. When you dispatch an event, "find all usages" in your editor stops telling the whole story, because the connection is made by reflection at runtime, not by an explicit call you can jump to. A new developer reading the controller sees `event(new OrderPaid($order))` and has no immediate way to know that fires six listeners across four files. That's the hidden-control-flow tax, and it's real.

My rule of thumb: reach for an event when the *number* of reactions is genuinely open-ended or crosses domain boundaries. If you're decoupling for its own sake on a one-to-one relationship, you're adding a layer you'll curse later. This is the same tension you hit with the [repository pattern](/blog/repository-pattern-laravel) — indirection is a tool, not a virtue, and it costs you when it's applied where it isn't needed.

## Common pitfalls

A few things that have actually bitten me or my team:

- **Double dispatch.** Firing the same event from both a controller and a model observer. Now your listener runs twice. Pick one place to dispatch.
- **Serialization surprises in queued listeners.** A queued listener serializes the event to store it on the queue. If your event holds an Eloquent model, only the model's key is stored and it's re-fetched when the job runs (via `SerializesModels`). If the record was deleted meanwhile, the listener can fail on a missing model.
- **Forgetting the worker.** A `ShouldQueue` listener does nothing until `php artisan queue:work` is actually running. In local dev without a worker, your queued side effects silently never happen. This confuses people constantly.
- **Heavy logic in closures.** Closure listeners can't be cached the same way, aren't easily testable, and clutter your service provider. Promote anything non-trivial to a class.
- **Depending on listener order.** If listener B needs listener A to have finished, you don't want two listeners — you want one listener that does both steps, or a queued chain.

## FAQ

### Do I still need EventServiceProvider in Laravel 11 or 12?

No. Listeners are auto-discovered from `app/Listeners` based on the event they type-hint. A new Laravel 11+ app doesn't even generate an `EventServiceProvider`. You can still register listeners manually via `Event::listen` in `AppServiceProvider::boot()` if you want explicit control, but it's optional.

### How do I run a listener on a queue?

Implement `Illuminate\Contracts\Queue\ShouldQueue` on the listener class. Laravel will push it to your default queue connection instead of running it inline. Make sure a queue worker is running, and configure `$tries`, `$backoff`, and a `failed()` method for resilience.

### What's the difference between events and jobs?

A job is a single unit of queued work you dispatch directly. An event is an announcement that can trigger zero, one, or many listeners, some of which may themselves be queued. Use a job when you know exactly what work to do; use an event when you want to broadcast that something happened and let interested parties react. For coordinating lots of queued work together, look at [job batching](/blog/laravel-job-batching).

### Can one event have multiple listeners in Laravel 11+?

Yes, and that's the main reason to use events at all. Create several listener classes that each type-hint the same event, and auto-discovery wires them all up. Each can independently choose to queue or run synchronously.

## Wrapping up

Laravel events and listeners are a decoupling tool, not a default. Use them when one action legitimately fans out into several independent reactions, especially ones you want to push off the request with `ShouldQueue`. Skip them when there's a single, always-happens reaction — a plain method call is clearer and easier to trace.

Concretely: start with `make:event` and `make:listener`, let auto-discovery do the wiring, add `ShouldQueue` to anything slow, remember to run a queue worker, and run `event:cache` in production. And every time you dispatch an event, ask yourself whether the next developer will be able to find what it triggers. If the answer is no, that's a signal — maybe it's worth a comment, or maybe it shouldn't be an event at all.