---
title: "Eviction policies"
slug: eviction-policies
seo_title: "Redis maxmemory and Eviction Policies: allkeys-lru Guide"
seo_description: "Set a Redis memory limit with maxmemory and choose a maxmemory-policy. Compare noeviction, allkeys-lru, allkeys-lfu, volatile-lru, and volatile-ttl for a cache."
---

Redis stores everything in memory, and memory is finite. So what happens when it fills up?
A Redis eviction policy decides that, and you control it with two settings: `maxmemory`, how
much memory Redis may use, and `maxmemory-policy`, what to do when it runs out.

## Set a memory limit

`maxmemory` caps how much RAM Redis will use for data. Check it from the
[redis-cli console](/course/redis-basics/managing-redis-from-the-console/the-redis-cli-console):

```bash
CONFIG GET maxmemory
```

```text
1) "maxmemory"
2) "0"
```

`0` means no limit, so Redis will use as much memory as the machine allows. On a real server
you set a limit, for example 512 megabytes:

```bash
CONFIG SET maxmemory 512mb
```

Once that limit is reached, the eviction policy decides what Redis does next.

## The maxmemory-policy: evict or error

`maxmemory-policy` picks the behaviour when memory is full. Check the current one:

```bash
CONFIG GET maxmemory-policy
```

```text
1) "maxmemory-policy"
2) "noeviction"
```

The choices split into two groups.

- `noeviction`: never delete anything. When memory is full, writes fail with an error and
  reads still work. This protects data you cannot afford to lose, but it means a full Redis
  stops accepting new keys.
- The eviction policies: when memory is full, Redis makes room by deleting some existing
  keys so the write can succeed. Which keys it removes depends on the policy.

The main eviction policies:

- `allkeys-lru`: evict the least recently used key out of all keys. LRU means "the one you
  have not touched for the longest".
- `allkeys-lfu`: evict the least frequently used key, the one accessed the fewest times.
- `volatile-lru`: same LRU idea, but only considers keys that have a
  [TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl) set. Keys
  without an expiry are never evicted.
- `volatile-ttl`: evict the key with the shortest remaining time to live first.

## For a cache, use allkeys-lru

If Redis is a pure [cache](/course/redis-basics/caching-patterns-and-invalidation/why-we-cache),
`allkeys-lru` is almost always what you want. A cache is meant to hold hot data and drop
what nobody uses, which is exactly what LRU does. When memory fills, the stale entries fall
out automatically and the values you actually read keep their spot. Because cached data can
be rebuilt from your database, losing the cold keys costs nothing.

```bash
CONFIG SET maxmemory-policy allkeys-lru
```

Reach for a `volatile-*` policy instead when your Redis mixes throwaway cache entries with
important keys, and only the cache entries carry a TTL. Then only those get evicted.

## Common mistake

Leaving `noeviction` on a cache with a memory limit. As soon as Redis fills up, every new
`SET` fails, so your app stops caching and may throw errors on write, even though the data
was safe to drop. For a cache, choose an `allkeys-*` policy so Redis quietly makes room
instead of refusing work.

## FAQ

### What does LRU actually mean?

Least Recently Used. Redis tracks roughly when each key was last touched and, when it needs
space, removes the one untouched for the longest. LFU is similar but counts how often a key
is used, not how recently. Worth knowing: Redis LRU and LFU are *approximate*. Rather than
scan every key, Redis samples a handful (five by default, tunable with `maxmemory-samples`)
and evicts the best candidate among them. That keeps eviction cheap on a huge keyspace at
the cost of occasionally passing over the true oldest key.

### Do these settings survive a restart?

`CONFIG SET` changes the running server only. To make it permanent, also set `maxmemory` and
`maxmemory-policy` in `redis.conf` or pass them as flags to the `redis:7`
[container](/course/redis-basics/getting-started/run-redis-with-docker), so they reapply on
restart.

### How do I know if keys are being evicted?

Run `INFO stats` and look at `evicted_keys`. A number that climbs means Redis is regularly
hitting `maxmemory`, which is a hint to raise the limit or review what you are storing. See
[server info and monitoring](/course/redis-basics/managing-redis-from-the-console/server-info-and-monitoring).
