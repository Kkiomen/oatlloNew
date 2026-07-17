---
title: "Caching in Laravel"
slug: caching-in-laravel
seo_title: "Laravel Cache::remember and the Cache Facade Methods"
seo_description: "Cache in Laravel with the Cache facade: remember, put, get, has, forget, flush, rememberForever, add, increment. The Cache::remember pattern, explained."
---

## The everyday Laravel cache API

With Redis as your cache store, you reach it through the `Cache` facade, and the method you will lean on hardest is `Cache::remember`. These methods behave the same whatever store sits behind them; on Redis they are fast and shared across your whole app.

Import it:

```php
use Illuminate\Support\Facades\Cache;
```

## Cache::remember: the one you will use most

The most common caching job is: "give me this value, and if it is not cached, compute it and cache it." That is `Cache::remember`. You pass a key, a time-to-live in seconds, and a closure that produces the value the first time.

```php
$posts = Cache::remember('posts.latest', 600, function () {
    return Post::latest()->take(10)->get();
});
```

Here is what happens. The first call runs the query, stores the result in Redis with a 600-second [TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl), and returns it. Every call for the next 10 minutes skips the database entirely and hands back the cached result. Once the TTL expires, the next call runs the query again and refreshes the cache.

You will reach for `remember` far more than anything else. The theory behind it (it goes by [cache-aside](/course/redis-basics/caching-patterns-and-invalidation/the-cache-aside-pattern)) gets its own treatment in the next chapter; here you just learn the API. One detail worth internalizing now: the closure runs only on a miss, so anything expensive inside it - a heavy query, an API call - is paid once per TTL window, not once per request.

You can also use an arrow function, which reads cleanly for short queries:

```php
$posts = Cache::remember('posts.latest', 600, fn () => Post::latest()->take(10)->get());
```

## put, get, has, forget

When you want to control the cache by hand, these four cover it.

```php
// Store a value for 300 seconds
Cache::put('user:42:profile', $profile, 300);

// Read it back (null if missing or expired)
$profile = Cache::get('user:42:profile');

// Read with a default when missing
$profile = Cache::get('user:42:profile', 'guest');

// Is it there?
if (Cache::has('user:42:profile')) {
    // ...
}

// Remove it
Cache::forget('user:42:profile');
```

`get` returns `null` for a missing key, so pass a default as the second argument when you need one. `forget` deletes a single entry, which is how you kick a stale value out early.

The key you pass here is not the raw key in Redis. The cache store adds the same `REDIS_PREFIX` you met with the facade, so `user:42:profile` lands under something like `laravel_database_user:42:profile`. That is why `redis-cli` will not find it by the bare name, and why `Cache::forget` on the correct connection is the clean way to remove one entry.

## rememberForever and add

`rememberForever` is `remember` with no expiry. The value stays until you delete it yourself with `forget` (or the store evicts it under memory pressure). Use it for data that only changes when you change it.

```php
$settings = Cache::rememberForever('site.settings', fn () => Setting::all());
```

`add` stores a value only if the key is not already there, and it does this atomically. It returns `true` if it wrote and `false` if the key already existed. That makes it a simple guard against two requests doing the same work.

```php
if (Cache::add('report:daily:lock', true, 60)) {
    // We got the lock, generate the report
}
```

## increment and decrement: atomic counters

Because Redis counts atomically (you saw `INCR` in the [counters lesson](/course/redis-basics/keys-values-and-expiration/atomic-counters)), `Cache::increment` gives you a safe counter with no race between reading and writing.

```php
Cache::put('downloads', 0);
Cache::increment('downloads');       // 1
Cache::increment('downloads', 5);    // 6
Cache::decrement('downloads');       // 5
```

Two requests hitting `increment` at the same moment both count correctly. No lost updates.

## flush: clear everything

`Cache::flush()` empties the entire cache store. It is a blunt instrument, so keep it for deploys or maintenance, not for clearing one stale value. To remove a single entry use `forget`; to remove a related group use tags (next lesson).

```php
Cache::flush();
```

## Common mistake

Caching a value forever and then updating the underlying data without clearing the cache. If you `rememberForever('site.settings', ...)` and later change a setting in the database, your app keeps serving the old cached copy because nothing ever expires it. When you cache without a TTL, you own the invalidation: call `Cache::forget('site.settings')` whenever the data changes. Getting this right is exactly what the next chapter is about.

## FAQ

### What is the difference between remember and put?

`put` just writes a value you already have. `remember` checks the cache first and only runs your closure to compute the value on a miss. `remember` is the read-through pattern; `put` is a plain write.

### Does Cache::forget delete from Redis?

Yes. With the Redis store, `forget` deletes that key from Redis immediately. `flush` deletes the whole cache store.

### Is Cache::increment safe under load?

Yes. It uses Redis's atomic increment, so simultaneous requests each count correctly with no lost updates. That is why it beats reading a value, adding one, and writing it back.
