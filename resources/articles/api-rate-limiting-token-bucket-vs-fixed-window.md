---
name: "API Rate Limiting: Token Bucket vs Fixed Window"
slug: api-rate-limiting-token-bucket-vs-fixed-window
short_description: "A practical guide to API rate limiting: fixed window, sliding window, token bucket, leaky bucket, and the 429 response with Retry-After."
language: en
published_at: 2026-08-12 09:00:00
is_published: true
tags: [api, rate-limiting, laravel, redis, backend]
---

The first time an API I maintained fell over, it wasn't a hacker. It was one well-meaning customer running a nightly export in a `while (true)` loop with no sleep. **API rate limiting** is the thing that would have saved me that evening, and it's the topic of this post: how to cap how many requests a client can make in a window of time, which algorithm to reach for, and how to respond when someone goes over.

I'll walk through the four algorithms you'll actually meet in production — fixed window, sliding window, token bucket, and leaky bucket — show working code for the token bucket, and cover the `429` response that ties it all together. If you're building idempotent write endpoints too, this pairs well with the [idempotency keys post](/blog/idempotency-key-api-safe-retries).

## Why rate limit an API at all

Three reasons, roughly in order of how often they bite:

- **Protecting capacity.** One runaway client shouldn't be able to starve everyone else. Rate limiting is fairness enforcement.
- **Cost control.** If each request hits a paid downstream (an LLM, a payment gateway, a geocoder), unbounded traffic is unbounded spend.
- **Abuse and brute force.** Login endpoints, password resets, and coupon checks all get hammered. A limit turns a fast attack into a slow one.

Note that rate limiting is **not** authentication or a WAF. It's a blunt volume control. Keep that scope in mind; it's tempting to ask it to do more than it can.

## Fixed window

The simplest approach. Pick a window (say 60 seconds), keep a counter per client, and reset it when the window rolls over.

```text
key = "rl:{client}:{floor(now / 60)}"
count = INCR key
if count == 1: EXPIRE key 60
if count > limit: reject (429)
```

It's cheap (one counter, one expiry) and easy to reason about. The catch is the **boundary burst**. Because the window resets on a hard clock edge, a client can send `limit` requests at `00:00:59` and another full `limit` at `00:01:00`. That's up to *2x* your intended rate in a one-second sliver. For a 100 req/min limit, someone can legitimately push ~200 requests across the boundary.

Whether that matters depends on what you're protecting. Guarding a beefy read endpoint? The burst is harmless. Guarding a fragile downstream with a hard concurrency ceiling? It'll hurt.

## Sliding window

Sliding window fixes the boundary problem by making "the last 60 seconds" mean the *actual* last 60 seconds, not the current clock minute.

The precise version keeps a timestamp log per client (a Redis sorted set), drops entries older than the window on each request, and counts what's left:

```text
now = current_time_ms
ZREMRANGEBYSCORE key 0 (now - window_ms)   # evict old
count = ZCARD key
if count < limit:
    ZADD key now now
    reject = false
else:
    reject = true
EXPIRE key window_seconds
```

This is **smooth**: no edge bursts, the rate is honest at every instant. The price is memory and CPU: you're storing one entry per request and doing sorted-set operations on every call. At high volume that adds up.

There's a cheaper middle ground, the **sliding window counter**, which blends the current and previous fixed-window counts by weight. It's an approximation, but it kills most of the boundary burst at fixed-window cost. Cloudflare popularized this one, and it's a sensible default when exact precision isn't required.

## Token bucket

This is my usual pick. Imagine a bucket that holds up to `capacity` tokens and refills at a steady `refill_rate` tokens per second. Every request removes one token. If the bucket is empty, the request is rejected.

The elegance is that it decouples two things you actually care about separately:

- **`capacity`** controls how big a burst you tolerate.
- **`refill_rate`** controls the sustained long-run rate.

So you can say "1 request/second sustained, but I'll allow a burst of 20." Fixed window can't express that cleanly. This is why token bucket is what most public APIs (Stripe, AWS) reach for.

Here's an accurate implementation. Redis is the usual store because the state is tiny and shared across app servers, just the two values `tokens` and `last_refill` per client:

```php
<?php

class TokenBucket
{
    public function __construct(
        private \Redis $redis,
        private int $capacity,      // max tokens (burst size)
        private float $refillRate   // tokens added per second
    ) {}

    public function allow(string $clientId, int $cost = 1): bool
    {
        $key  = "rl:tb:{$clientId}";
        $now  = microtime(true);

        // Load current state, or start with a full bucket.
        $state       = $this->redis->hMGet($key, ['tokens', 'ts']);
        $tokens      = isset($state['tokens']) ? (float) $state['tokens'] : $this->capacity;
        $lastRefill  = isset($state['ts'])     ? (float) $state['ts']     : $now;

        // Refill based on elapsed time, capped at capacity.
        $elapsed = max(0.0, $now - $lastRefill);
        $tokens  = min($this->capacity, $tokens + $elapsed * $this->refillRate);

        if ($tokens < $cost) {
            // Persist the refill so we don't lose accrued tokens, then reject.
            $this->redis->hMSet($key, ['tokens' => $tokens, 'ts' => $now]);
            $this->redis->expire($key, (int) ceil($this->capacity / $this->refillRate) + 1);
            return false;
        }

        $tokens -= $cost;
        $this->redis->hMSet($key, ['tokens' => $tokens, 'ts' => $now]);
        $this->redis->expire($key, (int) ceil($this->capacity / $this->refillRate) + 1);

        return true;
    }
}
```

A few things worth calling out:

- There's no countdown running in real time. We store `tokens` plus a timestamp `ts`, then recompute the refill lazily on the next read. No cron, no background job ticking every second. That's the whole trick.
- The `cost` parameter lets an expensive endpoint charge more than one token. Handy for weighting a search call heavier than a `GET /health`.
- **This code is not atomic.** Read-modify-write across two Redis calls has a race under concurrency. In production, move the logic into a Lua script (`EVAL`) so the whole read-refill-decrement runs atomically inside Redis. The PHP above is the readable version; the Lua version is the correct-under-load one.

## Leaky bucket

Leaky bucket is the token bucket's cousin, viewed from the other end. Requests enter a queue (the bucket); they *leak out* (get processed) at a fixed rate. If the queue overflows, new requests are dropped.

The defining property is **output smoothing**: downstream sees a perfectly steady stream regardless of how spiky the input was. Token bucket lets bursts *through* to the downstream; leaky bucket absorbs them and drips them out evenly.

Use leaky bucket when the thing you're protecting genuinely can't handle bursts: a legacy system with a fixed worker pool, or an outbound integration with its own strict limit you must respect. Use token bucket when bursts are fine as long as the average holds. In practice I reach for leaky bucket far less often; most services would rather serve a burst quickly than queue it.

## Comparison

| Algorithm | Burst behavior | Smoothness | Memory | Complexity | Good for |
|---|---|---|---|---|---|
| Fixed window | Allows 2x burst at boundary | Low | 1 counter | Trivial | Simple, high-volume, tolerant endpoints |
| Sliding window (log) | No burst | High | 1 entry/request | Medium | Exact limits, lower volume |
| Sliding window (counter) | Minor burst | Medium-high | 2 counters | Low-medium | Sensible general default |
| Token bucket | Controlled burst up to capacity | Medium | 2 values | Medium | Public APIs, weighted costs |
| Leaky bucket | No burst out; smoothed | Highest output | Queue state | Medium | Protecting burst-intolerant downstreams |

The one-line summary of the **burst-vs-smoothness trade-off**: fixed window is cheap but leaks bursts at the edges; sliding window is smooth but costs more per request; token bucket gives you a burst dial you control; leaky bucket refuses to burst at all and smooths the output.

## The 429 response and Retry-After

Enforcing a limit is only half the job. The client needs to know what happened and when to try again. Otherwise they'll just hammer you harder.

The correct status is **`429 Too Many Requests`**. Pair it with a **`Retry-After`** header, which takes either a number of seconds or an HTTP date:

```text
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 12
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1660294812

{"message": "Rate limit exceeded. Retry in 12 seconds."}
```

The `X-RateLimit-*` headers aren't a formal standard (there's a draft, `RateLimit`/`RateLimit-Policy`), but returning them on *every* response (not just the 429) is a real kindness to clients. A well-behaved consumer will slow itself down before it ever hits the wall.

For token bucket, `Retry-After` is easy to compute: it's how long until enough tokens accrue for the request, i.e. `ceil((cost - tokens) / refill_rate)` seconds.

## Doing it in Laravel

If you're on Laravel, you rarely need to hand-roll any of this for the common cases. The `throttle` middleware wraps a rate limiter you define in a service provider:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function ($request) {
    return $request->user()
        ? Limit::perMinute(120)->by($request->user()->id)
        : Limit::perMinute(30)->by($request->ip());
});
```

```php
// routes/api.php
Route::middleware('throttle:api')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

Laravel's built-in limiter is a fixed-window counter backed by your cache store (Redis in most real deployments), and it emits `429` with `Retry-After` and `X-RateLimit-*` headers for you. When you need token-bucket semantics (controlled bursts, weighted costs), that's when you drop down to the `RateLimiter` facade's `attempt`/`tooManyAttempts` primitives or the custom class above.

## FAQ

**Where should the counter live: app memory or Redis?**
Redis (or another shared store), almost always. In-memory works only if you have a single process; the moment you run two app servers behind a load balancer, per-process counters let a client get `N x limit` by spreading requests across instances. Shared state is the point.

**Should I rate limit by IP or by API key?**
By API key or user ID whenever you have one. It's stable and fair. Fall back to IP for unauthenticated traffic, but know that IP is leaky: corporate NATs and mobile carriers put thousands of users behind one address, and attackers rotate IPs. Many APIs layer both.

**What limit numbers should I pick?**
Start from what your downstream can actually sustain, not a round marketing number. Measure the p99 throughput your slowest dependency tolerates, leave headroom, and set the sustained rate below it. Then set burst capacity to cover legitimate spikes (a page load that fires 10 parallel calls). Tune from real 429 rates, not guesses.

**Is rate limiting enough to stop a DDoS?**
No. Application-layer rate limiting helps against a single abusive client, but a distributed attack spreads across thousands of sources, each staying under your per-client limit. That's a job for network-layer protection and a CDN/WAF upstream. Rate limiting is one layer, not the whole defense.

## Conclusion

Pick by the shape of your traffic and the fragility of what's behind the API. Reach for **fixed window** when you want something trivial and your endpoints tolerate an edge burst. Reach for **sliding window** when the limit has to be exact. Reach for **token bucket** — my default for public APIs — when you want a sustained rate plus a burst dial you control. Reach for **leaky bucket** when the downstream genuinely cannot absorb bursts.

Whatever you choose, keep the state in a shared store like Redis, return a clean `429` with `Retry-After`, and expose `X-RateLimit-*` headers so good clients can behave. Get those right and the nightly-export incident that cost me an evening becomes a non-event: the loop hits the wall, backs off, and everyone else keeps working.