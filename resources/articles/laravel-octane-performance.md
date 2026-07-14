---
name: "Laravel Octane Performance: Boosting Throughput with Swoole and RoadRunner"
slug: laravel-octane-performance
short_description: "How Laravel Octane performance works with Swoole, RoadRunner and FrankenPHP, plus the state-leak pitfalls nobody warns you about."
language: en
published_at: 2026-12-30 09:00:00
is_published: true
tags: [laravel, octane, performance, swoole, roadrunner]
---

If you have ever profiled a Laravel request and watched the framework spend more time booting itself than running your actual code, **Laravel Octane performance** is the thing that fixes exactly that. Octane keeps your application resident in memory between requests, so the expensive bootstrap phase happens once at worker startup instead of on every single hit. The payoff can be large. The catch is that "resident in memory" changes some assumptions you have probably relied on for years without noticing.

I have run Octane in production for an API that was getting hammered by mobile clients, and the throughput jump was real. So were the two bugs it introduced on day one. This article covers both sides honestly: how to get the speed, and how to not shoot yourself in the foot.

## What Laravel Octane actually does

A normal PHP-FPM request goes through a full lifecycle every time. PHP starts, autoloads classes, reads config, registers service providers, builds the container, handles the request, then throws all of it away. The next request repeats the whole dance from zero.

Octane replaces that model with a long-running worker. It boots the framework once, holds the booted application in memory, and then feeds incoming requests into that already-warm instance. No re-bootstrapping. No re-parsing config on every hit.

You choose an application server underneath it:

- **Swoole**: a PHP extension that gives you an event loop, coroutines, and concurrent tasks. The most feature-rich option.
- **RoadRunner**: a Go-based application server that talks to PHP workers over a binary protocol. No PHP extension to compile.
- **FrankenPHP**: a newer server built on Caddy, with a modern HTTP/2 and HTTP/3 story and a single-binary deployment.

They all deliver the same core win: skip the boot, keep the app warm. Which one you pick mostly comes down to your ops comfort and whether you want Swoole's concurrency features.

## Installing Octane

Getting started is short. Pull in the package and run the installer, which asks which server you want and wires up the config.

```bash
composer require laravel/octane
php artisan octane:install
```

If you go with RoadRunner, the installer can download the `rr` binary for you. For Swoole you need the extension installed (`pecl install swoole` or a distro package) before Octane will let you select it.

Then start the server:

```bash
php artisan octane:start --server=roadrunner --workers=4 --max-requests=500
```

A few flags worth understanding right away:

- `--workers` controls how many worker processes handle requests in parallel. A common starting point is one per CPU core, then tune from there.
- `--max-requests` tells each worker to restart itself after handling that many requests. This is your safety net against slow memory growth, and I will come back to why it matters.

Point your load balancer or local dev at the Octane port and you are serving warm requests.

## The gotcha that will bite you: state leakage

Here is the part the "10x faster in one command" tutorials skip.

Because the application stays alive across requests, anything you leave lying around in memory is still there when the next request arrives. In traditional PHP that never mattered, since the process died at the end of the request and scrubbed everything clean. Octane removes that automatic cleanup. So state you assumed was per-request quietly becomes shared, and it leaks from one user's request into another's.

This shows up in a handful of predictable places.

### Static properties

A static property holds its value for the entire life of the worker. Watch this:

```php
class ReportBuilder
{
    // BAD: this array survives across requests in the same worker
    protected static array $rows = [];

    public function addRow(array $row): void
    {
        static::$rows[] = $row;
    }
}
```

Under FPM this class starts fresh every request. Under Octane, request #2 sees the rows request #1 added. You get reports with another user's data glued to the bottom. Keep mutable request data on instances, not on `static` properties.

### Singletons that captured request data

Container singletons are resolved once and reused. If a singleton grabs the current request, the authenticated user, or any per-request value at construction time, it freezes that value and hands the stale copy to every later request.

```php
// BAD: the request is captured once and never updates
$this->app->singleton(TenantContext::class, function ($app) {
    return new TenantContext($app['request']->header('X-Tenant'));
});
```

The second tenant to hit that worker gets the first tenant's context. If you truly need per-request data, bind it as a scoped or plain binding, or resolve the request lazily inside the method that needs it rather than in the constructor. Octane also fires a `RequestReceived` event and can flush state between requests, but the cleaner fix is to not capture request state in long-lived objects at all. If singletons and binding lifetimes feel fuzzy, it is worth reviewing how the [service container](/blog/laravel-service-container) resolves and caches instances, because Octane makes those lifetimes suddenly visible.

### AppServiceProvider bindings that do work once

`register()` runs once per worker, not once per request. Anything you set up there that depends on the incoming request is baked in at boot. Move request-aware setup into middleware or resolve it lazily.

### Accumulating memory

Even without an obvious bug, long-lived processes tend to grow. A cache array that only ever gets appended to, a static log buffer, an event listener you register on every request without removing it: all of these creep upward until the worker is fat. That is what `--max-requests` guards against: recycle the worker periodically so any slow leak gets reset. It is a mitigation, not a cure. If you see memory climbing fast, find the leak; do not just lower the recycle threshold and hope.

Octane's config file lets you list objects to flush between requests, and packages like Livewire register their own cleanup. But your own code is your responsibility.

## Concurrent tasks with Octane

Octane ships a genuinely nice feature for fanning out work: `Octane::concurrently`. It runs multiple closures in parallel and collects the results. One prerequisite people miss: this needs Swoole. It runs on Swoole's task workers, so RoadRunner and FrankenPHP do not support it.

```php
use Laravel\Octane\Facades\Octane;

[$users, $orders, $stats] = Octane::concurrently([
    fn () => User::query()->count(),
    fn () => Order::query()->whereDate('created_at', today())->count(),
    fn () => Cache::get('daily_stats'),
]);
```

Instead of three sequential queries, they run side by side and you wait for the slowest one. Handy for dashboards that aggregate several independent sources. Two honest caveats. The closures run in separate task workers, in a different process from the request, so they do not share its in-memory state. And dispatching a task has real overhead. For three cheap `COUNT` queries it may not beat running them inline. Measure before you reach for it. You control the pool size with `--task-workers`, and Swoole caps a single call at 1024 tasks. If your bottleneck is repeated identical queries rather than independent ones, plain [query caching](/blog/laravel-cache-queries) is usually the better first move.

## Deploying: reload, do not just restart

When you deploy new code, the running workers are still holding the *old* code in memory. They will not magically pick up your changes. You reload them:

```bash
php artisan octane:reload
```

This gracefully cycles the workers so new requests hit the new code without dropping in-flight requests. Wire it into your deploy script right after you pull and install dependencies. Forgetting `octane:reload` is the single most common "why is my fix not live?" moment for teams new to Octane, and it is maddening precisely because the code on disk looks correct.

If you containerize your app, this fits naturally into the release step. See the notes on a production [Docker setup for Laravel](/blog/dockerize-laravel-production) for where a reload or a rolling container replacement belongs in the pipeline.

## Pitfalls checklist

Keep this list next to you when you migrate an existing app:

- **State leakage is the big one.** Static properties, request-capturing singletons, and boot-time bindings all leak data between requests. Audit them first.
- **Do not store per-request data on long-lived objects.** Keep it on the request or resolve it lazily.
- **Watch memory over time**, not just at startup. Set a sane `--max-requests` and investigate real growth.
- **Reload on every deploy.** Old code stays warm until you cycle workers.
- **Database connections can go stale** on idle workers; make sure your DB config reconnects, or lean on a health check.
- **Test under Octane, not just under FPM.** A state-leak bug is invisible with one request and obvious with a hundred concurrent ones.

## Is Octane worth it for you?

Straight answer: it depends on where your time actually goes.

Octane removes bootstrap cost. If your requests spend most of their time waiting on a slow database, an external API, or unindexed queries, then shaving a few milliseconds of framework boot will not move your p95 much. You would get more from fixing the query or adding a cache layer — [Redis caching patterns](/blog/redis-caching-patterns) will do more for that kind of workload than any application server swap.

Where Octane shines is high-throughput, CPU-and-boot-bound traffic: JSON APIs with lots of small fast requests, where the framework overhead is a meaningful slice of each response. There the per-request boot savings compound across thousands of hits per second, and you serve far more traffic on the same hardware.

My rule of thumb: profile first. If bootstrap and framework overhead are a top item in your request timeline, Octane is a strong lever. If they are noise next to a 300ms query, fix the query. And weigh the operational cost honestly — you are trading a stateless, forgiving execution model for a stateful, long-running one that demands more discipline from your code.

## FAQ

### Does Laravel Octane work with all my existing packages?

Mostly, but not blindly. Well-maintained packages are Octane-aware and clean up their own state. Older or niche packages that rely on static state or per-request singletons can leak. Test your full stack under Octane with concurrent load before trusting it in production.

### Which server should I choose: Swoole, RoadRunner, or FrankenPHP?

If you want concurrent tasks and coroutines, Swoole has the richest feature set but needs the PHP extension. RoadRunner avoids the extension and is simple to operate as a single Go binary. FrankenPHP is the modern option with strong HTTP/2 and HTTP/3 support and single-binary deploys. For raw request handling the performance is comparable; pick on ops fit.

### Can I run Octane in local development?

Yes, and it is a good idea so your dev environment matches production behavior, including state-leak bugs. Note that code changes require `octane:reload` (or `--watch` during development) to take effect, since old code stays in memory.

### Will Octane speed up a slow database query?

No. Octane cuts framework bootstrap time, not query time. A slow query is slow whether the framework is warm or cold. Fix the query, add indexes, or cache the result first; reach for Octane when framework overhead is genuinely your bottleneck.

## Conclusion

Laravel Octane performance gains are real and sometimes dramatic, but they are not a free `composer require`. The mental model shift is the whole game: your app now lives across requests instead of dying after each one. Get that, and the wins are yours — skip it, and you ship subtle cross-request data bugs.

Practical path: profile to confirm bootstrap is actually costing you, pick a server (RoadRunner if you want simple, Swoole if you want concurrency), audit your code for static state and request-capturing singletons, set `--max-requests`, and put `octane:reload` in your deploy script. Do that, and Octane earns its place. Bolt it on without the audit, and it will find every stateful shortcut you have ever taken.