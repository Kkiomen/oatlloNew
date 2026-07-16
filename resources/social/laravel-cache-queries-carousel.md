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
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: 79182e7a4bd4a1aaccd79e53a41b69f02844cd56
  notes: |
    Wrong verb on slide 4 (Tags need a driver that can do them). It says file and database throw BadMethodCallException the moment you FLUSH a tag. They do not - Illuminate/Cache/Repository::tags() throws it itself, before flush() is ever reached (verified in vendor, Laravel 11.36: tags() checks supportsTags() and throws This cache store does not support tagging). This matters, it is not pedantry: as written a reader concludes tagging WRITES work on file cache and only flushing breaks, so Cache::tags([...])->remember(...) looks safe. It is not - it throws too. Fix: throw the moment you TAG anything. The source article contradicts itself - its body says it right (call Cache::tags(...) on them and you will get a BadMethodCallException) and its FAQ says it wrong; the post copied the FAQ version, so fix the article FAQ too. Everything else on the post traces cleanly and Cache::flexible with [60,120] is real.
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

