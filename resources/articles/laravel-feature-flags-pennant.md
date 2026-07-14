---
name: "Laravel Feature Flags with Pennant: A Practical Guide"
slug: laravel-feature-flags-pennant
short_description: "Ship features safely with Laravel feature flags using Pennant: define flags, gradual rollout, A/B tests, kill switches, and Blade."
language: en
published_at: 2026-10-09 09:00:00
is_published: true
tags: [laravel, pennant, feature-flags, php]
---

Laravel feature flags with Pennant let you merge unfinished code into `main` and decide later who actually sees it. That single shift changed how our team ships. No more long-lived branches rotting for three weeks, no more "big bang" Friday deploys. You wrap the new thing in a flag, release it dark, then flip it on for 5% of users and watch the logs.

Pennant is Laravel's first-party package for exactly this. It's small, it plugs into the framework's auth and Blade layers, and it doesn't drag in a third-party dashboard you have to babysit. Below is how we actually use it in production, including the parts that bit us.

## What feature flags are actually for

A feature flag is a runtime switch. The code path exists in the deployed app, but whether it runs depends on a value you can change without redeploying. Three uses cover most real cases:

- **Gradual rollout** — turn a feature on for an increasing slice of traffic (1%, then 10%, then everyone) so a bug hits few people, not all of them.
- **A/B testing** — send half your users down path A and half down path B, then compare conversion.
- **Kill switch** — a payment provider starts timing out at 2am; you disable the integration in seconds instead of shipping a hotfix.

The mental model that matters: deployment and release become separate events. You deploy code whenever CI is green. You release it to users whenever the flag says so.

## Installing Pennant

Pennant needs Laravel 10 or newer and works cleanly on 11 and 12. Pull it in with Composer:

```bash
composer require laravel/pennant
```

Then publish the config and run the migration that creates the `features` table:

```bash
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan migrate
```

The migration gives you a `features` table. That's the `database` store, which persists each resolved flag value per scope. If you don't want a database round trip, Pennant also ships an `array` store that lives only for the current request. You pick the default in `config/pennant.php`:

```php
'default' => env('PENNANT_STORE', 'database'),

'stores' => [
    'array' => [
        'driver' => 'array',
    ],
    'database' => [
        'driver' => 'database',
        'connection' => null,
    ],
],
```

I reach for `array` in tests and for feature checks that are cheap to recompute. `database` is the right call when resolving a flag is expensive (say, it hits an external API) and you want the answer remembered per user.

## Defining your first flag

Flags are defined, usually in a service provider's `boot` method. `AppServiceProvider` is fine to start; a dedicated `FeatureServiceProvider` keeps things tidy once you have more than a handful.

```php
use Laravel\Pennant\Feature;
use App\Models\User;

public function boot(): void
{
    Feature::define('billing-v2', fn (User $user) => match (true) {
        $user->isInternalTester() => true,
        default => false,
    });
}
```

The closure receives the current *scope*. By default the scope is the authenticated user, so `$user` is whoever is logged in. Pennant runs your closure once per scope, stores the result, and reuses it. That "resolve once" behavior is the whole reason a user doesn't flip between the old and new UI on every page load.

Checking the flag anywhere in the app:

```php
if (Feature::active('billing-v2')) {
    return $this->newCheckout($request);
}

return $this->legacyCheckout($request);
```

`Feature::active()` resolves against the default scope (the logged-in user). When you need a specific user, be explicit:

```php
if (Feature::for($user)->active('billing-v2')) {
    // ...
}
```

There's also `Feature::inactive()`, and `Feature::when()` which takes two closures for the active and inactive branches if you like that style.

## Gradual rollout with Lottery

Here's where Pennant earns its keep. Laravel's `Lottery` helper makes percentage rollouts a one-liner:

```php
use Illuminate\Support\Lottery;

Feature::define('new-search', fn () => Lottery::odds(1, 10));
```

That resolves to `true` for roughly one in ten users. Because Pennant stores the result per user, a given person stays in their group. Someone who lands in the 10% keeps seeing new search on their next visit rather than flickering in and out.

Bumping the rollout to 50% is a code change to the odds, deployed like anything else. If you want to change rollout without a deploy, resolve the percentage from config or a database value inside the closure and update that instead.

## Rich values, not just on/off

Flags don't have to be boolean. Pennant handles any serializable value, which is how A/B tests get clean:

```php
use Illuminate\Support\Arr;

Feature::define('checkout-button', fn (User $user) => Arr::random(['green', 'blue']));
```

A `Lottery` is the wrong tool here. `Lottery::odds(1, 2)` returns an object, and an object is always truthy, so a `? :` on it would hand every user `'green'`. For a value you branch on, return the value directly. `Arr::random` picks one string per user, and Pennant stores it.

Read the value with `Feature::value()`:

```php
$color = Feature::value('checkout-button'); // 'green' or 'blue'
```

We used this on a "Buy now" button. Two colors, split down the middle, the value logged alongside each order. No conditional spaghetti, just one string per user that stays put. For richer experiments, return an array or an enum-backed value; anything JSON-serializable survives the round trip.

## Class-based features

Once a flag has real logic (multiple conditions, injected services, its own tests), inline closures get cramped. Pennant supports class-based features. Generate one:

```bash
php artisan pennant:feature NewApi
```

That scaffolds a class with a `resolve` method:

```php
namespace App\Features;

use App\Models\User;
use Illuminate\Support\Lottery;

class NewApi
{
    public function resolve(User $user): mixed
    {
        return match (true) {
            $user->isInternalTester() => true,
            $user->created_at->isAfter(now()->subMonths(3)) => Lottery::odds(1, 4),
            default => false,
        };
    }
}
```

Check it by passing the class name where you'd normally pass a string:

```php
if (Feature::active(NewApi::class)) {
    // ...
}
```

Because it's a plain class, you can constructor-inject dependencies and write a focused unit test for the resolution logic. That test is worth writing. Flag logic is exactly the kind of "match on five conditions" code that quietly breaks. If you lean on service resolution here, the [Laravel service container](/blog/laravel-service-container) guide covers how injection works under the hood.

## Flags in Blade

For view-level toggles, the `@feature` directive is cleaner than an `if`:

```blade
@feature('billing-v2')
    <x-checkout.v2 />
@else
    <x-checkout.legacy />
@endfeature
```

It reads well and keeps the flag name discoverable when someone greps the templates. You still want the server-side check on the actual endpoint, though. A hidden button is not access control. If a route does the sensitive work, guard the route.

## Changing and clearing values

Two operations you'll need in day-to-day work.

To force a value (a support agent enabling a beta for one customer, or a seeder setting up test data):

```php
Feature::for($user)->activate('billing-v2');
Feature::for($user)->deactivate('billing-v2');
```

To wipe stored results so they get recomputed, use `Feature::purge`. This matters when you change a flag's definition: old stored values don't update themselves.

```php
// Purge a single feature for everyone
Feature::purge('billing-v2');

// Purge everything
Feature::purge();
```

There's also an Artisan command for the same job in deploy scripts:

```bash
php artisan pennant:purge billing-v2
```

## Pitfalls we hit

These cost us real time, so here they are plainly:

1. **Stale stored values after a definition change.** You tweak the closure, deploy, and nothing changes because Pennant is serving the old resolved value from the `features` table. Run `pennant:purge` for that flag as part of the deploy, or scope the purge to the feature you touched.

2. **Forgetting the scope.** `Feature::active('x')` with no logged-in user resolves against a null scope, not "the current user you meant." In queued jobs and commands there is no auth user, so pass the scope explicitly with `Feature::for($model)`.

3. **Leaving dead flags in the code.** A flag at 100% for two months is just noise plus a branch nobody deletes. Put a removal ticket in the same PR that introduces the flag. Flags are meant to be temporary; treat the permanent ones as configuration instead.

4. **Testing against the database store.** It makes tests slower and leaks state between them. Set `PENNANT_STORE=array` in `phpunit.xml` so each test starts clean.

5. **Non-user scopes need a scope resolver.** If you flag by team or tenant rather than user, define how Pennant identifies that model, or you'll get one shared value across the whole app.

## A minimal end-to-end flow

Putting it together, here's the shape of shipping a feature behind a flag:

- Define `reports-export` in your feature provider, defaulting to internal testers only.
- Merge and deploy. The code is live but dark.
- Widen the closure to `Lottery::odds(1, 20)` and deploy. 5% of users get it.
- Watch errors and metrics for a day.
- Bump to `Lottery::odds(1, 1)` (everyone), deploy.
- Once stable, delete the flag and the old code path, then `pennant:purge reports-export`.

If your rollout also fires domain logic (emails, analytics), wiring that through [Laravel events and listeners](/blog/laravel-events-listeners) keeps the flag check separate from the side effects it triggers.

## FAQ

### Is Laravel Pennant free and official?

Yes. Pennant is a first-party package maintained by the Laravel team, released as open source under the MIT license. You install it with `composer require laravel/pennant`, no paid plan involved.

### Database store or array store?

Use `array` for tests and for flags that are cheap to recompute every request. Use `database` when resolution is expensive or you need a user's assignment to stay stable across requests and servers. The database store persists to the `features` table; the array store lives for one request.

### How do I roll a feature out to a percentage of users?

Return a `Lottery` from the flag definition, for example `Lottery::odds(1, 10)` for 10%. Pennant stores each user's result, so people don't bounce between variants. Change the odds and redeploy to widen the rollout.

### How is a feature flag different from a config value?

A config value is read-only at runtime and usually applies to everyone equally. A feature flag resolves per scope (per user, team, or tenant) and can be activated or deactivated for specific scopes at runtime. Flags are meant to be temporary; config is permanent. For request performance work that pairs well with flags, see [Laravel cache and queries](/blog/laravel-cache-queries).

## Wrapping up

Pennant gives you the two things that make continuous delivery bearable in a Laravel app: a clean way to define who sees what, and a place to store those decisions per user. Start with one boolean flag around your next risky change. Define it in a provider, gate the controller and the Blade view, and roll it out with a `Lottery` at 5%. Then, and this is the part teams skip, actually delete it once it hits 100% and add the purge to your deploy. A flag you never remove stops being a tool and becomes debt.

The whole point is boring, safe releases. Deploy on Tuesday, release on Thursday, sleep on Friday.