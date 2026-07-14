---
name: "Redis Caching Patterns for Web Applications: A Practical Guide"
slug: redis-caching-patterns
short_description: "Battle-tested Redis caching patterns for web apps: cache-aside, write-through, TTLs, stampede protection, and the right data structures."
language: en
published_at: 2026-11-18 09:00:00
is_published: true
tags: [redis, caching, laravel, performance]
---

Most slow endpoints I've profiled were not slow because the code was bad. They were slow because they asked the database the same question a few hundred times a minute. That is exactly the gap the right **redis caching patterns** are meant to close, and picking the wrong one quietly creates a different set of problems.

This guide walks through the patterns I actually reach for in production web apps, when each one fits, and the mistakes that cost me a weekend or two. Examples use raw `redis-cli` so you can see what happens on the wire, plus Laravel's `Cache` and `Redis` facades so you can drop it into a real app.

One thing worth saying up front: Redis is single-threaded for command execution. Commands run one at a time, so individual operations like `INCR` or `SETNX` are atomic without you doing anything special. That single fact underpins half the patterns below.

## Cache-aside (lazy loading): the default you should start with

Cache-aside is the pattern you'll use 80% of the time. Your application code owns the logic. Check the cache; on a miss, read the source, then write the value back.

The flow reads like this:

1. Look for the key in Redis.
2. If it's there (a hit), return it and you're done.
3. If it's not (a miss), query the database, store the result with a TTL, return it.

In raw Redis it looks like this:

```bash
# Miss: nothing there yet
redis-cli GET user:42
# (nil)

# After the app loads from DB, it writes with a 1-hour expiry (EX = seconds)
redis-cli SET user:42 '{"id":42,"name":"Ada"}' EX 3600
# OK

redis-cli GET user:42
# "{\"id\":42,\"name\":\"Ada\"}"
```

The `EX 3600` sets a 3600-second TTL. That expiry is not decoration — it's your safety net against stale data and unbounded memory growth. Never cache without deciding on a TTL first.

In Laravel, `Cache::remember()` is cache-aside in one call. It checks the store, runs the closure only on a miss, and caches the return value:

```php
use Illuminate\Support\Facades\Cache;

$user = Cache::remember("user:{$id}", now()->addHour(), function () use ($id) {
    return User::findOrFail($id);
});
```

The upside: you only ever cache data that's actually requested, so cold or rarely-touched rows don't waste memory. The tradeoff is a slower first request per key, and the risk of stampedes (more on that below). If you're caching Eloquent query results specifically, I wrote up the mechanics separately in [caching Laravel queries](/blog/laravel-cache-queries).

## Write-through: keep the cache honest on writes

Cache-aside populates the cache on reads. Write-through populates it on writes: whenever you update the database, you update Redis in the same operation. Reads then almost always hit a warm, current cache.

```php
public function updateProfile(int $id, array $data): User
{
    $user = User::findOrFail($id);
    $user->update($data);

    // Write straight through to the cache so the next read is fresh
    Cache::put("user:{$id}", $user->fresh(), now()->addHour());

    return $user;
}
```

Write-through shines for data that's read far more often than it changes and where stale values are genuinely harmful — account balances, feature flags, pricing. The cost is that every write does extra work, and if you have write-heavy keys nobody reads, you're caching for no reason.

A common middle ground I use: cache-aside for reads, plus an explicit `Cache::forget("user:{$id}")` on write. You invalidate instead of rewriting, and the next reader repopulates. Simpler than full write-through, and it dodges the "wrote a value nobody read" waste.

## TTLs and expiry: the part people skip

A few habits that have saved me:

- **Always set an expiry.** A key without a TTL lives until something evicts or deletes it. Multiply that by a busy app and you'll meet your `maxmemory` limit sooner than you'd like.
- **Jitter your TTLs.** If ten thousand keys are all written with `EX 3600` during a deploy, they all expire in the same second an hour later. Add a random spread, e.g. `3600 + rand(0, 300)`, so expirations fan out.
- **Match the TTL to how fresh the data must be**, not to a round number that felt nice. A homepage feed might tolerate 60 seconds; a user's permission set might not.

You can inspect and adjust TTLs live:

```bash
redis-cli TTL user:42        # seconds left, -1 = no expiry, -2 = key gone
redis-cli EXPIRE user:42 120 # reset it to 120 seconds
redis-cli PERSIST user:42    # remove the expiry entirely (use with care)
```

## Cache stampede: the failure mode nobody warns you about

Here's the scenario that bit me. A popular key expires. In the same instant, five hundred requests all miss the cache, all hit the database with the identical expensive query, and the database falls over. The cache was supposed to protect it, and its expiry became the trigger.

Two defenses I lean on:

**A short-lived lock so only one worker rebuilds.** Redis makes this cheap with `SET ... NX`, which sets a key only if it doesn't already exist. It returns success to exactly one caller:

```bash
# Only one caller wins this; the rest get (nil)
redis-cli SET lock:user:42 1 EX 10 NX
```

Laravel wraps the same idea in atomic locks:

```php
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock("rebuild:user:{$id}", 10);

if ($lock->get()) {
    try {
        $value = User::findOrFail($id);
        Cache::put("user:{$id}", $value, now()->addHour());
    } finally {
        $lock->release();
    }
}
```

The winner rebuilds; everyone else either waits briefly or serves the slightly stale value. Either beats a database dogpile.

**Probabilistic early expiration.** Instead of everyone recomputing at the exact expiry moment, each request has a small, growing chance of recomputing *before* the key expires. As the key ages, that probability rises, so one unlucky-but-early request refreshes the value while the rest still get cache hits. It smooths the cliff into a gentle slope. This is the "XFetch" approach if you want to read the paper behind it.

## Pick the right data structure

Redis is not a key-value store with strings and nothing else. Matching the structure to the job keeps things both fast and readable.

- **Strings** for simple scalar or serialized values: a rendered HTML fragment, a JSON blob, a counter.
- **Hashes** for objects, when you want to read or update one field without deserializing the whole thing. `HSET user:42 name Ada plan pro`, then `HGET user:42 plan`.
- **Sorted sets** for leaderboards and time-windowed data. Score by points or by timestamp, then range-query. `ZADD leaderboard 4820 player:7`, `ZREVRANGE leaderboard 0 9 WITHSCORES` for the top ten.
- **Sets** for uniqueness and membership: "have we seen this IP today", tag collections, deduplication. `SADD seen:2026-11-18 1.2.3.4` returns 1 the first time, 0 after.

```bash
redis-cli HSET user:42 name Ada plan pro
redis-cli HGET user:42 plan
# "pro"

redis-cli ZADD leaderboard 4820 player:7
redis-cli ZREVRANGE leaderboard 0 2 WITHSCORES
```

## Atomic operations you'll use constantly

Because command execution is single-threaded, these are safe under concurrency with no extra locking:

- `INCR page:views:home` increments a counter and returns the new value. Great for view counts and metrics.
- `SETNX key value` sets only if absent. The classic "acquire a slot" primitive.
- `SET key value EX 60 NX` — set-with-expiry-if-absent in one atomic command. This is the correct way to build a self-expiring lock; doing `SETNX` then `EXPIRE` as two steps has a race window if the process dies between them.

Counters via `INCR` are also the backbone of a fixed-window rate limiter. If you're deciding between limiting strategies, I compared the approaches in [token bucket vs fixed window rate limiting](/blog/api-rate-limiting-token-bucket-vs-fixed-window).

## Pub/sub for invalidation across nodes

When you run several app servers, a write on one needs to invalidate caches everywhere. Redis pub/sub gives you a lightweight broadcast: publishers push messages to a channel, and every subscriber gets them.

```bash
# Terminal 1
redis-cli SUBSCRIBE cache-invalidation

# Terminal 2
redis-cli PUBLISH cache-invalidation "user:42"
```

Keep in mind pub/sub is fire-and-forget. Subscribers that are offline miss the message entirely; there's no replay. For guaranteed delivery you want Redis Streams or a real queue. For "drop this local key on all nodes," pub/sub is perfectly fine.

## Eviction: what happens when memory fills up

If you don't cap memory, Redis keeps accepting writes until the host runs out of RAM, and then bad things happen. Set a ceiling and an eviction policy so it sheds load gracefully instead:

```bash
redis-cli CONFIG SET maxmemory 512mb
redis-cli CONFIG SET maxmemory-policy allkeys-lru
```

Quick guide to the two policies I use most:

- `allkeys-lru` evicts the least-recently-used key from *all* keys when full. This is the right default when Redis is purely a cache and every key is expendable.
- `volatile-lru` only evicts keys that have a TTL set. Useful when you mix cache data with keys you want to keep, since anything without an expiry is protected from eviction.

For production, put these in `redis.conf` rather than setting them at runtime, so they survive a restart.

## Pitfalls I've actually hit

- **Caching without a TTL.** Memory creeps up for weeks, then a 3am page. Set expiries.
- **Stale-but-not-invalidated data after a write.** Either write through or forget the key on every mutation. Pick one and be consistent.
- **Two-step lock (`SETNX` + `EXPIRE`).** If the process crashes between them, the lock never expires and blocks everyone. Use the atomic `SET key val EX n NX`.
- **Synchronized TTLs.** Everything expiring on the same tick recreates the stampede you were avoiding. Add jitter.
- **Caching the wrong shape.** Storing a giant serialized object as a string when you only ever read one field. A hash saves both bandwidth and CPU.
- **Treating pub/sub as reliable delivery.** Offline subscribers miss messages. Don't build critical invalidation on it without a fallback.

## FAQ

### When should I use cache-aside versus write-through?
Default to cache-aside; it's simpler and only caches data that's requested. Reach for write-through when reads vastly outnumber writes and serving stale data would cause real harm, like billing or permissions.

### How do I choose a TTL?
Start from a business question: how stale can this data be before someone notices or gets hurt? Set the TTL to that tolerance, add a little random jitter, and adjust after watching your hit rate. There's no universally correct number.

### Does caching replace a good database index?
No. A cache hides a slow query; an index makes the query itself fast, including the cache-miss path and any query the cache never covers. Do both — I cover the indexing side in [database indexing explained](/blog/database-indexing-explained).

### Is Redis safe for counters under heavy concurrency?
Yes. `INCR` and the other single-command operations are atomic because Redis executes commands one at a time, so concurrent increments never lose updates.

## Wrapping up

If you take one thing from this: start with cache-aside and an honest TTL, add stampede protection before your key gets popular rather than after it takes down your database, and match the data structure to the access pattern instead of defaulting to strings everywhere. Set a `maxmemory` limit with an LRU policy today so future-you isn't debugging an out-of-memory Redis at 3am.

Pick one hot endpoint in your app, wrap it in `Cache::remember()` with a sensible expiry, and measure the before-and-after. That single change is usually the biggest performance win for the least code.