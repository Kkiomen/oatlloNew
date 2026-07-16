---
title: "Finding keys: SCAN vs KEYS"
slug: finding-keys-scan-vs-keys
seo_title: "Redis SCAN vs KEYS: How to See All Keys Safely"
seo_description: "How to list Redis keys by pattern. Why KEYS blocks production, how SCAN with MATCH and COUNT is the safe way, plus the redis-cli --scan shortcut."
---

## Two ways to see all keys in Redis

You have a live Redis and you want to know what keys are in there: all the sessions, maybe, or every key for user 42. To see all keys in Redis you reach for one of two commands, `KEYS` or `SCAN`, and choosing the wrong one can freeze your whole server. This lesson is about picking right.

## KEYS: simple but dangerous

`KEYS` takes a pattern and returns every matching key:

```text
127.0.0.1:6379> KEYS user:*
1) "user:42:name"
2) "user:42:email"
3) "user:7:name"
```

The pattern is a glob (more on that below). `KEYS *` returns everything.

It looks perfect, so why the warnings? Redis is single-threaded: it does one thing at a time. `KEYS` walks the entire keyspace in one go, and while it does, Redis can do nothing else. On your laptop with 20 keys, that is instant. On production with ten million keys, `KEYS *` can block the server for seconds, and every other request piles up behind it. Users see timeouts.

So the rule is simple: `KEYS` is fine for local play, never on production.

## SCAN: the safe way

`SCAN` does the same job without blocking. Instead of returning everything at once, it hands back a small batch plus a cursor, a bookmark telling Redis where to continue.

You start with cursor `0`:

```text
127.0.0.1:6379> SCAN 0 MATCH user:* COUNT 100
1) "17"
2) 1) "user:42:name"
   2) "user:7:name"
```

The first line, `"17"`, is the next cursor. Feed it back in to get the next batch:

```text
127.0.0.1:6379> SCAN 17 MATCH user:* COUNT 100
1) "0"
2) 1) "user:42:email"
```

When the returned cursor is `0` again, you have seen everything.

- `MATCH` filters by pattern, same glob rules as `KEYS`.
- `COUNT` is a hint for how much work to do per step (roughly how many keys to check, not how many to return). Bigger is faster but each step blocks a little longer. `100` is a sane default.

Because each step is small, other clients keep getting served in between. That is why `SCAN` is safe on any size of database.

Two things surprise people the first time. `SCAN` can hand you the same key twice across batches, so if you are collecting results, dedupe them. And it only ever iterates the database you are currently on; `SELECT`ed the wrong one and your scan comes back empty even though the keys exist next door.

## Glob patterns

Both commands use the same simple wildcards:

- `*` matches any number of characters: `user:*` matches `user:42:name`.
- `?` matches exactly one character: `user:?` matches `user:1` but not `user:42`.
- `[ab]` matches one character from the set: `user:[12]` matches `user:1` and `user:2`.

More examples:

```text
session:*          all session keys
*:session:*        anything with :session: in the middle
cart:99:*          everything for cart 99
```

Consistent [key naming](/course/redis-basics/keys-values-and-expiration/key-naming-conventions) is what makes these patterns useful.

## The redis-cli --scan shortcut

Running `SCAN` by hand and copying cursors is tedious. `redis-cli` has a flag that loops for you and prints one key per line:

```bash
redis-cli --scan --pattern 'user:*'
```

Output:

```text
user:42:name
user:7:name
user:42:email
```

This uses `SCAN` under the hood, so it is safe on production, and the plain one-key-per-line output is easy to pipe into other commands (you will use exactly that in the next lesson to delete keys in bulk).

## Common mistake

Running `KEYS *` on a production server "just to have a look." That single command can stall every other request for seconds while it scans millions of keys. Build the habit now: on local, either is fine; anywhere real, always `SCAN` or `redis-cli --scan`.

## FAQ

### How do I see all keys in Redis?

Use `redis-cli --scan` to list every key, or `redis-cli --scan --pattern 'user:*'` to filter. On a local database `KEYS *` also works, but avoid it on production.

### Why is KEYS dangerous in production?

Redis handles one command at a time. `KEYS` scans the whole keyspace in a single blocking pass, so on a large database it freezes the server and every other request waits.

### What is the difference between SCAN and KEYS?

`KEYS` returns all matches at once and blocks the server. `SCAN` returns small batches with a cursor so other requests keep flowing. Same results, but `SCAN` is safe at any scale.

### What does COUNT do in SCAN?

`COUNT` hints how many keys Redis should examine per step. It is not a limit on results, just a lever between speed and how long each step blocks. `100` is a reasonable default.
