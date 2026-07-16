---
title: "The cache-aside pattern"
slug: the-cache-aside-pattern
seo_title: "Cache-Aside Pattern in Laravel: Lazy Loading Explained"
seo_description: "The cache-aside pattern in Laravel: check the cache, compute on a miss, store, return - and why Cache::remember is this pattern in one line."
---

Almost every cache you write follows one pattern. It has a name - **cache-aside**, also
called **lazy loading** - and once you see it you will spot it everywhere in Laravel, from a
hand-written `Cache::get` to the `remember` closures scattered through your controllers.

## The three steps of cache-aside

Cache-aside means your application code sits beside the cache and manages it directly. On
every read you do the same dance:

1. **Check the cache** for the key.
2. **On a hit** (the value is there), return it. Done.
3. **On a miss** (the value is not there), do the expensive work, **store** the result in
   the cache, then return it.

The name "lazy loading" says it well: the cache is filled **lazily**, only when someone
actually asks for the data. Nothing is cached until the first request needs it. The first
visitor pays the cost and warms the cache for everyone after.

```text
read(key):
    value = cache.get(key)
    if value exists:        -> return value          (hit, fast)
    value = do_expensive_work()
    cache.put(key, value)
    return value                                      (miss, slow once)
```

## You already wrote this: Cache::remember

In [caching in Laravel](/course/redis-basics/redis-and-laravel/caching-in-laravel) you met
`Cache::remember`. That method **is** cache-aside, wrapped in one line:

```php
$articles = Cache::remember('articles.latest', 600, function () {
    return Article::latest()->take(10)->get();
});
```

Read it against the three steps. `remember` checks the cache for `articles.latest`. If the
value is there, it returns it and the closure never runs. If it is missing, it runs the
closure (the expensive query), stores the result for 600 seconds, and returns it. Check,
miss, compute, store, return - the whole pattern in a single call.

That is why `remember` is the workhorse of Laravel caching. You almost never write the
if-check-store logic by hand; you hand the closure to `remember` and let it run the
pattern for you.

One thing the one-liner hides is worth knowing: whatever the closure returns gets
**serialized** into the store and unserialized on every hit. Return a lightweight array and
that is cheap. Return a full Eloquent model with three eager-loaded relations and you have
quietly stored a large blob that has to be rebuilt on every read - and if the model's shape
changes in a deploy, the old serialized copies can come back wrong until they expire. Cache
the data you actually need, not the fattest object you happen to have in hand.

## Read-through and write-through, the two cousins

Cache-aside puts your app in charge: you read the cache, and you decide what to do on a
miss. Two cousins move that logic elsewhere. In **read-through**, the cache itself knows
how to load a missing value from the source, so your app only ever talks to the cache and
the cache fills its own gaps. In **write-through**, every time you write data you write it
to the cache **and** the source together, so the cache is updated the moment the data
changes instead of on the next read. Laravel's `Cache` facade gives you cache-aside out of
the box; read-through and write-through are patterns you build on top when you need them,
and we come back to write-through in the
[invalidation strategies](/course/redis-basics/caching-patterns-and-invalidation/invalidation-strategies)
lesson.

## Common mistake

Forgetting the store step. People check the cache, miss, do the work, return the value -
and never write it back. The cache stays empty, every request is a miss, and the "cache"
does nothing but add a wasted lookup. If you are writing the pattern by hand, the
`cache.put` is not optional. This is exactly the slip `Cache::remember` saves you from:
storing on a miss is built in, so you cannot forget it.

## FAQ

### Why is it called "lazy" loading?

Because the data is only loaded into the cache when it is first needed, not ahead of time.
The opposite approach - filling the cache in advance, before anyone asks - is called
**cache warming**, and it is useful when you do not want the first visitor to pay the cost.

### What happens to the first request after the cache expires?

It misses, so it runs the expensive work again and re-stores the value. That single slow
request is the normal cost of cache-aside. When many requests hit that expired key at once,
it can become a problem called a stampede - a
[later lesson](/course/redis-basics/caching-patterns-and-invalidation/cache-stampede) is
all about it.

### Does cache-aside work with any cache store?

Yes. The pattern is about your code's logic, not about Redis specifically. It works the
same with Redis, Memcached, or Laravel's file and array drivers. Redis is just a fast,
shared place to keep the values.
