---
name: "Redis vs Memcached: Which Cache to Choose"
slug: redis-vs-memcached
short_description: "How Redis and Memcached differ in data types, persistence, threading, memory, and clustering, plus which one to pick for a Laravel app."
language: en
published_at: 2027-05-21 09:00:00
is_published: true
tags: [redis, memcached, database, devops, laravel]
---

The first time I had to defend this choice in a design review, I opened with "both work" and got a blank stare. That answer is technically correct and completely useless. So here is the honest version: if all you ever do is stash a serialized array under a key and read it back, Redis and Memcached will both serve you fine, and you should pick based on whatever your infra team already runs. The interesting part starts the moment you want the cache to do more than remember things.

This article is about that moment. It covers where the two actually diverge, why the differences exist, and how the decision plays out inside a real Laravel app.

## The honest answer first

For a plain key/value cache, the gap between these two is small enough that operational preference wins. Both keep data in RAM, both are fast enough that the network round trip dominates the timing, both have mature clients in every language. If you inherited a running Memcached cluster and your only job is caching rendered HTML fragments, ripping it out for Redis is busywork.

Redis pulls ahead when the cache stops being just a cache. Rate limiting, a job queue, a leaderboard, a distributed lock, pub/sub between workers, session storage that survives a restart. Memcached does none of those. Once one of them appears on your roadmap, the decision is basically made, because running Redis for those and Memcached for the plain caching means operating two systems where one would do.

So the real question is not "which is faster." It's "how much do you want out of this thing?"

## Data structures: the biggest real difference

Memcached stores strings. That's it. Keys map to opaque blobs, and every richer shape you want, you build in your application by serializing and deserializing on each read and write.

Redis stores strings too, but also lists, sets, sorted sets, hashes, streams, bitmaps, and more, and it exposes operations that run *on the server*. That server-side part is what matters. Consider a counter shared across processes:

```bash
# Memcached: read, add in your app, write back — racy under contention
# unless you use the atomic incr, which only works on numeric strings
incr page:views:42 1

# Redis: atomic, and the value is a real integer type
INCR page:views:42
```

Both have an atomic increment, fine. But now say you want the ten most-viewed pages. In Redis that's a sorted set and one command:

```bash
ZINCRBY page:ranking 1 "article-42"
ZREVRANGE page:ranking 0 9 WITHSCORES
```

In Memcached you fetch the blob, deserialize it, mutate it, reserialize it, and write it back, and you pray no other process did the same thing between your read and your write. The Redis version is atomic and does the sorting in C on the server. This is the pattern that repeats across the whole feature set: Redis lets you keep the logic next to the data, Memcached makes you drag the data to your logic.

Where this bit me: an early "recently viewed products" feature I built on Memcached kept losing entries under load. It was a classic read-modify-write race. Moving it to a Redis list with `LPUSH` and `LTRIM` made the race disappear because the trim happens on the server in one shot.

```bash
LPUSH user:7:recent "sku-991"
LTRIM user:7:recent 0 19   # keep only the last 20
```

## Persistence versus pure memory

Memcached is memory and only memory. Restart the process and everything is gone. For a cache that is arguably the correct behavior, a cold cache just repopulates from the source of truth.

Redis can persist. It offers two mechanisms, and people mix them up constantly:

- **RDB** takes point-in-time snapshots of the dataset to disk on an interval. Compact, fast to load, but you lose whatever changed since the last snapshot if the process dies.
- **AOF** (append-only file) logs every write command. Durable down to roughly the last second with the default `everysec` fsync policy, at the cost of a larger file and slower restarts.

You can run both together, which is the common production setup: AOF for durability, RDB for fast restarts.

```
# redis.conf
appendonly yes
appendfsync everysec
save 900 1
save 300 10
```

The trap is expecting cache-grade behavior from a persistence-configured Redis. Enable AOF with `appendfsync always` and every write waits on a disk sync, so your "fast in-memory cache" now moves at disk speed. For pure caching, many teams deliberately turn persistence *off* and treat Redis as ephemeral, which makes the durability difference with Memcached evaporate. Persistence is a capability, not an obligation.

## Single-threaded Redis vs multi-threaded Memcached

This one gets stated as a Redis weakness and it usually isn't.

Redis executes commands on a single thread. Memcached is multi-threaded and will use every core you give it. Sounds like Memcached wins on a big box. In practice, Redis's single thread is rarely the bottleneck: most commands are O(1) or O(log n) and finish in microseconds, and modern Redis offloads I/O to helper threads anyway. That single command thread is also *why* operations like `INCR` and `ZADD` are atomic with no locking overhead — there is no concurrent command to race against.

The single thread does have two real consequences worth remembering:

- **One slow command blocks everything.** Run `KEYS *` on a large database, or `SMEMBERS` on a set with a million entries, and every other client waits behind it. Use `SCAN` instead of `KEYS`, and be wary of any command that touches a whole large structure at once.
- **A single Redis process won't saturate a many-core machine.** If you have raw throughput needs beyond what one core delivers, you scale Redis by running more instances (or clustering), whereas one Memcached instance scales up with the cores.

For the vast majority of workloads, none of this is the deciding factor. If you genuinely need to push millions of small reads per second per node with uniform data, Memcached's threading model is a real advantage. Most apps never get there.

## Eviction policies

When memory fills up, both have to decide what to drop. Memcached uses an LRU (least recently used) scheme, evicting the coldest items within its slab classes. It's simple and you don't configure much.

Redis gives you a menu via `maxmemory-policy`, and the choice actually matters:

```
# redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru
```

- `noeviction` — refuse writes when full and return errors. This is the *default*, and it surprises people who assumed Redis behaves like a cache out of the box. If you use Redis as a cache, you almost certainly do not want this.
- `allkeys-lru` — evict the least recently used key, cache-like behavior.
- `allkeys-lfu` — evict the least *frequently* used, often better when access frequency matters more than recency.
- `volatile-*` variants — only evict keys that have a TTL set, leaving keys without an expiry untouched.

The `volatile-lru` policy is a nice tool when you use one Redis instance for both durable-ish data and cache: set TTLs on the cache keys, leave the important ones without a TTL, and only the cache keys get evicted. Memcached has no equivalent because everything in it is a disposable cache entry by definition.

## Memory efficiency

Memcached's slab allocator is genuinely clever for its intended job. It carves memory into fixed-size chunks (slab classes) and drops each value into the smallest chunk that fits. For a workload of uniform small values, this nearly eliminates fragmentation and the per-item overhead is tiny. If you're caching millions of similar 200-byte objects, Memcached often fits more of them in the same RAM.

The flip side is the same slab design: a value slightly larger than a slab boundary rounds up to the next class and wastes the difference, and once a slab class owns memory it doesn't easily hand it back for values of a different size.

Redis uses jemalloc and spends more bytes per key on metadata, but it does its own small-value optimizations — small hashes, lists, and sorted sets use a compact encoding until they cross a configured size threshold, then convert to the full structure. So a hash with a handful of fields is cheaper than you'd guess. Rule of thumb: for huge volumes of uniform tiny values, Memcached is leaner; for varied structured data, Redis's overhead buys you the structures.

## Clustering and scaling out

Memcached scaling is client-side. The servers don't know about each other; your client hashes each key and picks a node, ideally with consistent hashing so adding or removing a node only reshuffles a fraction of the keys. Dead simple, no coordination — but no replication either, so lose a node and you lose that slice of the cache. For a cache, often acceptable.

Redis has a real clustering mode. Redis Cluster shards the keyspace across nodes using 16384 hash slots, supports replicas for failover, and redirects the client to the right node automatically. Redis Sentinel covers high-availability for a non-sharded setup with automatic master failover. More moving parts, but you get replication and failover that Memcached doesn't offer.

If losing part of your cache on a node failure is fine, Memcached's client-side sharding is less to run. If the cache holds anything you'd rather not lose when a node dies — sessions, rate-limit counters, queue state — Redis's replication earns its keep.

## The stuff only Redis does

This is where "it's just a cache" stops being true. Redis is regularly used as:

- **A queue** — `LPUSH`/`BRPOP` gives you a blocking work queue; Laravel's queue system uses exactly this.
- **Rate limiting** — atomic `INCR` with an `EXPIRE`, or a sorted set for a sliding window.
- **Distributed locks** — `SET key value NX PX 30000` sets a key only if absent, with a timeout, which is the core of a lock.
- **Pub/sub** — `PUBLISH`/`SUBSCRIBE` for pushing events between processes, which powers Laravel broadcasting.
- **Sessions** — durable enough to survive a deploy, shared across web nodes.

Memcached does none of these. Every one of them would be a hand-rolled, race-prone approximation on top of a plain key/value store. When people say "we ended up needing Redis anyway," this list is why.

## The Laravel angle

Laravel treats both as first-class cache drivers, so at the plain-cache level the code is identical — you change `CACHE_STORE` and move on:

```php
// Works the same regardless of driver
Cache::put('dashboard:stats', $stats, now()->addMinutes(10));
$stats = Cache::remember('dashboard:stats', 600, fn () => Stats::compute());
```

```ini
# .env — Memcached
CACHE_STORE=memcached
MEMCACHED_HOST=127.0.0.1

# .env — Redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_CLIENT=phpredis
```

The divergence shows up the moment you look past `Cache`. Laravel's **queues**, **broadcasting**, **rate limiter**, and Redis-backed **atomic locks** all require Redis. If you set `QUEUE_CONNECTION=redis` you're depending on Redis lists. `Cache::lock()` on the Redis store gives you a real distributed lock; on Memcached the lock support is thinner and leans on `add`. So a Laravel app that uses queues — which is most non-trivial Laravel apps — already has a Redis dependency, and at that point running Memcached *as well* just for the cache is two dependencies doing one job's worth of work.

One config detail people miss: install the `phpredis` PHP extension rather than relying on the pure-PHP `predis/predis` package for production. `phpredis` is a compiled C extension and noticeably faster under load. Set `REDIS_CLIENT=phpredis` and confirm `php -m | grep redis` shows it installed.

For sessions the same logic applies. `SESSION_DRIVER=redis` gives you shared sessions across web servers that survive a Redis restart if persistence is on. Memcached sessions work too but vanish on restart, which logs everyone out — usually not what you want.

## A decision guide

Run down this list and stop at the first line that fits:

- **You need queues, pub/sub, rate limiting, locks, or leaderboards** → Redis. It's not close.
- **You need the cache to survive restarts / node failure** → Redis, with persistence or replication.
- **You're on Laravel using queues or broadcasting already** → Redis. You have the dependency anyway.
- **You only cache, and want the leanest possible RAM use for millions of uniform small values** → Memcached has a real edge here.
- **You already run a healthy Memcached cluster and only do plain caching** → keep it; don't migrate for its own sake.
- **You genuinely need to saturate many cores with raw throughput on one node** → Memcached's threading helps.
- **None of the above / greenfield project** → Redis. It's the safer default because it grows with you.

Redis is the "grows with you" default; Memcached is the specialist that shines when your needs are narrow and stay narrow.

## FAQ

### Is Redis faster than Memcached?
For typical workloads they're close enough that the network and serialization dominate, and micro-benchmarks flip depending on payload size and command mix. Memcached's multi-threading can win on raw uniform-small-value throughput per node; Redis wins on anything involving server-side data structures. "Faster" is the wrong axis for the decision — capability is.

### Can I run both in the same app?
You can, and some large systems do — Memcached for a huge volume of simple cached blobs, Redis for queues and structured data. But it's two systems to monitor, patch, and reason about. Unless you have a measured reason, one store is less operational surface.

### Does Redis lose data if it crashes?
Depends on config. With persistence off, yes, like any cache. With AOF and `appendfsync everysec` you lose at most about a second of writes. With RDB only, you lose everything since the last snapshot. Match the setting to whether the data is disposable cache or something you'd miss.

### Why does my Redis return errors instead of evicting when full?
Because the default `maxmemory-policy` is `noeviction`. Set `maxmemory` and an eviction policy like `allkeys-lru` if you're using it as a cache. This catches almost everyone once.

## Where this lands

Pick Memcached when your needs are narrow — a big, fast, disposable key/value cache of uniform small values — and you want the least to operate. Pick Redis for basically everything else, and especially for anything that will one day want a queue, a lock, or a counter that has to be right under concurrency. If you're starting fresh on Laravel, save yourself the future migration and start with Redis. Then go set `maxmemory-policy` before you forget, because the default will bite you.
