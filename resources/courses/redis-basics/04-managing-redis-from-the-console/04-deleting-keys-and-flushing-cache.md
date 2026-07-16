---
title: "How to delete keys and flush the cache in Redis"
slug: deleting-keys-and-flushing-cache
seo_title: "How to Delete All Keys in Redis (and Flush the Cache)"
seo_description: "Delete Redis keys with DEL and UNLINK, remove many by pattern, and delete all keys with FLUSHDB vs FLUSHALL. Plus the danger of flushing production."
---

## How to delete keys in Redis

You can find keys and inspect them. Removing them is the next step, and the question splits three ways: how do I delete one key, a batch that share a pattern, or delete all keys in Redis at once? Each has a right tool. The whole-cache ones are the most dangerous commands in this course, so we save them for last and handle them carefully.

## DEL: delete one or more keys

`DEL` removes keys and returns how many it actually deleted:

```text
127.0.0.1:6379> DEL user:42:name
(integer) 1
```

You can pass several at once:

```text
127.0.0.1:6379> DEL user:42:name user:42:email session:abc
(integer) 3
```

A return of `0` just means none of those keys existed. `DEL` never errors on a missing key.

## UNLINK: delete without blocking

`DEL` frees the memory immediately, before returning. For a huge value (a list with millions of items), that freeing can briefly block the server, the same single-threaded problem you saw with `KEYS`.

`UNLINK` avoids it. It removes the key from the keyspace right away, then frees the memory in the background:

```text
127.0.0.1:6379> UNLINK big:list
(integer) 1
```

From your point of view the key is gone instantly. Rule of thumb: for big values, or when in doubt on production, prefer `UNLINK`. For a handful of small keys, `DEL` is perfectly fine.

## Deleting many keys by pattern

Redis has no "delete by pattern" command, on purpose, since that would be a blocking scan. Instead you combine the safe `--scan` from the [previous lesson](/course/redis-basics/managing-redis-from-the-console/finding-keys-scan-vs-keys) with `xargs`:

```bash
redis-cli --scan --pattern 'cache:*' | xargs redis-cli DEL
```

Read it left to right: `--scan` lists every matching key one per line, and `xargs` feeds those keys as arguments to `redis-cli DEL`. Both halves use `SCAN` under the hood, so this stays safe even on a large database.

Before you delete, run the left half alone to see exactly what will go:

```bash
redis-cli --scan --pattern 'cache:*'
```

Look at the list first, then add the `| xargs redis-cli DEL`. This one habit prevents most accidents.

## Delete all keys: FLUSHDB vs FLUSHALL

Sometimes you want everything gone. Two commands do it, and the difference matters.

`FLUSHDB` empties the current database only:

```text
127.0.0.1:6379> FLUSHDB
OK
```

`FLUSHALL` empties every database on the server, all sixteen:

```text
127.0.0.1:6379> FLUSHALL
OK
```

Both accept `ASYNC`, which frees the memory in the background so the command returns instantly, the flush version of `UNLINK`:

```text
127.0.0.1:6379> FLUSHDB ASYNC
OK
```

## Common mistake

This is the one that ends careers. `FLUSHALL` and `FLUSHDB` delete data instantly, with no confirmation and no undo. Two ways people get burned:

- Running `FLUSHALL` on production, thinking it was staging. Every cache, session, and queue vanishes at once.
- Being on the wrong database. You `SELECT`ed the wrong number earlier, run `FLUSHDB` expecting to clear a test database, and wipe the live one.

Protect yourself: before any flush, check the prompt to confirm the host and the `[n]` database number, and prefer a targeted `--scan | xargs DEL` over a flush whenever you can. If you only need to remove some keys, never reach for `FLUSHALL`.

This is common enough that many production setups disarm the command entirely. Using `rename-command FLUSHALL ""` in the config, an ops team can leave `FLUSHALL` doing nothing at all, so a stray flush cannot wipe the server even if someone types it. If a flush ever comes back with "unknown command" on a real box, that is not a bug, it is a seatbelt someone fastened on purpose.

## FAQ

### How do I delete all keys in Redis?

`FLUSHDB` clears the database you are currently on; `FLUSHALL` clears every database on the server. Both are instant and cannot be undone, so confirm the host and database first.

### What is the difference between DEL and UNLINK?

`DEL` frees the memory before it returns, which can briefly block on very large values. `UNLINK` removes the key immediately and frees the memory in the background, so it never blocks.

### How do I delete keys by pattern in Redis?

There is no built-in pattern delete. Combine the safe scan with xargs: `redis-cli --scan --pattern 'x:*' | xargs redis-cli DEL`. Run the scan alone first to preview what will be removed.

### What is the difference between FLUSHDB and FLUSHALL?

`FLUSHDB` empties only the current database. `FLUSHALL` empties all sixteen databases on the server. Add `ASYNC` to free the memory in the background.
