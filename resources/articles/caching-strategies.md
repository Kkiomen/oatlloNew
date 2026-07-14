---
name: "Caching Strategies: Cache-Aside, Write-Through, and More"
slug: caching-strategies
short_description: "A practical guide to caching strategies: cache-aside, read-through, write-through, write-behind, and refresh-ahead, with trade-offs and PHP examples."
language: en
published_at: 2027-02-17 09:00:00
is_published: true
tags: [caching, redis, php, architecture]
---

Most performance problems I get called in to fix are not slow algorithms. They are the same query running ten thousand times against a database that has not changed. That is where caching strategies earn their keep, and picking the wrong one is how you end up debugging a "phantom" bug at 2 a.m. where the app shows data that no longer exists.

This post is a map of the main patterns: cache-aside, read-through, write-through, write-behind, and refresh-ahead. I will show where each one fits, where each one bites, and give you PHP you can actually drop into a project. If you want the Laravel- and Redis-specific mechanics, I lean on those in the [Redis caching patterns](/blog/redis-caching-patterns) and [caching database queries in Laravel](/blog/laravel-cache-queries) posts; this one is about choosing the shape before you write the code.

## Why the strategy matters more than the tool

You can run any of these patterns on Redis, Memcached, or an in-process array. The store is a detail. What actually decides whether your cache helps or hurts is the read/write flow: who populates the cache, who writes to the database, and what happens on a miss.

Get that flow right and you cut database load by an order of magnitude. Get it wrong and you get stale data, thundering-herd outages, or writes that silently vanish. Three concerns cut across every strategy, so keep them in the back of your mind:

- **TTL**: how long an entry lives before it is considered expired.
- **Invalidation**: how you evict or update an entry when the source of truth changes.
- **Cache stampede**: what happens when a popular key expires and a hundred requests all miss at once.

I will come back to these, because they are usually where the real work is.

## Cache-aside (lazy loading)

Cache-aside is the one most teams already use, often without naming it. The application is in charge. On a read, it checks the cache first. On a miss, it loads from the database, stores the result, and returns it. The cache only ever holds data that someone actually asked for.

```php
function getUser(int $id): array
{
    $key = "user:{$id}";

    // 1. Try the cache
    $cached = $redis->get($key);
    if ($cached !== false) {
        return json_decode($cached, true);
    }

    // 2. Miss: load from the source of truth
    $user = $db->selectOne('SELECT * FROM users WHERE id = ?', [$id]);

    // 3. Populate the cache for next time, with a TTL
    $redis->setex($key, 300, json_encode($user));

    return $user;
}
```

The appeal is honesty about failure. If the cache is down, `get()` returns false, you fall through to the database, and the site stays up (slower, but up). The data model in the cache is also free to differ from your tables. You cache the assembled DTO, not raw rows.

The catches are real, though. Every new key pays a "first request is slow" tax while it warms up. And because the app writes to both the cache and the database in separate places in your code, invalidation is on you. Forget to evict `user:42` after an update and you serve stale data until the TTL saves you.

**Fits best when:** reads dominate writes, and you can tolerate short windows of staleness bounded by TTL. This is the sane default for most web apps.

## Read-through

Read-through looks almost identical from the outside, but the responsibility moves. Instead of your application code doing the check-miss-load-populate dance, a caching library or provider sits in front of the database and does it for you. Your code just asks the cache for the key; the cache loads on a miss.

In PHP you rarely get a true read-through provider the way Java's Ehcache or Hazelcast offers. What you get instead is a wrapper that hides the pattern:

```php
$user = $cache->remember("user:{$id}", 300, function () use ($db, $id) {
    return $db->selectOne('SELECT * FROM users WHERE id = ?', [$id]);
});
```

Laravel's `remember()` is effectively cache-aside dressed up as read-through: the loader closure is the "on miss" hook, and the framework handles the check and the store. The practical win is that the loading logic lives in one place, so you stop copy-pasting the three-step block across controllers.

**Fits best when:** you want cache-aside behaviour but with the loading centralized. If your team keeps forgetting step 3, this discipline is worth it.

## Write-through

Now flip to the write path. With write-through, every write goes to the cache and the database together, synchronously, in the same operation. The cache is never stale for data you have written, because you updated it at the same moment you updated the source of truth.

```php
function updateUserEmail(int $id, string $email): void
{
    $key = "user:{$id}";

    // 1. Write to the database (source of truth)
    $db->update('UPDATE users SET email = ? WHERE id = ?', [$email, $id]);

    // 2. Write through to the cache in the same request
    $user = $db->selectOne('SELECT * FROM users WHERE id = ?', [$id]);
    $redis->setex($key, 300, json_encode($user));
}
```

The reward is read-after-write consistency. A user changes their email and immediately sees the new one, no TTL gamble, no stale read. Pairing write-through reads with cache-aside is a common and solid combo.

The cost is write latency. Every write now does two round trips, and if the cache write fails you have to decide: fail the whole operation, or let the cache drift and rely on TTL to heal it? I usually let the database write win and treat the cache write as best-effort, because losing a user update is far worse than serving one stale record for five minutes. You also cache data nobody may ever read again, which wastes memory on write-heavy keys.

**Fits best when:** you need fresh reads right after a write and your write volume is modest relative to reads.

## Write-behind (write-back)

Write-behind is write-through's impatient cousin. You write to the cache and return immediately. A background process flushes the change to the database asynchronously, often batched.

This is the fastest write path you can offer, because the client never waits on the database. It also lets you coalesce writes: if the same counter is incremented 500 times a second, you can flush one aggregated value instead of 500 statements. Think view counts, like counters, telemetry.

The danger is blunt: if the cache node dies before the flush, those writes are gone. There is no source of truth yet, only a volatile buffer. I have used write-behind happily for metrics where losing a few seconds of counts is acceptable, and I would never use it for a payment or an order without durable queuing behind it.

```php
function incrementViews(int $postId): void
{
    // Fast path: bump the counter in the cache only
    $redis->incr("post:{$postId}:views");
    // A scheduled worker reads these keys every N seconds
    // and writes the aggregated totals to the database.
}
```

**Fits best when:** write throughput is the priority and occasional loss of the most recent writes is survivable.

## Refresh-ahead

Refresh-ahead tries to make sure a hot key never expires on a user's request. The cache proactively reloads an entry that is about to expire, in the background, before anyone hits a miss. Popular keys stay warm and readers never pay the reload cost.

It is the trickiest to get right. You need to know which keys are hot, and you refresh on a prediction that they will be requested again. Guess wrong and you burn database queries refreshing data nobody wanted. A lighter, more common variant is probabilistic early expiration, where a request occasionally recomputes the value slightly before the real TTL, spreading the reload cost across time instead of stacking it on one unlucky request. That variant also happens to be one of the cleanest defences against stampede.

**Fits best when:** you have a known set of hot, expensive-to-compute keys and want to hide reload latency from users.

## The cross-cutting problems

### TTL is a design decision, not a default

A TTL of "300 because that felt right" is how staleness bugs are born. Match the TTL to how fast the data actually changes and how wrong you are allowed to be. Reference data that changes weekly can live for hours. A price that traders watch might tolerate seconds. When in doubt, shorter is safer, and you buy back the extra database load with a better invalidation strategy.

### Invalidation is the hard part

There are, famously, only two hard problems in computing, and one of them is naming things and off-by-one errors. Invalidation is the other. Two workable approaches:

- **Expiry-based**: let the TTL do it. Simple, eventually consistent, and you accept a staleness window.
- **Event-based**: evict or rewrite the key the moment the underlying data changes (on the update, via a model observer, or a domain event). Precise, but every write path has to remember to fire it.

Most real systems use both: event-based eviction for the keys that matter, TTL as the safety net for everything you forgot.

### Cache stampede

A popular key expires. In the same millisecond, 200 requests miss, and all 200 slam the database with the identical query. The database, which was fine a second ago, falls over. That is a stampede (or dogpile).

Three defences, roughly in order of how often I reach for them:

- **Locking / single-flight**: the first miss acquires a lock and does the load; everyone else waits for the result. One database hit instead of 200.
- **Probabilistic early expiration**: recompute slightly before expiry so the reload never lands on a synchronized cliff.
- **Stale-while-revalidate**: serve the expired value while one background job refreshes it. Readers never block.

If you are chasing latency specifically, the [reduce TTFB](/blog/reduce-ttfb) post gets into how stampede protection shows up in real response times. And if your cache holds expensive LLM output rather than database rows, the same stampede logic applies with even higher stakes per miss, which I covered in [caching LLM responses](/blog/cache-llm-responses).

## Comparing the strategies

Here is the mental model I use when someone asks "which one":

- **Cache-aside**: read pattern, app-managed, resilient to cache outages, staleness bounded by TTL. Complexity: low. Your default.
- **Read-through**: read pattern, library-managed, centralizes loading. Complexity: low to medium. Use for discipline.
- **Write-through**: write pattern, synchronous, fresh reads after writes, slower writes. Complexity: medium. Use when consistency matters.
- **Write-behind**: write pattern, asynchronous, fastest writes, risk of loss. Complexity: high. Use for high-volume, loss-tolerant writes.
- **Refresh-ahead**: read pattern, proactive, hides reload latency, wasteful if you guess wrong. Complexity: high. Use for known hot keys.

The axis that runs through all of them is the same trade-off triangle: **consistency**, **latency**, and **complexity**. Write-through buys consistency with latency. Write-behind buys latency with consistency (and complexity). Cache-aside keeps complexity low and accepts bounded staleness. There is no free option; there is only the one that matches what your app can afford to be wrong about.

In practice you rarely pick just one. The workhorse setup is cache-aside (or read-through) on reads, write-through on the handful of entities that need fresh reads, and write-behind reserved for counters and metrics. Layer the stampede protection on the keys that are both hot and expensive, and leave the rest on plain TTL.

## FAQ

### What is the difference between cache-aside and read-through?

Both load from the database on a miss and populate the cache. The difference is who does it. In cache-aside, your application code runs the check-miss-load-store logic. In read-through, a caching library or provider does it behind a single `get` call. Cache-aside is more common in PHP because true read-through providers are rare; `remember()`-style helpers give you most of the benefit.

### Which caching strategy should I use by default?

Cache-aside. It is simple, it degrades gracefully when the cache is unavailable, and its staleness is bounded by the TTL you choose. Add write-through only for the specific entities where a user must see their own write immediately, and reach for write-behind only when write throughput forces your hand.

### How do I prevent a cache stampede?

Stop many simultaneous misses from all hitting the database. The most reliable fix is a lock so only the first request rebuilds the value while the rest wait. Probabilistic early expiration and stale-while-revalidate are good complements, especially for keys that are expensive to recompute.

### Is write-behind safe for critical data?

Not on its own. Write-behind acknowledges the write before it reaches the database, so a cache failure before the flush loses data. For anything you cannot afford to lose, back it with a durable queue or use write-through instead. Save write-behind for counters and metrics where losing the last few seconds is acceptable.

## Conclusion

Caching strategies are not a menu where one option is "best". They are trade-offs against three constraints: how fresh your reads must be, how fast your writes must return, and how much complexity your team can carry. Pick against those, not against fashion. And whatever shape you land on, the three cross-cutting decisions are what actually save you: set the TTL deliberately, plan invalidation before you ship, and protect the hot keys from stampede before the first traffic spike finds them.

The mechanics live in the store-specific posts. The judgment lives here: name the strategy on purpose, and the "phantom" bugs mostly stop happening.