---
name: "Config and Route Caching in Laravel Production"
slug: laravel-config-and-route-caching
short_description: "Why env() returns null after config caching, why route closures break route:cache, and the deploy order that keeps both from biting you in production."
language: en
published_at: 2027-06-25 09:00:00
is_published: true
tags: [laravel, php, devops, performance]
---

The first time it happened to me, the site had been green for weeks. Then a routine deploy, and suddenly every outbound email was going nowhere and Stripe was rejecting keys. Nothing in the code had changed around those features. What changed was that someone finally ran `php artisan config:cache` on the server, and half a dozen `env()` calls scattered through the app quietly started returning `null`. That failure mode is the price of admission for Laravel's production caches, and once you understand it, you never get bitten by it again.

Laravel gives you a set of "compile it once" commands for production: `config:cache`, `route:cache`, `view:cache`, and `event:cache`. They exist because parsing config files, resolving routes, and compiling Blade templates on every single request is wasted work when none of it changes between requests. Cache the results once at deploy time and the framework skips that work forever after. The speedup is real. But two of these commands change how your app behaves, not just how fast it runs, and that's the half nobody warns you about until it's already 2am and the payments queue is on fire.

## What each command actually compiles

They look interchangeable. They aren't — each one writes a different artifact to disk, and the artifact is what tells you why two of them can bite.

- **`config:cache`** reads every file in `config/`, merges them into one big array, and serializes that array to `bootstrap/cache/config.php`. On boot, Laravel loads that single file instead of `require`-ing 30 separate config files and running their logic.
- **`route:cache`** resolves your entire route table and writes the compiled result to `bootstrap/cache/routes-v7.php`. The router loads that instead of re-executing `routes/web.php` and `routes/api.php` on every request.
- **`view:cache`** precompiles all your Blade templates to plain PHP under `storage/framework/views/` so the first request that hits a view doesn't pay the compilation cost. This one is harmless — Blade compiles on demand anyway, this just does it ahead of time.
- **`event:cache`** compiles a manifest of your discovered event listeners so Laravel doesn't scan your listener classes with reflection on every boot. Only relevant if you use event auto-discovery.

There's also `php artisan optimize`, which is a convenience wrapper that runs `config:cache`, `route:cache`, `view:cache`, and `event:cache` together (plus a couple of framework internals). And `optimize:clear` reverses all of them at once.

The `view:cache` and `event:cache` commands are pure performance. They can't change your app's behavior. The two that can — and the two that generate the "works on my machine" support tickets — are `config:cache` and `route:cache`.

## The gotcha that costs everyone a production incident

Here is the rule, and I'd tattoo it on a junior's monitor if I could: once you run `config:cache`, the `env()` helper returns `null` everywhere except inside your config files.

To see why, look at what `config:cache` does. It builds that merged config array by evaluating every config file, and your config files call `env()` at that moment to read values from the environment. The results get baked into `config.php`. After that, Laravel doesn't reload the `.env` file at all on subsequent boots — the whole point is to skip that work. So any `env()` call sitting outside `config/` runs at request time, when the environment was never loaded, and gets nothing back.

This works fine in local development because you almost never run `config:cache` locally. So a call like this passes every test and every code review:

```php
// app/Services/PaymentGateway.php — looks fine, ships fine, breaks in prod
class PaymentGateway
{
    public function __construct()
    {
        $this->secretKey = env('STRIPE_SECRET');
    }
}
```

Locally, no config cache, `env('STRIPE_SECRET')` reads the `.env` file, everything works. You deploy, the deploy script runs `config:cache` for performance, and now `$this->secretKey` is `null` on every request. Stripe throws an authentication error. Nothing in your recent diff touched payments. You lose an hour before someone remembers the cache.

The fix is a discipline, not a code change: **every `env()` call lives in a config file, and every other piece of the app reads through `config()`.** Add the key to a config file:

```php
// config/services.php
return [
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key'    => env('STRIPE_KEY'),
    ],
];
```

Then read it through the config repository, which is populated from the cached array and works identically whether or not the cache exists:

```php
// app/Services/PaymentGateway.php — cache-safe
public function __construct()
{
    $this->secretKey = config('services.stripe.secret');
}
```

If you want to enforce this instead of trusting everyone to remember, grep your app before shipping:

```bash
# Any env() call outside the config directory is a landmine
grep -rn "env(" app/ routes/ | grep -v "config/"
```

I run something close to that in CI. Zero results is the only passing state. The framework itself follows this rule religiously — that's why `config('app.debug')` behaves correctly with a cached config while a stray `env('APP_DEBUG')` in a controller doesn't.

One more subtlety people miss: after you've cached config, editing `.env` on the server does nothing until you re-cache. The `.env` file is no longer being read. If you change a value in production, you must run `config:cache` again (or `config:clear`) for it to take effect. I've watched people edit `.env`, restart PHP-FPM, and stare at the old value in confusion for ten minutes.

## Why closures in routes break route:cache

The second command with a behavioral catch is `route:cache`. The rule here: **you cannot cache routes that use closures.** Try it and you get a hard failure at cache time:

```
LogicException: Unable to prepare route [/] for serialization. Uses Closure.
```

The reason is mechanical. `route:cache` serializes your compiled route table to a PHP file so it can be loaded without re-running your route definitions. A closure is a chunk of live PHP code with a bound scope — it cannot be serialized to a file and read back. Controller references can, because they're just class-and-method strings that Laravel resolves later. So a closure route is the one thing that stands between you and a cacheable route table.

This trips people up because closure routes are the most natural thing to write for something quick:

```php
// routes/web.php — this route makes route:cache fail
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
```

The fix is to move the closure body into a controller and reference it by class:

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function __invoke()
    {
        return response()->json(['status' => 'ok']);
    }
}
```

```php
// routes/web.php — now cacheable
Route::get('/health', HealthController::class);
```

Single-action controllers with `__invoke` are perfect for this — they keep tiny endpoints tiny without leaving a closure in the route file. The gotcha is that `route:cache` fails loudly, which is actually the good outcome: you find out at deploy time, not at request time. The dangerous scripts are the ones that swallow the error and deploy anyway with a stale or missing route cache.

There's a related trap in `RouteServiceProvider` and anywhere you define routes: route-model-binding resolvers and pattern constraints defined with closures are fine because they run at boot, not at request-serialization time. It's specifically the route's *action* closure — the `function () { ... }` handling the request — that can't be cached.

## The deploy order that keeps you sane

Caching is a deploy-time step, and the order matters. The one rule that catches everyone: **cache after you've pulled the new code, never before.** A config cache built from the old code, or a route cache pointing at routes that no longer exist, is worse than no cache at all — it serves stale behavior with total confidence.

Here's the sequence I use, adapted for whatever deploy tool is in front of it:

```bash
# 1. Get the new code onto the server first
git pull origin main
composer install --no-dev --optimize-autoloader

# 2. Clear everything old — start from a clean slate
php artisan optimize:clear

# 3. Run migrations before the cache, while the app boots uncached
php artisan migrate --force

# 4. Rebuild all caches from the code that's actually deployed
php artisan optimize
```

`php artisan optimize` in step 4 does `config:cache`, `route:cache`, `view:cache`, and `event:cache` in one shot, which is what you want on a real production box. If you're running any local process, or if a step fails and you need to debug, `php artisan optimize:clear` is the escape hatch — it drops every cache and puts you back in the uncached, reads-`.env`-directly behavior that matches local dev.

A couple of things I've learned to put in the runbook:

| Command | When to run it | What breaks if you skip it |
| --- | --- | --- |
| `config:clear` before editing `.env` on server | Changing a prod secret | New value silently ignored |
| `optimize:clear` when debugging weird prod behavior | Investigating an incident | You debug against stale compiled state |
| `route:cache` in the deploy | Every production deploy | Router re-parses routes every request |
| Never `config:cache` in local dev | Ongoing | `.env` edits stop taking effect and confuse you |

The last row is a real preference of mine. Locally I keep config uncached so I can tweak `.env` and see the change immediately. Caching config on a dev machine buys you nothing and costs you an afternoon the first time you forget it's on.

## The "works locally, broken in prod" bug — caused and cured

This is worth naming directly because config caching sits on both sides of it. Caching is the single most common cause of "it works on my machine, it's broken in prod" in Laravel — and, done right, it's also the cure.

It's a *cause* because the config cache is the biggest behavioral difference between a typical dev box (uncached) and a typical production box (cached). The `env()`-returns-null bug is invisible locally and guaranteed in prod. So is a `.env` value that's set on your laptop but never added to the server's environment — uncached, Laravel might fall back gracefully; cached, the missing value is baked in as `null` at cache time and there's no fallback.

It's a *cure* because the same mechanism removes a whole class of environmental drift. When config is compiled to one file, the app boots identically every time regardless of what's happening with the `.env` reader, filesystem timing, or config-file evaluation order. Two servers running the same compiled `config.php` behave the same, full stop. The trick to making it a cure and not a curse is to run `config:cache` somewhere in your pipeline *before* production — in CI or on staging — so the `env()` landmines detonate in front of you instead of in front of users.

That's the actual takeaway: don't just cache in production. Cache in a place that can fail safely first.

## How caching speeds up the boot

The performance win is concrete, and it's all about work the framework does on every request that it doesn't need to.

Without a config cache, Laravel `require`s every file in `config/` on each boot, runs each one's PHP (including all those `env()` reads), and merges the arrays. With the cache, it loads one already-merged, already-serialized array. On an app with 25–30 config files, that's the difference between dozens of file reads plus environment parsing and a single `include`.

Route caching is similar. Without it, the router executes your route files on every request — every `Route::get`, every group, every middleware assignment — and builds the route collection from scratch. With the cache, it deserializes a pre-built collection. Apps with hundreds of routes feel this the most; the router setup can be a meaningful slice of the request lifecycle before it's cached.

This isn't the kind of optimization you squint at a profiler to justify. On a route-heavy app under real traffic, config and route caching together take a visible bite out of per-request boot overhead, and they cost nothing at runtime — the work just moves to deploy time and happens once. Pay once, benefit on every request. The only tax is the discipline around `env()` and closures, and you've now seen both.

## FAQ

**Why does `env()` return null after `config:cache`?**
Because `config:cache` bakes the results of your config files (which is where `env()` is meant to be called) into a single compiled file, and Laravel stops reading `.env` on boot after that. Any `env()` call outside a config file runs at request time when the environment isn't loaded, so it gets nothing. Move the value into a config file and read it with `config()`.

**Can I use closures in routes if I never run `route:cache`?**
Yes, closure routes work perfectly without route caching — that's why they work in local dev. But you're leaving a real performance win on the table in production, and `route:cache` will fail the moment someone adds it to the deploy. Prefer invokable controllers so caching is always available.

**Do I need to re-run `config:cache` after editing `.env` on the server?**
Yes. Once config is cached, the `.env` file isn't read anymore. Run `php artisan config:cache` again (or `config:clear` to go back to reading `.env` directly) for the new value to take effect. Restarting PHP-FPM alone won't do it.

**What's the difference between `optimize` and `optimize:clear`?**
`php artisan optimize` runs all the caching commands together (`config:cache`, `route:cache`, `view:cache`, `event:cache`) to prepare for production. `php artisan optimize:clear` reverses them, dropping every cache and returning the app to its uncached behavior — the one that matches local development.

The whole game here is turning a class of production surprises into a checklist. Put every `env()` behind `config()`, keep closures out of your route actions, cache after you deploy, and run `config:cache` somewhere safe before prod so the failures land where you can see them. Do that and these commands become what they were always meant to be: free speed.
