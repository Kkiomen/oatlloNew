---
title: "Persistence: RDB and AOF"
slug: persistence-rdb-aof
seo_title: "Redis Persistence: RDB Snapshots vs AOF Explained"
seo_description: "Does Redis survive a restart? Learn how Redis persists in-memory data to disk with RDB snapshots and AOF, their trade-offs, and when a cache needs neither."
---

Redis keeps your data in memory, which is why it is so fast. That raises an obvious worry:
if the server restarts, is everything gone? Not necessarily. Redis persistence writes your
data to disk so Redis can reload it after a restart, and it comes in two flavours: RDB
snapshots and the AOF log.

## In memory, but with a copy on disk

Redis lives in RAM, but it can save a copy to disk in the background. When Redis starts up,
it reads that copy back and rebuilds everything in memory. Your keys, values, and their
[TTLs](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl) come back as
they were. There are two ways Redis does this, and you can use one, both, or neither.

## RDB: snapshots

RDB takes a point-in-time snapshot of the whole dataset and writes it to a single file,
every so often. You can see whether it is on by checking the config from the
[redis-cli console](/course/redis-basics/managing-redis-from-the-console/the-redis-cli-console):

```bash
CONFIG GET save
```

```text
1) "save"
2) "3600 1 300 100 60 10000"
```

That reads as "snapshot after 3600 seconds if at least 1 key changed, or after 300 seconds
if 100 changed", and so on.

- Good: the file is compact and restarts are fast, because Redis loads one clean snapshot.
- Bad: you can lose recent writes. If Redis crashes between snapshots, everything changed
  since the last one is gone.

## AOF: the append-only log

AOF (append-only file) takes a different approach. Instead of snapshots, it writes every
change command to a log file as it happens. On restart, Redis replays the log to rebuild
the data.

```bash
CONFIG GET appendonly
```

```text
1) "appendonly"
2) "no"
```

- Good: much more durable. With the common `everysec` setting, you lose at most about one
  second of writes on a crash.
- Bad: the file is larger and grows over time, and replaying a long log makes restarts
  slower than loading a snapshot.

## RDB vs AOF: which one to use

RDB trades a little safety for speed and small files. AOF trades size and restart speed for
durability. Many production setups turn on both: AOF for safety, RDB for fast restarts and
backups. If you must not lose data, lean on AOF.

One point that surprises people when both are on: at startup Redis loads the AOF, not the
RDB snapshot. The AOF holds every write up to the last second, so it is the more complete
record. The RDB file is then mostly there for fast backups and as a fallback, not for the
normal restart path.

## A cache often needs neither

Here is the freeing part. If your Redis is purely a
[cache](/course/redis-basics/caching-patterns-and-invalidation/why-we-cache), you may not
need persistence at all. Cached values can always be rebuilt from your database. If Redis
restarts empty, the next request just misses the cache, reads the source, and refills it.
Turning persistence off makes Redis a touch faster and simpler. Persistence matters when
Redis holds data that is not stored anywhere else, like queue jobs or a rate-limit count you
care about keeping.

## Common mistake

Assuming an empty cache after a restart is a bug. If you run cache-only Redis with
persistence off, coming back empty is expected and harmless. It is only a problem when Redis
is the sole home for that data. Match your persistence choice to what the data is worth, not
to a habit.

## FAQ

### Is Redis persistence on by default?

The official `redis:7` image ships with RDB snapshotting on and AOF off. So you get periodic
snapshots unless you change the config.

### Does saving to disk slow Redis down?

Only a little. RDB snapshots and AOF rewrites happen in a background process, so your normal
reads and writes keep flying. AOF's per-command write is small with the `everysec` setting.

### How do I force a save right now?

Run `SAVE` for a blocking snapshot or `BGSAVE` for a background one. `BGSAVE` is the usual
choice because it does not pause other clients.
