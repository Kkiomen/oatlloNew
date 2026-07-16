---
title: "Redis as the cache driver"
slug: redis-as-cache-driver
seo_title: "Redis as Laravel Cache Store: set CACHE_STORE=redis"
seo_description: "Make Redis your Laravel 11 cache store with one line, CACHE_STORE=redis. How the Redis cache driver differs from file and array, and why it uses its own database."
---

## Pointing the Laravel cache at Redis

Laravel's cache system works the same no matter where the data lives - files, the database, memory, or Redis. For a real app, Redis is the best fit: it is fast, shared across every server, and it can expire keys on its own (the TTL you learned back in [expiration](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl)). Making Redis your Laravel cache store takes one line.

## Set CACHE_STORE to redis

In Laravel 11 the cache store is chosen by `CACHE_STORE` in `.env`:

```ini
CACHE_STORE=redis
```

That is the whole change. Every `Cache::` call in your app now reads and writes Redis. You will use those calls in the next lesson.

> Note: if you are used to older Laravel, this key was called `CACHE_DRIVER`. Laravel 11 renamed it to `CACHE_STORE`. Use the new name.

The store is defined in `config/cache.php`, which already has a `redis` entry:

```php
'redis' => [
    'driver' => 'redis',
    'connection' => env('CACHE_STORE_CONNECTION', 'cache'),
    'lock_connection' => env('CACHE_STORE_LOCK_CONNECTION', 'default'),
],
```

## Redis cache driver vs file and array

A fresh install often defaults to `file`, and tests use `array`. It helps to know what changes when you move to Redis.

- **file** writes each cached value to a file under `storage/framework/cache`. It works with zero setup, but it is slow, it does not clean up expired entries eagerly, and it is not shared between servers. Fine for local tinkering, weak for production.
- **array** keeps the cache in PHP memory for the length of a single request, then it is gone. Nothing persists. It exists so tests can run without touching any real store.
- **redis** keeps everything in a fast in-memory server that every web server, queue worker, and console command can reach. Expiry is handled by Redis itself. This is what you want in production.

Same `Cache::` API in your code either way. Only the storage behind it changes. One nicety you get for free: the cache store serializes values before writing them, so you can cache an array, an Eloquent model, or a whole collection and get the same shape back. That is the raw `Redis` facade's missing serialization, handled for you.

## The cache uses its own Redis connection

Look again at that config: the redis cache store uses the `cache` connection, not `default`. Back in the connecting lesson you saw the two connections point at different Redis databases (`0` for `default`, `1` for `cache`).

This separation is on purpose and it matters. When you run:

```bash
php artisan cache:clear
```

Laravel flushes the cache store. Because the cache lives on its own database, that flush wipes cached values only. Anything you stored yourself through the `Redis` facade on the `default` connection is untouched. If both shared one database, clearing the cache would blow away your own data too.

## Verify it works

Quick check in Tinker:

```bash
php artisan tinker
```

```php
Cache::put('greeting', 'hello', 60);
Cache::get('greeting');
// "hello"
```

If you get `"hello"` back, the Redis cache store is live. You can even open `redis-cli`, select database 1 with `SELECT 1`, and see the key sitting there.

## Common mistake

Setting `CACHE_STORE=redis` but leaving a stale compiled config. If you cached your config earlier with `php artisan config:cache`, the old value sticks and the change seems to do nothing. Run `php artisan config:clear` (or re-run `config:cache`) after editing `.env` so Laravel actually reads the new store.

## FAQ

### Is it CACHE_STORE or CACHE_DRIVER in Laravel 11?

`CACHE_STORE`. Laravel 11 renamed the old `CACHE_DRIVER` key. Set `CACHE_STORE=redis`.

### Why does the cache use a different Redis database than my own keys?

So `cache:clear` only wipes cached values. The cache connection sits on database `1`; your facade calls default to database `0`. They never overwrite each other.

### Do I need to change my code to switch to Redis?

No. The `Cache::` methods are identical across stores. You change `CACHE_STORE` and nothing else.
