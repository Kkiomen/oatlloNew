---
slug: laravel-cache-queries-carousel
type: carousel
language: en
title: "Caching queries"
topic: laravel
source_type: article
source: laravel-cache-queries
link: https://oatllo.com/laravel-cache-queries
publish_at: 2026-09-07 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, redis, performance, cache]
caption: |
  Caching an expensive query is a two-line change. Knowing when the copy went bad is the job.

  Model events, cache keys, tags, stampedes. Get invalidation wrong and you have
  built a very fast way to show wrong numbers.

  Full guide in bio.

  What served you stale data last?
---

## A seeder bulk-inserted rows and our cache stayed wrong all afternoon.

I was convinced Redis was broken. Model events only fire when you go through
Eloquent, and a bulk insert goes around it.

<!-- slide -->

## What silently skips your observer

```php
Product::where(...)->update([...]);
DB::table('products')->update([...]);
// saved() never fires. Cache stays wrong.
```

Mass updates, raw queries and migrations all bypass the model lifecycle. Flush
the keys by hand right after.

<!-- slide -->

## A key that ignores an input is a data leak

```php
$key = "user:{$user->id}:orders"
     . ":page:{$page}:status:{$status}";
```

Leave `$status` out and you serve pending orders to someone who asked for
shipped. That is not a caching bug.

<!-- slide -->

## Tags need a driver that can do them

```php
Cache::tags(["user:{$user->id}"])->flush();
```

`file` and `database` throw `BadMethodCallException` the moment you flush a tag.
Check `CACHE_STORE` before you design around tags.

<!-- slide -->

## When a hot key expires, everyone runs the query

```php
Cache::flexible('home:feed', [60, 120],
    fn () => Post::published()->get()
);
```

Stale-while-revalidate: keep serving slightly old data while one deferred
refresh runs, instead of a stampede onto the database.

<!-- slide role="cta" -->

## Caching hides a slow query. It does not fix it.

A missing index turns a lookup into a full table scan, and no cache helps the
first request or the moment it expires. Index first, cache the fast result.

