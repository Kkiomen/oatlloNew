---
name: "The Strangler Fig Pattern for Legacy Migration"
slug: strangler-fig-pattern-legacy-migration
short_description: "How to replace a legacy PHP app route-by-route behind a proxy without a big-bang rewrite, including data sync, seams, and when to stop."
language: en
published_at: 2027-03-29 09:00:00
is_published: true
tags: [architecture, php, laravel, devops, migration]
---

The old app was a 2011-era PHP monolith: no framework, SQL strings glued into page templates, sessions in files, one `index.php` that `require`d whatever the URL pointed at. Management wanted "a rewrite in Laravel." I've been on two big-bang rewrites before. Both slipped a year and one got cancelled with nothing shipped. So we didn't rewrite it. We put a router in front of it and replaced it one URL at a time, over about eight months, while it kept serving traffic every single day.

That approach has a name — Martin Fowler called it the strangler fig, after the vine that grows around a host tree and slowly takes over its structure until the original is gone. This is the honest version of how it works, where it hurts, and how you know it's actually done.

## Why big-bang rewrites lose

A rewrite freezes value. For however many months it takes, you ship nothing to users while the old system keeps accruing bug reports and small feature requests that the new one now also has to satisfy. The scope isn't "what the old app does" — it's "what the old app does, plus everything added while you were rewriting."

Worse, you only find out if the new system actually works at the end, on cutover day, all at once. Every assumption you got wrong about the legacy behavior surfaces in the same 48 hours. That's the day people quietly turn the old system back on.

Incremental migration flips both problems. You ship a replaced piece to production the week you build it, so you learn whether your new stack handles real traffic while the blast radius is one route, not the whole app. Value never freezes because the legacy system stays live for everything you haven't touched yet.

## The proxy is the whole trick

The pattern lives or dies on one component: a routing layer in front of both systems that decides, per request, who serves it. New code gets the requests it's ready for; everything else falls through to the legacy app. To the browser it's one site on one domain.

The cheapest place to put that decision is the web server. Here's the shape of it in nginx — new Laravel app on one upstream, legacy PHP on another, with an explicit allowlist of paths that have already migrated:

```nginx
upstream legacy_app {
    server 127.0.0.1:9000;   # old php-fpm pool
}

upstream new_app {
    server 127.0.0.1:9001;   # laravel php-fpm pool
}

server {
    listen 80;
    server_name shop.example.com;

    # Migrated routes: send to the new app. Order matters — the
    # most specific locations win, the catch-all is last.
    location = /checkout          { proxy_pass http://new_app; }
    location ^~ /account/         { proxy_pass http://new_app; }
    location ^~ /api/orders       { proxy_pass http://new_app; }

    # Everything not yet migrated falls through to the monolith.
    location / {
        proxy_pass http://legacy_app;
    }

    location ~ \.php$ {
        # legacy fastcgi wiring stays as-is
        fastcgi_pass legacy_app;
        include fastcgi_params;
    }
}
```

Migrating a route becomes a one-line diff: add a `location` block, reload nginx, done. Rolling back is deleting that line. No deploy of application code, no database change — you're moving a single URL between two running systems. That reversibility is the safety net that makes the whole thing feel less terrifying than it is.

For anything more dynamic than a path prefix — say you want to route 5% of `/checkout` traffic to the new app first — the decision moves up a layer into an application-level proxy. In Laravel that's a catch-all route that forwards unmatched requests to the legacy backend:

```php
// routes/web.php — real routes are defined above this line.
// Anything that falls through goes to the monolith.
Route::any('{path}', LegacyProxyController::class)
    ->where('path', '.*');
```

```php
class LegacyProxyController
{
    public function __invoke(Request $request, string $path)
    {
        $response = Http::withHeaders($this->forwardHeaders($request))
            ->send($request->method(), config('legacy.base_url').'/'.$path, [
                'query'   => $request->query(),
                'body'    => $request->getContent(),
                'cookies' => $request->cookies->all(),
            ]);

        return response($response->body(), $response->status())
            ->withHeaders($response->headers());
    }
}
```

The Laravel router evaluates real routes first, so the day you add `Route::post('/checkout', ...)` above the catch-all, that path stops proxying and starts running your code. You now control the seam in PHP, which means feature flags, percentage rollouts, and per-user routing become trivial. It's slower than an nginx `proxy_pass` because every legacy request round-trips through Laravel's kernel, so I only reach for it when I actually need per-request logic. Static prefixes stay in nginx.

## Choosing seams that don't bleed

The order you migrate in decides whether this is pleasant or a nightmare. A good seam is a piece of the system that talks to the rest through a narrow, well-understood boundary. A bad seam is one where the legacy code reaches into shared session state and half-written database rows that ten other pages also touch.

What I look for, roughly in this order:

- **Leaf pages first.** A read-only report, a public product page, an RSS feed — things that consume data but don't mutate the shared state everyone else depends on. Low risk, and they teach you the plumbing (auth, sessions, the shared DB) before you bet anything important on it.
- **Vertical slices, not layers.** Migrate `/checkout` end to end — its controller, its templates, its queries — rather than "all the database access" across the whole app. A half-migrated layer means every request now crosses the boundary twice and you own two versions of everything at once.
- **The thing that changes most.** Once you're comfortable, move the code with the highest churn. That's where new code pays off fastest and where the legacy code hurts most.

Leave the tangled core for last, when you understand it best and have the most new infrastructure to lean on. Migrating it first — the instinct, because it's the scariest — means doing the hardest work with the least experience and the least tooling.

## Keeping data in sync during the overlap

This is the part nobody warns you about. While both systems are live, they read and write the same data, and a strangler migration only works if they share one source of truth. Two databases that sync "eventually" will drift, and reconciling an order that half-exists in each is genuinely awful.

My default: **both systems point at the same database** for as long as the overlap lasts. The new Laravel app reads the legacy schema as-is. No renamed columns, no cleaned-up types, no foreign keys the old code didn't expect — you map an ugly table to a tidy model in your code, not in a migration:

```php
class Order extends Model
{
    protected $table = 'tbl_orders';       // legacy naming stays
    protected $primaryKey = 'order_id';
    public    $timestamps = false;         // legacy has no created_at/updated_at

    protected $casts = [
        'is_paid'   => 'boolean',          // stored as tinyint 0/1
        'placed_on' => 'datetime',
    ];
}
```

The temptation to "fix the schema while we're in here" is exactly what turns a six-month migration into an eighteen-month one. The old code is still writing to those columns. Rename `is_paid` and you've broken the half of the app you haven't migrated yet. Schema cleanup is a separate project you do *after* the legacy writers are gone, not during.

If shared writes genuinely aren't possible — separate databases, or the new service needs a different store — you need a synchronization strategy, and the sane one is a single owner per piece of data. Whichever system owns a table writes it; the other reads through an API or a replica and never writes directly. The moment both systems write the same row, you've signed up for conflict resolution, and that's a distributed-systems problem you did not mean to take on.

One concrete gotcha: **sessions and auth**. If the new app can't read the legacy login, every migrated route bounces the user to a second login and the illusion of one site collapses. Decode the legacy session format, or issue a shared token both sides accept, before you migrate anything behind auth. On that 2011 monolith it meant reading its PHP session files from Laravel and trusting the user id inside — ugly, but it kept people logged in across the seam, which is the only thing users noticed.

## When to stop — and the failure mode

Here's the uncomfortable truth the pattern's fans skip. The strangler fig has a specific way of dying: it never finishes. The easy 80% gets migrated in a few months, everyone celebrates, priorities shift — and the gnarly last 20% sits behind the proxy forever. Now you run *two* stacks in production permanently, pay two sets of dependency updates and security patches, and onboard every new hire into both. The dual-maintenance cost was supposed to be temporary. It became the architecture.

The defense is to name the finish line before you start and treat it as a real deliverable, not a someday. "Delete the legacy `php-fpm` pool and remove the `location /` fallback from nginx" is a ticket with an owner and a date, same as any feature. If retiring the last routes can't be justified against next quarter's roadmap, that's a signal worth taking seriously: maybe those routes are fine as they are, and the honest move is to stop, wrap them cleanly, and declare *that* the end state — not to pretend a migration is ongoing that nobody's funding.

You know you're actually done when the legacy upstream serves zero requests for a couple of weeks. Log the fallthroughs — count every request that hits `location /` — and watch the number decay. When it hits zero and stays there, you delete the fallback and the old pool, and the tree the vine grew around is finally gone.

## The costs, stated plainly

None of this is free. During the overlap you carry real, ongoing costs:

- **Two deployment pipelines**, two sets of logs, two runtimes to keep patched.
- **A proxy layer** that is now a critical piece of infrastructure — if it goes down, everything does, so it needs the monitoring and care of a production service.
- **Boundary bugs**: headers, cookies, and encodings that were implicit inside one process now have to survive a hop. Expect to lose an afternoon to a mangled `Set-Cookie` or a double-encoded UTF-8 body at least once.

The trade you're making is spreading risk over time instead of concentrating it on cutover day, and paying dual maintenance for the privilege. For a system that has to stay up and can't afford a failed rewrite, that trade is almost always worth it. For a small app you could genuinely rewrite in three weeks — just rewrite it. The strangler fig is machinery for migrations too big to do at once, and machinery has overhead.

## FAQ

### How long should a strangler fig migration take?

However long it takes to migrate the routes, plus the discipline to actually finish. The danger isn't the timeline, it's the tail — set a hard finish-line ticket for deleting the legacy system, or the last 20% never gets migrated and you run two stacks forever.

### Do I need a service mesh or Kubernetes for this?

No. A single nginx config with `proxy_pass` and an allowlist of migrated paths covers the majority of cases. Reach for heavier routing infrastructure only when you need per-request logic — percentage rollouts, per-user targeting — and even then an application-level proxy route in your framework is usually enough.

### Should the new and old systems share a database?

During the overlap, yes, if you can — one shared database is dramatically simpler than syncing two. Map the legacy schema to clean models in code rather than renaming columns, since the old app is still writing to them. Save schema cleanup for after the legacy writers are retired.

### What's the difference between the strangler fig pattern and a branch-by-abstraction?

Strangler fig routes whole requests to new or old systems at the edge, usually via a proxy. Branch-by-abstraction works inside one codebase, hiding old and new implementations behind an interface and switching between them. They compose well — use the proxy for coarse route-level cutover and abstractions for finer swaps within a service.

## Where this leaves you

The strangler fig isn't a clever trick, it's a way to make a scary migration boring — small reversible steps, each shipped to production, each teaching you something before the next. The proxy makes cutover a one-line change; sharing the database keeps your data honest; and a real finish-line ticket is what stops the whole thing from becoming permanent.

If you're staring at a legacy system right now, don't plan the rewrite. Pick one leaf route, stand up the proxy, and migrate that single URL this week. You'll learn more from one route in production than from a month of rewrite design docs — and you'll have something shipped to show for it.
