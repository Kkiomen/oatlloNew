---
title: "Cache stampede"
slug: cache-stampede
seo_title: "Cache Stampede in Laravel: Fix the Thundering Herd"
seo_description: "A hot cache key expires and every request rebuilds it at once, hammering the DB. Fix cache stampede with Cache::lock, jittered TTLs, and stale-while-revalidate."
---

Here is a failure that only shows up under load, which is the worst time to discover it.
You cache an expensive value with a TTL. It serves thousands of requests per second happily.
Then the TTL expires - and everything falls over for a moment. This is a **cache stampede**,
also called the **thundering herd**.

## What actually happens

Imagine a homepage query that takes 800ms and is cached for 10 minutes. While the key exists,
every request gets an instant hit. Now the key expires.

```text
10:00:00  key expires
10:00:00  request A: MISS -> starts the 800ms query
10:00:00  request B: MISS -> starts the 800ms query
10:00:00  request C: MISS -> starts the 800ms query
   ...    (200 more requests arrive in that 800ms window)
10:00:00  ALL of them run the same expensive query at once
```

The cache was doing its job: hiding an expensive query behind one cheap key. The instant that
key vanished, every in-flight request missed *at the same time*, and they all decided to
rebuild it themselves. Two hundred copies of an 800ms query hit the database simultaneously.
The database, which was fine serving zero of these a second ago, now gets a spike it was never
sized for. It slows down, which makes the query take longer, which means more requests pile in
before the first one finishes. That is the stampede.

The nasty part: the more popular the key, the worse the stampede. A hot key is exactly the one
that will hurt most when it expires, because the most traffic is waiting on it.

## Fix 1: let only one request rebuild (Cache::lock)

The core idea: when the key is missing, **only one** request should recompute it. The rest
should wait for that one to finish, then read the fresh value. Laravel gives you a Redis-backed
lock for exactly this.

```php
function homepageData()
{
    // Fast path: if it is cached, return it. No lock needed.
    if ($data = Cache::get('homepage')) {
        return $data;
    }

    // Miss. Try to grab the lock. Only ONE request wins it.
    $lock = Cache::lock('homepage:rebuild', 10);

    if ($lock->get()) {
        try {
            // We are the chosen one. Build it and cache it.
            $data = expensiveHomepageQuery();
            Cache::put('homepage', $data, 600);
            return $data;
        } finally {
            $lock->release();
        }
    }

    // We did NOT get the lock - someone else is rebuilding.
    // Wait briefly for them to finish, then read their fresh value.
    $lock->block(5);
    return Cache::get('homepage');
}
```

`Cache::lock('homepage:rebuild', 10)` asks Redis for a lock that auto-expires after 10 seconds
(so a crashed request cannot hold it forever). `get()` returns `true` for the one request that
wins and `false` for everyone else. The winner runs the query once; the losers `block()` -
wait - until the lock frees, then read the value the winner just stored. One expensive query
instead of two hundred.

`block($seconds)` waits up to that long for the lock to be released, which by then means the
value is cached. Always `release()` in a `finally` so the lock frees even if the query throws.

The detail that trips people up is the two timeouts, and they are not decoration. The lock's
auto-expiry (10 seconds here) has to be **longer than the query can ever take**, or the lock
frees while the winner is still working - a second request grabs it and you are rebuilding
twice, quietly, exactly the thing you added the lock to prevent. And `block(5)` has to be long
enough to outlast the winner too, because a loser whose wait runs out wakes to a cache that is
*still empty* and reads back `null`. Size both against the real worst-case query time, not the
happy path, and decide what a loser returns if the wait genuinely expires. A lock does not
remove the failure mode; it moves it to your timeout numbers.

## Fix 2: stagger the TTLs (jitter)

Fix 1 handles one hot key. But stampedes also happen when *many* keys expire together. If you
warm 500 keys in a deploy loop, they all get the same 600-second TTL and therefore all expire
in the same second - 500 simultaneous rebuilds.

The fix is cheap: add a little randomness to each TTL so they expire at spread-out times.

```php
// Instead of a fixed 600s, use 600 plus up to 60s of random jitter.
$ttl = 600 + random_int(0, 60);
Cache::put($key, $value, $ttl);
```

Now those 500 keys expire spread across a 60-second window instead of all at once. No single
instant sees the whole herd. This costs nothing and prevents the *synchronized* stampede that
a fixed TTL quietly sets up.

## Fix 3: serve stale while you revalidate

The third idea attacks the root cause: the gap where the key is *gone* and everyone must wait
for a rebuild. What if the value never fully disappeared?

**Stale-while-revalidate** means: when the value is getting old, serve the slightly stale copy
*immediately* to the reader, and rebuild the fresh copy in the background. Nobody waits on the
800ms query - they get the old value now, and the next requests get the new one.

You can approximate this by hand: store the value with a long TTL, but keep your own "fresh
until" timestamp inside it. When a reader sees the value is past its freshness time, it returns
the stale value right away and kicks off a single background refresh (guarded by a lock, like
Fix 1, so only one refresh runs). The reader never blocks on the rebuild.

The trade-off is honesty: you are deliberately serving data that is a little out of date to
keep the site fast and the database calm. For a homepage list that is usually fine; for a bank
balance it is not. Choose it where slightly-stale beats slow.

## Which fix do I use?

- A single very hot key that is expensive to build - **Cache::lock**.
- Many keys warmed together (deploys, batch jobs) - **jittered TTLs**.
- A hot key where users must never wait for the rebuild - **stale-while-revalidate**.

They stack. A big system often jitters its TTLs *and* locks its hottest keys. The goal is
always the same: never let one expiry turn into a herd of identical database queries.

## Common mistake

Wrapping a `Cache::remember` in nothing and assuming it is safe under load. `remember` does
not coordinate concurrent misses - if the key is cold and 200 requests arrive together, all
200 run the closure. It is perfectly fine for ordinary keys; it needs a lock only for keys hot
and expensive enough that a simultaneous rebuild would hurt the database.

## FAQ

### Does Cache::remember prevent a stampede on its own?

No. `remember` just does get-or-compute. If many requests miss at the exact same moment, they
all compute. For a cheap query that does not matter. For an expensive, very hot key, add a
`Cache::lock` so only one request rebuilds.

### What does block() do on a lock?

`$lock->block(5)` waits up to 5 seconds for the lock to become available, instead of giving up
immediately. In this pattern the losing requests block until the winner finishes rebuilding,
then read the freshly cached value - so they wait once, not run the query themselves.

### Why add randomness to a TTL?

To stop keys that were created together from expiring together. A fixed TTL means a batch of
keys all die in the same second and rebuild at once. A few seconds of random jitter spreads
those expiries out so no single moment gets the whole herd.
