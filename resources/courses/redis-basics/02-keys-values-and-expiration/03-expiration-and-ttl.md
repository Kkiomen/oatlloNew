---
title: "Expiration and TTL"
slug: expiration-and-ttl
seo_title: "Redis Key Expiration and TTL: EXPIRE, SETEX, PERSIST"
seo_description: "Set Redis key expiration so keys delete themselves with EXPIRE, SETEX and SET EX. Read a key's TTL and remove expiry with PERSIST - the basis of caching."
---

Here is the feature that turns Redis from a dictionary into a **cache**: Redis key
expiration lets a key delete itself after a set number of seconds. You store something,
put a timer on it, and Redis quietly removes it when the timer runs out. No cleanup job,
no cron, no code.

## Why self-deleting keys matter for caching

Cached data goes stale. A product price, a rendered page, a currency rate - you store it
to avoid recomputing it, but you don't want to serve a week-old copy forever. The classic
answer is: "keep this for 60 seconds, then throw it away and fetch fresh data."

That timer is called the **TTL** - time to live. It is the beating heart of almost every
caching strategy, and the rest of this course leans on it heavily.

## Setting an expiry with EXPIRE

Store a key the normal way, then attach a TTL in seconds with `EXPIRE`:

```bash
SET session:abc "logged-in"
EXPIRE session:abc 60
```

```text
OK
(integer) 1
```

The `(integer) 1` means "yes, the expiry was set". From now on, this key will vanish 60
seconds after you set the timer. One catch worth knowing early: `EXPIRE` only works on a
key that already exists. Run it against a missing key and you get `(integer) 0` and no
timer is created - there is nothing to attach the clock to.

## Reading a key's TTL

Ask how much time a key has left with `TTL`:

```bash
TTL session:abc
```

```text
(integer) 55
```

Fifty-five seconds left. Run it again a moment later and the number will be lower. Two
special replies are worth knowing:

```text
(integer) -2   the key does not exist (gone, or never existed)
(integer) -1   the key exists but has no expiry (it lives forever)
```

So `-1` means "permanent" and `-2` means "already gone". Beginners often confuse these
two - remember `-2` is more negative because the key is more gone.

## Setting value and expiry together with SETEX

Doing `SET` then `EXPIRE` as two commands has a subtle risk: if something interrupts you
between them, the key could end up with no timer and live forever. Redis gives you two
ways to set the value and the TTL together, safely.

`SETEX` takes the seconds in the middle:

```bash
SETEX page:home 30 "<h1>Home</h1>"
TTL page:home
```

```text
OK
(integer) 30
```

The modern, more flexible form is `SET` with the `EX` option:

```bash
SET page:home "<h1>Home</h1>" EX 30
```

```text
OK
```

Both store the value **and** the 30-second TTL in a single atomic command. Prefer these
over a separate `EXPIRE` whenever you know the lifetime up front - which, for a cache, is
almost always.

## Removing an expiry with PERSIST

Changed your mind? `PERSIST` strips the timer off a key and makes it permanent again:

```bash
SET temp "keep me" EX 100
PERSIST temp
TTL temp
```

```text
OK
(integer) 1
(integer) -1
```

The `TTL` is now `-1`: the key exists and will no longer expire.

## Why overwriting a key clears its TTL

This one bites people. If a key has a TTL and you overwrite it with a plain `SET`, the
timer is **wiped** and the key becomes permanent:

```bash
SET item "v1" EX 60
SET item "v2"
TTL item
```

```text
OK
OK
(integer) -1
```

The second `SET` replaced the value and threw away the expiry. If you want the new value
to keep expiring, set the TTL again in the same command: `SET item "v2" EX 60`. (Commands
like `INCR`, which you will meet in a later lesson, do *not* clear the TTL - only a full
`SET` does.)

## Common mistake: forgetting the TTL

The most common expiry bug is forgetting the TTL entirely. You add a cache, ship it, and
weeks later Redis is full of keys that never die because every write was a plain `SET`.
Memory creeps up until Redis starts evicting or refusing writes. Rule of thumb: **a cache
key should almost always be born with a TTL.** Use `SET ... EX` or `SETEX` so the timer is
never an afterthought.

## FAQ

### Does the TTL count down only while Redis is running?

TTL is based on wall-clock time, not uptime. If a key has 60 seconds left and Redis is
restarted, then comes back two minutes later, the key is already expired and gone. The
clock does not pause.

### Can I set a TTL in milliseconds?

Yes. Use `PEXPIRE` for milliseconds, `PTTL` to read the remaining milliseconds, and
`SET ... PX 500` to set the value with a 500-millisecond TTL. Seconds cover most caching
needs, but the millisecond versions exist when you need finer control.

### What exactly happens the moment a key expires?

It is treated as deleted. `GET` returns `(nil)`, `EXISTS` returns `0`, and `TTL` returns
`-2`. Redis removes expired keys both lazily (when you touch them) and in the background,
so you never have to clean them up yourself.
