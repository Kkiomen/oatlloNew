---
slug: api-rate-limiting-token-bucket-vs-fixed-window-carousel
type: carousel
language: en
title: "Rate limit algorithms"
topic: redis
source_type: article
source: api-rate-limiting-token-bucket-vs-fixed-window
link: https://oatllo.com/api-rate-limiting-token-bucket-vs-fixed-window
publish_at: 2026-09-16 19:00
status: ready
formats: [post, reel]
hashtags: [api, ratelimiting, redis, backend, laravel]
caption: |
  A 100 req/min fixed window happily serves 200 requests in one second, and every one of them is legal.

  The window resets on a clock edge, so a client can drain it at :59 and again
  at :00. Token bucket gives you a burst dial instead.

  Full comparison linked in bio.

  Which limiter is sitting in front of your API right now?
---

## A fixed window limiter lets clients push 2x your rate limit for free.

The counter resets on a hard clock edge. Nobody is cheating - the algorithm
genuinely allows it. Whether it hurts depends on what is behind the endpoint.

<!-- slide -->

## The boundary burst, in one picture

```text
limit = 100 per minute

00:00:59  -> 100 requests  (window A)
00:01:00  -> 100 requests  (window B)

200 requests in one second. Both legal.
```

<!-- slide -->

## Token bucket has two dials, not one

```php
new TokenBucket($redis,
    capacity: 20,     // burst you tolerate
    refillRate: 1.0,  // sustained rate/sec
);
```

"1 request/second sustained, but I allow a burst of 20." A fixed window cannot
express that at all. This is why Stripe and AWS reach for it.

<!-- slide -->

## The readable version has a race

```php
// Read-modify-write across two calls races.
$this->redis->hMGet($key, ['tokens', 'ts']);
$this->redis->hMSet($key, [...]);
// Production: move it into a Lua EVAL script.
```

Nothing ticks in real time. You store `tokens` plus a timestamp and recompute
the refill lazily on the next read. That is the whole trick.

<!-- slide -->

## Tell the client when to come back

```text
HTTP/1.1 429 Too Many Requests
Retry-After: 12
X-RateLimit-Remaining: 0
```

Send the headers on every response, not just the 429. A well-behaved client
slows down before it hits the wall.

<!-- slide role="cta" -->

## Keep the counter in a shared store

Two app servers with per-process counters hand a client 2x the limit for free.
Redis is the point, not a detail.
