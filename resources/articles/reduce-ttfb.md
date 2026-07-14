---
name: "How to Reduce TTFB (Time to First Byte) in Web Apps"
slug: reduce-ttfb
short_description: "Practical ways to reduce TTFB in web apps: cache rendered output, kill slow queries, enable OPcache, add a CDN, and measure it right."
language: en
published_at: 2027-01-20 09:00:00
is_published: true
tags: [performance, ttfb, caching, laravel]
---

If you want to reduce TTFB, the first thing to get straight is what that number actually measures. Time to First Byte is the delay between the browser sending a request and the first byte of the response coming back. It is a server-and-network problem, not a rendering problem. No amount of image lazy-loading or JavaScript code-splitting will move it, because all of that happens *after* the byte you are trying to speed up has already arrived.

I've spent a fair share of debugging sessions staring at a 900ms TTFB on a page that "felt fine" locally. Local is a lie: no network hop, warm caches, a database with 200 rows instead of two million. This post is about what actually moves the needle in production.

## What TTFB is (and what it is not)

TTFB is the sum of a few things that happen before your app sends anything:

- **DNS + TCP + TLS handshake** — the connection setup and network latency between the client and your server.
- **Server processing** — your framework booting, running middleware, hitting the database, rendering the response.
- **Time until that first byte leaves the server** and reaches the client.

What TTFB is *not*: it is not total page load time. A page can have a great 120ms TTFB and still take six seconds to become usable because of a 3MB JavaScript bundle. Conversely, a page with almost no front-end weight can feel sluggish purely because the server sat on the request for a second. Keep the two separate in your head, because they get fixed in completely different places.

A rough field guide for the server-processing portion: under 200ms is good, 200–500ms is a yellow flag, and anything past 800ms means something specific is wrong and worth chasing down.

## Measure first, guess never

Before changing anything, get a number you trust. The quickest honest measurement is `curl`, which reports timing without any browser rendering noise in the way.

```bash
# time_starttransfer is TTFB; time_total is the whole download
curl -w "TTFB: %{time_starttransfer}s | total: %{time_total}s\n" -o /dev/null -s https://your-app.test/some-page
```

Run it a handful of times. The first request after a deploy or a cache flush is almost always slower (cold caches, empty OPcache), so ignore the first hit and look at the steady-state numbers.

For a breakdown that separates connection setup from server thinking time, expand the format string:

```bash
curl -w "dns: %{time_namelookup}s\nconnect: %{time_connect}s\ntls: %{time_appconnect}s\nttfb: %{time_starttransfer}s\n" -o /dev/null -s https://your-app.test/some-page
```

If `time_appconnect` (TLS done) is already at 300ms, your problem is the network and handshake, not your controller. If TLS finishes at 80ms but TTFB is 900ms, the app is the bottleneck. That single comparison tells you which half of this article to read.

WebPageTest gives you the same split from real locations around the world, and the browser devtools Network panel shows "Waiting for server response" per request. Use those to confirm what `curl` tells you.

## Fix the server side

Most high TTFB I've run into lives here. In rough order of how often it's the culprit:

### Slow database queries and N+1

This is the number one offender in ORM-heavy apps. A page that fires 140 tiny queries because it loads a relationship inside a loop will have a fine-looking query log (every query is 2ms) and a terrible TTFB. Eager-load relationships, add the indexes your `WHERE` and `JOIN` columns need, and read the query plan before assuming a query is fine. There's a full walkthrough of that in [optimizing SQL queries with EXPLAIN](/blog/optimizing-sql-queries-with-explain).

If you can't tell where the time goes, profile the request instead of squinting at logs. I wrote up my approach in [how to profile a slow PHP application](/blog/profile-slow-php-application).

### No caching of expensive work

If a page renders the same output for every visitor and you recompute it on every request, that's wasted server time on every single hit. Cache the result.

- Cache **rendered output** or the expensive data that feeds it, keyed so it invalidates when the underlying data changes.
- Reach for Redis for anything that needs to survive across requests and processes.
- Set a sensible TTL rather than caching forever and hoping you remember to bust it.

For the Laravel specifics of caching query results and tagging them for invalidation, see [caching queries in Laravel](/blog/laravel-cache-queries) and the patterns roundup in [Redis caching patterns](/blog/redis-caching-patterns).

### OPcache is off (or misconfigured)

On PHP, without OPcache the engine recompiles your scripts to bytecode on every request. Turning it on is one of the cheapest TTFB wins available in production.

```php
; php.ini, production settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
; in production, skip the timestamp check so PHP doesn't stat every file
opcache.validate_timestamps=0
```

With `validate_timestamps=0` you have to clear OPcache on every deploy, otherwise your new code won't take effect. That's the trade: a little deploy discipline for less work per request.

### Framework bootstrap on every request

Frameworks like Laravel rebuild the whole application container on each request under the traditional PHP-FPM model. For request-heavy APIs, an application server that keeps the app booted in memory (Laravel Octane, for example) removes that repeated bootstrap cost. It's a bigger change with its own gotchas around shared state, so treat it as a step you take after the cheaper wins, not before.

## Fix the network side

If your TLS-handshake timing was the slow part, no query tuning will help. Look here instead.

### Put a CDN in front

A CDN reduces TTFB in two distinct ways, and it's worth being precise about which:

1. **For cacheable responses**, the CDN serves the content from an edge node physically near the user, so the request never travels to your origin at all. This is the big win.
2. **For dynamic responses**, a good CDN still terminates TLS at the edge and keeps warm connections back to your origin, shaving handshake and latency off requests it can't cache.

To get the first benefit you have to actually send caching headers so the CDN is allowed to store the response:

```nginx
# Cache a public, static-ish response at the edge for 5 minutes
location /articles/ {
    add_header Cache-Control "public, max-age=300, s-maxage=300";
}
```

`s-maxage` targets shared caches (CDNs and proxies) specifically, so you can let the edge cache aggressively while keeping browsers conservative. Never send these headers on responses that contain per-user data.

### Keep connections alive and use HTTP/2

Re-doing the TCP and TLS handshake for every asset is pure latency. Enable keep-alive so connections are reused, and serve over HTTP/2 (or HTTP/3) so multiple requests share one connection instead of queuing. This mostly affects the connection-setup slice of TTFB, but on high-latency links it's a real chunk.

### Move the server closer to the users

If your users are in Europe and your box is in Virginia, every request eats a transatlantic round trip you can't optimize away in code. For a genuinely global audience, either move the origin closer to where the traffic actually is, or lean harder on edge caching so most requests never reach the origin.

## A step-by-step to actually reduce TTFB

Here's the order I follow so I'm not guessing:

1. **Measure the baseline** with `curl -w "%{time_starttransfer}"`, ignoring the first cold request.
2. **Split network from app** by comparing `time_appconnect` against `time_starttransfer`. That tells you which section below to prioritize.
3. **If the app is slow**, profile one representative request and find the biggest single cost — it's usually queries.
4. **Fix queries and add indexes**, then remeasure. Don't stack changes; verify each one moved the number.
5. **Cache the expensive path** (Redis for rendered output or query results) and confirm the second request is faster than the first.
6. **Turn on OPcache** in production and clear it on deploy.
7. **If the network is slow**, add a CDN, send correct caching headers, and enable HTTP/2 with keep-alive.
8. **Remeasure from a real user location** with WebPageTest, not from a machine sitting next to the server.

The remeasure-after-each-step part is the one people skip, and it's the one that stops you from shipping five "optimizations" where only one did anything.

## FAQ

### What is a good TTFB?

For the server-processing portion, aim for under 200ms; 200–500ms is acceptable but improvable, and consistently above 800ms points at a specific fixable cause. Remember these numbers include network latency, so a user far from your server will always see a higher floor than your local `curl`.

### Why is my TTFB so high on only some pages?

Almost always a query or computation specific to those pages: an N+1 relationship, a missing index on a filtered column, or an expensive report generated on the fly. Pages that are fast usually hit a cache or a trivial query; slow pages don't. Profile the slow one and compare.

### Does a CDN reduce TTFB for dynamic, logged-in pages?

Partially. A CDN can't cache a per-user page, so the origin still does the work. But it still terminates TLS at the edge and reuses a warm connection to your origin, which trims the handshake and latency portion. The dramatic wins come only from responses the CDN is actually allowed to cache.

### Is TTFB the same as page load time?

No. TTFB stops the moment the first response byte arrives. Everything after it (downloading HTML, CSS, JS, rendering, hydration) is separate and lives in the front end. You can have an excellent TTFB and a slow page, or the reverse. Fix them in the right place.

## Wrapping up

Reducing TTFB comes down to two questions: is the delay in the network or in the app, and what is the single biggest cost inside whichever one it is. Measure with `curl` to split the two, profile to find the real bottleneck, then cache aggressively, kill slow queries, and enable OPcache on the app side, and add a CDN with correct caching headers on the network side.

Do it one change at a time and remeasure after each. A page that took 900ms to send its first byte can usually get under 200ms without heroics, once you stop guessing and start measuring.