---
slug: caching-strategies-carousel
type: carousel
language: en
title: "Caching strategies"
topic: caching
source_type: article
source: caching-strategies
link: https://oatllo.com/caching-strategies
publish_at: 2026-09-23 19:00
status: ready
formats: [post, reel]
hashtags: [caching, redis, php, architecture, performance]
caption: |
  Write-behind acknowledges the write before it reaches the database, so a dead cache node loses it.

  Fine for view counts. Never for an order. The store is a detail - what decides
  whether a cache helps or hurts is who writes what, and when.

  Full map linked in bio.

  Which strategy is your app running without naming it?
---

## The cache node dies before the flush. Those writes are gone.

That is write-behind: you write to the cache, return immediately, and a worker
flushes later. There is no source of truth yet, only a volatile buffer.

<!-- slide -->

## Where write-behind actually earns it

```php
function incrementViews(int $postId): void
{
    // Fast path: cache only. No DB write yet.
    $redis->incr("post:{$postId}:views");
}
```

500 increments per second coalesce into one flushed value. Losing a few seconds
of view counts is survivable. Losing a payment is not.

<!-- slide -->

## Cache-aside is the honest default

```php
$cached = $redis->get($key);
if ($cached !== false) {
    return json_decode($cached, true);
}

$user = $db->selectOne('SELECT ...', [$id]);
$redis->setex($key, 300, json_encode($user));
```

Cache down? `get()` returns false, you fall through, the site stays up.

<!-- slide -->

## A popular key expires. 200 requests miss.

All 200 slam the database with the identical query in the same millisecond.
The fix is a lock: the first miss loads, the rest wait for it. One query
instead of 200.

<!-- slide -->

## TTL of 300 "because it felt right"

That is how staleness bugs are born. Match the TTL to how fast the data changes
and how wrong you are allowed to be. Event-based eviction for keys that matter,
TTL as the net.

<!-- slide role="cta" -->

## There is no free option

Consistency, latency, complexity - pick which two you are buying. Write-through
trades latency for fresh reads. Full map linked in bio.
