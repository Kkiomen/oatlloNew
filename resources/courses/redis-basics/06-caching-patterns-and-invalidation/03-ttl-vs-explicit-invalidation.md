---
title: "TTL vs explicit invalidation"
slug: ttl-vs-explicit-invalidation
seo_title: "TTL vs Explicit Cache Invalidation: When to Use Each"
seo_description: "TTL vs explicit cache invalidation in Laravel: let entries expire on a timer or clear them on change. When each is enough, and why you want both."
---

A cached value is a copy, and copies go stale. So every cache needs an answer to one
question: **how does the stale copy get replaced?** There are exactly two answers - let it
expire on a TTL, or invalidate it explicitly on change - and good caching usually leans on
both at once.

## Way one: let it expire (TTL)

The simplest approach is to give every cached value a lifespan. You already know this from
[expiration and TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl):
set a **time to live**, and Redis deletes the key when it runs out. The next read misses,
recomputes, and stores a fresh copy.

```php
// Fresh at most 10 minutes old, then recomputed on the next read.
Cache::remember('articles.latest', 600, fn () => Article::latest()->take(10)->get());
```

With TTL you never explicitly clear anything. You simply accept that the data can be up to
600 seconds stale, and Redis handles the rest. It is hands-off and hard to get wrong.

The cost is built into the deal: for up to the full TTL, readers may see old data. You
choose the number by how much staleness the data can tolerate - a topic straight from
[why we cache](/course/redis-basics/caching-patterns-and-invalidation/why-we-cache).

Worth being honest about what a TTL actually buys you, because it is easy to read "600
seconds" as "fresh within 600 seconds." It is not. The staleness you see depends on *when*
the data changed relative to the last cache write. Change it one second after the key was
stored and it stays hidden for almost the whole 600 seconds; change it one second before the
key expires and the fix shows up almost instantly. A TTL bounds the worst case, but it gives
you no control over the average - two readers can see wildly different lag from the same
number. That randomness is exactly what explicit invalidation removes.

## Way two: invalidate on change

The second approach is active: the moment the underlying data changes, you go and clear
the cached copy yourself. When a user edits an article, you delete the `articles.latest`
key in the same request. The next read misses and rebuilds from fresh data.

```php
$article->save();
Cache::forget('articles.latest'); // the cached list is now wrong - drop it
```

This is **explicit invalidation**. It gives you freshness on demand: readers see the change
almost immediately, not "sometime in the next 10 minutes." The cost is that **you** are now
responsible for knowing which keys to clear and remembering to clear them everywhere the
data can change. That responsibility is the whole reason invalidation is hard, which the
[next lesson](/course/redis-basics/caching-patterns-and-invalidation/why-cache-invalidation-is-hard)
is devoted to.

## When each is enough

Pick by how quickly the change needs to show:

- **TTL alone is enough** when a little lag is fine and you cannot easily hook into every
  change. A "trending topics" list, an external API's response, a daily summary - let them
  expire on a timer and move on.
- **Explicit invalidation is worth it** when stale data is visibly wrong or embarrassing,
  and you control the code that changes the data. A user updates their profile name and
  expects to see it immediately on the next page - a timer that shows the old name for ten
  minutes feels broken.

## TTL as a safety net - even when you invalidate

Here is the part people miss: **these two are not either-or.** The strongest setup uses
explicit invalidation for freshness **and** a TTL as a backstop.

Explicit invalidation is only as reliable as your code. Miss one place that changes the
data, ship a bug, or hit a race, and a stale key can live **forever** - there is nothing to
clean it up. A TTL guarantees that even a key you forgot to invalidate cannot outlive its
lifespan. Worst case, it is stale until the timer runs out, then it self-heals.

```php
// Invalidate on change for freshness; TTL guarantees it self-corrects within an hour.
Cache::remember('articles.latest', 3600, fn () => Article::latest()->take(10)->get());
// ...elsewhere, on edit:
Cache::forget('articles.latest');
```

Think of the TTL as the seatbelt. You drive carefully (invalidate correctly), but the belt
is there for the day something goes wrong.

## Common mistake

Caching forever with no TTL and relying purely on explicit invalidation. It works right up
until the one code path you forgot, and then a stale value sits in Redis with nothing to
ever remove it. Users see wrong data and no amount of waiting fixes it. Unless you have a
strong reason, give cached values a TTL so a mistake expires instead of lasting forever.

## FAQ

### If I invalidate on change, why bother with a TTL at all?

Because your invalidation code is not perfect. A missed hook, a bug, or a race can leave a
stale key behind, and only a TTL will ever clean it up. The TTL turns "wrong forever" into
"wrong for a few minutes."

### What TTL should I use?

Long enough to actually save work, short enough that the worst-case staleness is
acceptable. Seconds for fast-moving data, minutes to hours for slow-moving data. There is
no single number - it comes from how stale this specific value is allowed to be.

### Does forgetting a key delete it from Redis?

Yes. `Cache::forget('key')` removes that key from the store, exactly like a Redis `DEL`. The
next read for it is a miss and rebuilds the value.
