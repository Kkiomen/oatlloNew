---
title: "Common caching mistakes"
slug: common-caching-mistakes
seo_title: "Common Caching Mistakes in Laravel and How to Avoid Them"
seo_description: "Five caching mistakes that bite in production: no TTL, per-user data under a shared key, over-caching, cached errors, and unbounded key growth."
---

Every common caching mistake in this lesson has the same tell: it works perfectly on your
laptop and falls apart in production. That is not a coincidence. Development runs with one
user, fresh data, and a cold cache - the exact conditions under which none of these bugs can
appear. Production has many users hitting the same keys at once, data that changes while
copies sit around, and a cache that stays warm for hours. The bugs live in precisely the
conditions your dev environment never reproduces, which is why they slip through and why it
pays to recognize the shape of each one now, before it is a customer complaint.

## Mistake 1: caching with no TTL and never invalidating

The most common one, and we have warned about it before because it is that frequent. You cache
a value forever - no TTL - and then never clear it when the underlying data changes.

```php
// BAD: cached forever, and nothing ever clears it when the setting changes.
$settings = Cache::rememberForever('site.settings', fn () => Setting::all());
```

Change a setting in the database and the app keeps serving the old copy, because nothing expires
it. As the [invalidation lesson](/course/redis-basics/caching-patterns-and-invalidation/why-cache-invalidation-is-hard)
showed, a stale key with no TTL is stale *forever* - no amount of waiting fixes it.

```php
// BETTER: a TTL so a missed invalidation self-heals within the hour.
$settings = Cache::remember('site.settings', 3600, fn () => Setting::all());
```

If you genuinely need `rememberForever`, then you must `Cache::forget('site.settings')` on every
path that changes a setting. If you are not certain you covered them all, use a TTL so a mistake
expires instead of lasting until the next deploy.

## Mistake 2: caching per-user data under a shared key

This one is not slow or stale - it is a **data leak**, and it is the scariest bug on this list.
You cache something that is different for every user, but under a key that is the same for
everyone.

```php
// BAD: every user reads and writes the SAME key.
$profile = Cache::remember('current.profile', 300, fn () => auth()->user()->profile);
```

User A logs in, misses, and caches *their* profile under `current.profile`. User B logs in a
second later, *hits* that key - and sees user A's profile. Private data crossing between users.
It will pass every test you run alone and fail the moment two people use the app at once.

The fix is to put the thing that makes the data unique - the user id - into the key:

```php
// GOOD: the user id is part of the key, so copies never collide.
$id = auth()->id();
$profile = Cache::remember("user:{$id}:profile", 300, fn () => auth()->user()->profile);
```

The rule: **if the value depends on who is asking, the key must contain who is asking.** This is
exactly why [key naming](/course/redis-basics/keys-values-and-expiration/key-naming-conventions)
matters - the id in `user:42:profile` is not decoration, it is what keeps users apart.

## Mistake 3: caching everything indiscriminately

Caching is not free, so caching *everything* is not automatically good. Every cached value costs
memory in Redis and adds an invalidation you now have to get right. Cache a query that runs once
a day and changes constantly, and you have paid the full invalidation tax to save almost no work
- while risking a stale read.

Cache the things that are **read often, expensive to produce, and slow to change**. That is where
the [why we cache](/course/redis-basics/caching-patterns-and-invalidation/why-we-cache) math pays
off. A cheap, rarely-hit query is often better left uncached: no memory used, no key to
invalidate, no chance of serving it stale. More cache is not more speed - it is more copies to
keep honest.

## Mistake 4: caching error and empty responses

When you cache the result of something that can fail, you can accidentally cache the *failure*.

```php
// BAD: if the API is down, this caches an empty result for 10 minutes.
$rates = Cache::remember('exchange.rates', 600, function () {
    $response = Http::get('https://api.example.com/rates');
    return $response->json('rates'); // null or [] when the call failed
});
```

The API blips for one second, one request catches the failure, and now the empty result is
cached for ten minutes. Every user for the next ten minutes sees "no rates available" even
though the API recovered instantly. You cached a bad moment and froze it.

Only cache a value once you know it is good. Do not `remember` around a call that can fail;
compute first, check the result, and cache only on success:

```php
// GOOD: only cache a real result.
$rates = Cache::get('exchange.rates');

if ($rates === null) {
    $response = Http::get('https://api.example.com/rates');

    if ($response->successful() && $response->json('rates')) {
        $rates = $response->json('rates');
        Cache::put('exchange.rates', $rates, 600); // cache ONLY the good answer
    }
}
```

The same goes for "not found" lookups and empty lists - decide deliberately whether an empty
result deserves to be cached, and for how long. Often it should not be, or only for a few
seconds, so a transient failure does not stick around.

## Mistake 5: unbounded key growth

Redis keeps everything in memory, so the number of keys you create matters. It is easy to write
code that mints a brand-new key on every request, forever, and never lets go.

```php
// BAD: a new key per search query, cached forever. The set of possible
// queries is infinite, so this fills Redis until it runs out of memory.
Cache::rememberForever("search:" . $request->query('q'), fn () => runSearch($request));
```

Search terms, request URLs with query strings, per-timestamp keys - these have effectively
unlimited variety. Give each one no TTL and you have built a memory leak: keys accumulate faster
than anything removes them, and eventually Redis is full. A full Redis starts evicting keys or
rejecting writes, and now your *whole* cache misbehaves, not just this feature.

Two defenses: always give high-variety keys a **TTL** so they clean themselves up, and prefer a
**bounded key space** where you can. Caching per category (`search:php`, a handful of values) is
safe; caching per free-text query (infinite values) needs a short TTL at minimum. Ask "how many
different keys can this line ever create?" - if the answer is unbounded, it needs a TTL.

## FAQ

### What is the single most important caching habit?

Give cached values a TTL by default. It does not replace correct invalidation, but it turns your
worst-case bug from "stale forever" into "stale for a few minutes," and it stops high-variety
keys from filling Redis. Only drop the TTL when you have a specific reason and a plan to clear
the key.

### How do I know if I am over-caching?

Look at whether each cached value is read often enough and expensive enough to earn its keep. If
a key is rarely hit, changes constantly, or was cheap to compute in the first place, caching it
adds an invalidation risk and memory cost for little gain. When in doubt, do not cache it.

### Why is caching per-user data under a shared key so dangerous?

Because it leaks private data between users, silently. One user's cached value gets served to the
next, and it only happens when two people use the app close together - so it passes solo testing
and fails in production. Always put the user id (or whatever makes the value unique) into the key.
