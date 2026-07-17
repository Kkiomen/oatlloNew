---
title: "Patterns you already use in Laravel"
slug: patterns-you-already-use-in-laravel
seo_title: "Design Patterns in Laravel You Already Use Daily"
seo_description: "Facades, the container, events, middleware, notifications - the design patterns in Laravel you use every day, named and mapped to the Gang of Four."
---

Six chapters of patterns from small, invented examples. Here is the part nobody tells you up
front: if you write Laravel, you already use most of the classic design patterns in Laravel's
own source every single day. A framework is not a pile of magic - it is a careful application
of the exact patterns you just met. Naming them turns "Laravel magic" into "oh, that is just a
*strategy*", and that shift is worth more than it sounds: it changes how you read the source.

Five everyday Laravel features, mapped back to patterns from earlier chapters.

## Facades are the facade pattern

When you write `Cache::get('key')` or `Log::info('hi')`, you're using a **facade** - a
simple, static-looking front over a more complex subsystem. Behind `Cache` sits a full
cache manager with drivers, stores and configuration. The facade gives you one clean entry
point and hides all of it.

```php
use Illuminate\Support\Facades\Cache;

Cache::put('user:1', $user, 600); // one simple call, a whole subsystem behind it
```

That is exactly the intent from
[the facade lesson](/course/design-patterns/structural-patterns/facade): a friendly face
over a messy back end. One detail the classic pattern does not have: a Laravel facade still
resolves a real object from the container, so `Cache::shouldReceive(...)` can swap that object
for a mock in a test. A pure static call could never do that.

## The service container is a factory plus dependency injection

Every time Laravel *builds* an object for you - a controller, a mailer, your own service -
the **service container** decides which class to create and hands over its dependencies.
That's the [factory method](/course/design-patterns/creational-patterns/factory-method)
idea (something else creates the object) combined with dependency injection. The [next
lesson](/course/design-patterns/patterns-in-the-real-world/dependency-injection-and-the-container)
is entirely about this, because it's the pattern that powers the whole framework.

## Events and observers are the observer pattern

Laravel events are a textbook [observer](/course/design-patterns/behavioral-patterns/observer):
one thing happens, and many listeners react without the source knowing who they are.

```php
event(new OrderShipped($order)); // the sender doesn't know or care who listens
```

Model observers (`created`, `updated`, `deleted`) are the same pattern with a dedicated
class per model. The subject fires; the observers respond.

## Route middleware is chain of responsibility

A request passes through a pipeline of middleware - authenticate, throttle, verify CSRF -
and each one either handles it, passes it along, or stops it. That's
[chain of responsibility](/course/design-patterns/behavioral-patterns/chain-of-responsibility):
a chain of handlers, each deciding whether to act or delegate.

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified']); // request flows through the chain
```

## Collection pipelines and notification channels are strategy

A `Collection` lets you swap the operation at each step (`map`, `filter`, `reject`) - each
callback is an interchangeable [strategy](/course/design-patterns/behavioral-patterns/strategy).
Notifications go further: a notification's `via()` method returns which channels to use
(mail, database, Slack), and each channel is a different strategy for *delivering* the same
message.

```php
public function via(object $notifiable): array
{
    return ['mail', 'database']; // pick delivery strategies at runtime
}
```

## A common mistake

Recognizing these patterns is not a cue to re-implement them. Beginners sometimes build their
own facade or event layer on top of Laravel's. Don't. Use the framework's. The value here is
*understanding*, not rebuilding. Knowing the pattern helps you read the source, debug faster,
and extend the framework the way it already expects to be extended.

## FAQ

### Do I need to know these patterns to use Laravel?

No. You can be productive without naming any of them. But knowing the names turns "magic"
into understandable design and makes the docs and source code far easier to follow.

### Is a Laravel facade the same as the facade design pattern?

Close, but not identical. Both hide complexity behind a simple front. Laravel facades add a
static-looking syntax that resolves an object from the container underneath - the *intent*
is the same, the mechanics have a container twist.

### Where can I see these patterns in the framework?

Read the source of `Illuminate\Support\Facades\Cache`, the middleware `Pipeline`, and the
`Notification` classes. Once you know the pattern name, the code stops looking like magic.
