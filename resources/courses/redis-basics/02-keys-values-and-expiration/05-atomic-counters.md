---
title: "Atomic counters"
slug: atomic-counters
seo_title: "Redis Atomic Counters: INCR, DECR, INCRBY, DECRBY"
seo_description: "Build race-free Redis atomic counters with INCR, DECR, INCRBY and DECRBY. Count page views safely under load and combine counters with EXPIRE."
---

Counting looks trivial until two users do it at the same time. Page views, likes,
downloads, "attempts remaining" - each is just a number going up or down. A Redis atomic
counter solves the part that plain PHP code cannot handle on its own: doing the count
**safely** when many requests hit at once. `INCR` and its siblings are built for exactly
this.

## The read-modify-write race condition

Imagine counting page views without Redis. You read the current value, add one, write it
back:

```text
1. read views  -> 100
2. add one      -> 101
3. write views  -> 101
```

Now two requests do this at the same moment. Both read `100`, both add one, both write
`101`. Two views happened, but the counter only moved by one. One view was lost. This is a
**race condition**, and it comes from the gap between reading and writing.

## INCR: increment by one, atomically

Redis fixes this with `INCR`. It reads, adds one, and writes back as a single
**atomic** operation - nothing can slip in between the steps:

```bash
SET views:home 100
INCR views:home
```

```text
OK
(integer) 101
```

`INCR` returns the new value after adding. Because the whole thing happens in one
indivisible step, a thousand requests calling `INCR` at once produce exactly a thousand
increments. No views are lost, no matter how many people are counting at the same time.
This is the property that makes Redis counters trustworthy.

Handy side effect of that return value: you almost never need a separate `GET` after an
`INCR`. The command hands you the fresh number, so reaching for the count and bumping it
is a single round trip, not two.

## INCR creates the key at zero

You do not need to set the key first. If the key does not exist, `INCR` treats it as `0`
and then increments, so the first call returns `1`:

```bash
DEL downloads
INCR downloads
```

```text
(integer) 0
(integer) 1
```

(The `(integer) 0` is `DEL` telling us there was nothing to delete.) That "starts at zero
automatically" behaviour means you never have to write initialisation code - just start
incrementing.

## DECR, INCRBY and DECRBY

`DECR` is the mirror image: it subtracts one.

```bash
SET stock 5
DECR stock
```

```text
OK
(integer) 4
```

When you need to move by more than one, `INCRBY` and `DECRBY` take an amount:

```bash
INCRBY views:home 10
DECRBY stock 2
```

```text
(integer) 111
(integer) 2
```

All four commands are atomic in the same way as `INCR`. Counters can go negative too -
`DECR` on a key at `0` simply returns `-1`.

## Combining a counter with EXPIRE

Counters get really useful when you give them a lifetime. Remember `EXPIRE` from
[Expiration and TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl):
you can put a timer on a counter so it counts "per minute" or "per hour" and then resets
itself by expiring.

```bash
INCR requests:user:42
EXPIRE requests:user:42 60
```

```text
(integer) 1
(integer) 1
```

The first `INCR` creates the key at `1`, and `EXPIRE` gives it a 60-second life. Every
request in that minute bumps the number; after 60 seconds the key vanishes and the next
request starts fresh at `1`. That pattern - a self-expiring counter - is the whole basis
of **rate limiting**, which we will build properly in a later chapter.

## Common mistake: resetting the TTL on every request

The usual slip is setting the expiry only on the very first request and getting it wrong.
Notice that `INCR` does **not** touch the TTL - once a key has a timer, incrementing it
leaves that timer alone (unlike a plain `SET`, which wipes it). So calling `EXPIRE` on
every request would keep pushing the reset further away and the window would never close.
Set the TTL once, when the counter is born, and let `INCR` carry on without resetting it.

## FAQ

### What happens if the value is not a number?

`INCR` only works on values that look like integers. If the key holds something like
`"hello"`, Redis refuses and returns an error: `value is not an integer or out of range`.
Counters are for numbers - keep other data in separate keys.

### Can I count with decimals?

Not with `INCR`. It works on whole integers only. Redis has a separate command,
`INCRBYFLOAT`, for adding fractional amounts like `0.5`, but for view counts and limits
the plain integer commands are what you want.

### How high can a counter go?

Redis counters are 64-bit signed integers, so they run up to about 9.2 quintillion. You
are not going to overflow a page-view counter. If you somehow reach the limit, `INCR`
returns the "out of range" error rather than wrapping around silently.
