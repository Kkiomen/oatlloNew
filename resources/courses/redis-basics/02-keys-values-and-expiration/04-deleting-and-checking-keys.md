---
title: "Deleting and checking keys"
slug: deleting-and-checking-keys
seo_title: "Delete Redis Keys with DEL, EXISTS, RENAME and SCAN"
seo_description: "Delete Redis keys with DEL, check them with EXISTS, rename with RENAME, and learn why KEYS can freeze a production server - use SCAN instead."
---

You know how to create keys and let them expire on their own. Sometimes, though, you need
to act on keys directly, and the command to delete Redis keys on demand is `DEL`. This
lesson covers the everyday key-management commands - checking, renaming, deleting - plus
one command you should treat with real caution on a busy server.

## Deleting Redis keys with DEL

`DEL` removes one or more keys immediately:

```bash
SET temp "bye"
DEL temp
GET temp
```

```text
OK
(integer) 1
(nil)
```

The `(integer) 1` is the number of keys actually deleted. Delete several at once by
listing them:

```bash
DEL user:42:name user:42:email
```

```text
(integer) 2
```

If a key does not exist, `DEL` simply does not count it - deleting a missing key is not an
error, it just returns a lower number. That return value is more useful than it looks: it
is the count of keys that were actually there, so `DEL session:abc` returning `1` versus
`0` tells you in one round trip whether the key existed before you removed it.

## Checking existence with EXISTS

`EXISTS` tells you whether a key is present without fetching its value:

```bash
SET greeting "hi"
EXISTS greeting
EXISTS missing
```

```text
OK
(integer) 1
(integer) 0
```

`1` means the key exists, `0` means it does not. This is the classic cache check: "is this
already cached?" In practice you often just `GET` and test for `(nil)`, but `EXISTS` is
handy when you only care about presence, not the value.

## Renaming a key with RENAME

`RENAME` changes a key's name, carrying its value along:

```bash
SET draft "content"
RENAME draft published
GET published
```

```text
OK
OK
"content"
```

Two things to know. If the target name already exists, `RENAME` overwrites it without
asking; to rename only when the target is free, use `RENAMENX`, which renames **only if**
the new name does not already exist. And `RENAME` expects the source key to exist - point
it at a missing key and you get an error, `ERR no such key`, rather than a quiet `0` like
`DEL` gives you.

## Finding keys: why KEYS is dangerous and SCAN is safe

Sooner or later you will want to *find* keys - say, every key starting with `user:42:`.
Redis has a command that looks perfect for this, `KEYS`, and a safer one, `SCAN`.

`KEYS` takes a pattern and returns every matching key:

```bash
KEYS user:42:*
```

```text
1) "user:42:name"
2) "user:42:email"
```

On your laptop with a handful of keys, this is fine. **On a production server, `KEYS` is
dangerous.** Redis handles one command at a time, and `KEYS` scans the *entire* keyspace
before it replies. With millions of keys, that single command can freeze Redis for
seconds - blocking every other request from every app connected to it. It is one of the
classic ways to take down a live Redis.

The safe alternative is `SCAN`, which walks the keyspace in small batches instead of all
at once:

```bash
SCAN 0 MATCH user:42:* COUNT 100
```

```text
1) "0"
2) 1) "user:42:name"
   2) "user:42:email"
```

`SCAN` hands back a **cursor** (the `0` on the first line) plus a chunk of results. You
feed the cursor into the next `SCAN` call, and keep going until the cursor comes back as
`0`, meaning you have seen everything. Because each step is small, Redis stays responsive
for everyone else the whole time.

Don't worry about mastering the cursor loop yet - we cover `SCAN` properly in a later
chapter on managing Redis from the console. The lesson to carry with you now is simple:
**reach for `SCAN`, not `KEYS`, on anything that isn't a throwaway local instance.**

## Common mistake: running KEYS on production

The number-one production incident for Redis beginners is running `KEYS *` on a live
server "just to see what's in there". On a big instance that one command can stall the
whole server while it builds a list of every key. Build the habit early: on any Redis that
real users depend on, treat `KEYS` as off-limits and use `SCAN`.

## FAQ

### Is there a difference between DEL and expiration?

`DEL` removes a key right now, on demand. Expiration (from the [previous
lesson](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl)) removes it
automatically after a timer. You use `DEL` when you want a key gone this instant - for
example, to invalidate a cache entry after the underlying data changed.

### What does UNLINK do?

`UNLINK` is like `DEL`, but it removes the key from the keyspace immediately and frees the
memory in the background. For large values it can be gentler on a busy server. `DEL` is
perfectly fine for the small keys in this course.

### Can EXISTS check several keys at once?

Yes. `EXISTS a b c` returns how many of those keys exist, counting duplicates. So
`EXISTS a a` returns `2` if `a` exists. Usually you pass a single key and read it as a
plain yes/no.
