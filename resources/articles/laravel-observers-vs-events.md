---
name: "Laravel Model Observers vs Events: What to Use When"
slug: laravel-observers-vs-events
short_description: "A practical guide to Laravel observers vs events: when to use each, why observers skip bulk updates, and how to avoid the classic gotchas."
language: en
published_at: 2026-09-30 09:00:00
is_published: true
tags: [laravel, eloquent, php, architecture]
---

Every Laravel project reaches the same fork in the road: something needs to happen *after* a record changes. Send a welcome email. Bust a cache. Push a row to a search index. The question of **laravel observers vs events** shows up the moment you have to decide where that "something" lives, and picking wrong is the kind of mistake that only bites you three months later, when a batch job silently stops sending emails and nobody knows why.

I've shipped both patterns across a handful of production apps, and the short version is this: observers are glued to a model's lifecycle, events are not. That single distinction explains almost every practical difference between them, including the ones that cause the most confusing bugs.

## The core difference in one sentence

An **observer** listens to Eloquent lifecycle hooks for one specific model. An **event** is a message you fire yourself, and a **listener** reacts to it, with zero knowledge of any model.

Put differently:

- Observers answer *"what happens when this model is created, updated, or deleted?"*
- Events answer *"what happens when this thing happened in my domain?"*

The overlap is real. You can absolutely dispatch an event from inside a model's `created` hook, and plenty of codebases do. But the two tools optimize for different things, and treating them as interchangeable is where teams get burned.

## How model observers actually work

You generate one with Artisan:

```bash
php artisan make:observer UserObserver --model=User
```

That gives you a class with method stubs matching Eloquent events. Each method receives the model instance:

```php
namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created(User $user): void
    {
        // Runs after the INSERT commits
        Cache::forget('users.count');
    }

    public function updated(User $user): void
    {
        // Only when an actual UPDATE happened on a model instance
        if ($user->wasChanged('email')) {
            $user->update(['email_verified_at' => null]);
        }
    }

    public function deleting(User $user): void
    {
        // Runs BEFORE the delete, useful for cleaning up related records
        $user->tokens()->delete();
    }
}
```

The available hooks track the full Eloquent lifecycle: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `trashed`, `forceDeleting`, `forceDeleted`, `restoring`, `restored`, and `replicating`. The `-ing` variants fire before the query, the `-ed` variants fire after.

In Laravel 11 and 12 you register the observer with an attribute right on the model, which I find far cleaner than the old service-provider dance:

```php
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([UserObserver::class])]
class User extends Model
{
    // ...
}
```

If you prefer explicit registration (or you're on an older version), `User::observe(UserObserver::class)` in a service provider's `boot` method does the same job.

## The gotcha that trips up everyone

Here's the thing nobody tells you until you've already lost an afternoon to it. **Observers only fire for events dispatched by Eloquent model instances.** They are completely skipped by:

- Bulk updates through the query builder: `User::query()->where('active', false)->update(['status' => 'archived'])`
- Mass deletes: `User::where(...)->delete()`
- Anything you do with `saveQuietly()`, `updateQuietly()`, or `deleteQuietly()`
- Raw DB facade queries

The reason is mechanical, not arbitrary. A query-builder `update()` compiles a single SQL statement and sends it to the database. There is no model instance being hydrated, no `updated` event to fire, so the observer has nothing to hook into. This is a performance feature: you don't want 50,000 model objects instantiated just to run one `UPDATE`. But it means any side effect you *need* to happen on every change cannot live only in an observer.

I got caught by exactly this once: an audit-log observer that worked flawlessly in the admin panel, then produced zero records when a nightly command archived stale accounts with a bulk update. The fix was to move the audit logic into an explicit event dispatched from both code paths.

## How events and listeners work

Events are generated separately and carry whatever payload you want:

```bash
php artisan make:event OrderShipped
php artisan make:listener SendShipmentNotification --event=OrderShipped
```

The event is a plain data holder:

```php
namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderShipped
{
    use Dispatchable;

    public function __construct(public Order $order)
    {
    }
}
```

And the listener does the work. Implement `ShouldQueue` and it runs on your queue instead of blocking the request:

```php
namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendShipmentNotification implements ShouldQueue
{
    public function handle(OrderShipped $event): void
    {
        $event->order->customer->notify(
            new \App\Notifications\ShipmentDispatched($event->order)
        );
    }
}
```

You fire it wherever the domain action completes:

```php
OrderShipped::dispatch($order);
```

In Laravel 11+, listeners are auto-discovered by their type-hinted event, so there's usually no manual wiring in `EventServiceProvider` anymore.

The win here is decoupling. `OrderShipped` doesn't care whether the order was shipped via the API, a queued job, an admin action, or a webhook. One event, many listeners, each independent. That's genuinely hard to replicate cleanly with observers because observers are pinned to a single model's persistence.

## Side-by-side comparison

| Aspect | Model Observers | Events + Listeners |
|---|---|---|
| Generated with | `make:observer` | `make:event` / `make:listener` |
| Bound to | One Eloquent model's lifecycle | Any domain action you define |
| Triggered by | `created`, `updated`, `deleted`, `saving`, etc. | Your explicit `dispatch()` call |
| Fires on bulk/query-builder updates | **No**, skipped entirely | Yes, if you dispatch it there |
| Fires with `saveQuietly()` | No | Yes, if you dispatch it |
| Multiple independent handlers | Awkward (one observer per model) | Natural (many listeners per event) |
| Queueable | Not directly; queue work inside | Yes, via `ShouldQueue` |
| Best for | Model-persistence side effects | Cross-cutting domain logic |
| Coupling | Tight to the model | Loose, decoupled |

## So which one do I reach for?

My rule of thumb, after making this call more times than I can count:

**Use an observer** when the side effect is genuinely tied to a model saving to the database and you're confident all writes go through model instances. Slug generation on `saving`, cascading cleanup on `deleting`, cache invalidation on `saved`: these belong in observers. They keep the logic close to the data and out of your controllers. If you're already leaning on Eloquent-heavy patterns, this pairs well with a [repository pattern](/blog/repository-pattern-laravel) sitting on top.

**Use an event** when the trigger is a business moment rather than a row change, when you need several unrelated things to happen, or when the same outcome must fire from multiple entry points (API, console, admin). Anything you want on a queue is also a strong signal to reach for events. For a deeper walkthrough of the mechanics, the guide on [Laravel events and listeners](/blog/laravel-events-listeners) covers the wiring in detail.

There's a middle path I use often: keep a thin observer that does nothing but *dispatch a domain event*. The observer captures the "model changed" fact, the event carries it into decoupled handlers. Just remember the bulk-update caveat: if writes can bypass Eloquent, that observer won't fire, and you'll need to dispatch the event from those code paths too.

One more practical note: putting slow work directly in an observer's `saved` method will slow down every save on the request. If the side effect is expensive, dispatch a queued job or a queued listener from the observer rather than doing the work inline. This ties into the same discipline as fixing [N+1 query problems](/blog/eloquent-n1-query-problem) — cheap hooks, expensive work moved off the hot path.

## FAQ

### Do Laravel observers fire on mass updates?

No. Query-builder operations like `Model::query()->update([...])` and `Model::where(...)->delete()` run as single SQL statements without hydrating model instances, so no Eloquent events are dispatched and observers are skipped. If you rely on an observer for a critical side effect, make sure every write path goes through a loaded model, or move that logic into an explicitly dispatched event.

### Why isn't my observer method being called?

The three usual suspects: you used `saveQuietly()` / `updateQuietly()` (which mute events on purpose), you ran a bulk query-builder update instead of saving a model instance, or the observer isn't registered. Check that the `#[ObservedBy]` attribute is on the model or that `Model::observe()` runs in a service provider's `boot` method.

### Can observers be queued like listeners?

Observer methods themselves run synchronously in the same process as the save. There's no `ShouldQueue` on an observer. To get async behavior, dispatch a queued job or a queued event listener from inside the observer method, so the save returns fast and the heavy work runs on the queue.

### Can I use both in the same project?

Yes, and most mature apps do. A common and clean split is observers for model-persistence bookkeeping (slugs, cache busting, cascade cleanup) and events for domain notifications and cross-cutting concerns. Just be deliberate about which is which, so future you can predict where a given side effect lives.

## Conclusion

The **laravel observers vs events** decision isn't about which pattern is "better." It's about matching the tool to the trigger. A side effect that's genuinely a function of a model hitting the database belongs in an observer. A business moment that fans out to independent handlers, or runs off the request cycle, belongs in an event.

So: default to observers for simple, model-scoped persistence side effects, and reach for events the second you spot a bulk-update path or more than one thing that needs to react. Whatever you pick, write the assumption down somewhere — "this only works because all writes go through Eloquent." That single line is exactly what a future bulk-update refactor will quietly break.