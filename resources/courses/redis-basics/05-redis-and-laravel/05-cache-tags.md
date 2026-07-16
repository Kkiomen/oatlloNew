---
title: "Cache tags"
slug: cache-tags
seo_title: "Laravel Cache Tags with Redis: flush a group at once"
seo_description: "Group cache entries with Laravel cache tags and flush a whole group in one call. Why tags need a taggable store like Redis, with a real posts example."
---

## Clearing a group of cache entries, not one key

In the last lesson you saw [Cache::forget](/course/redis-basics/redis-and-laravel/caching-in-laravel), which removes one entry, and `Cache::flush()`, which wipes the whole store. Real apps often need the middle ground: clear a whole group of related entries at once and leave everything else alone. Laravel cache tags give you exactly that.

Picture ten different lists of posts cached under ten keys. When one post changes, all ten are stale. Forgetting them one by one means tracking every key you ever used. Flushing the store throws away unrelated caches too. Tags cut straight through it.

## Tagging cache entries with Cache::tags

You attach one or more tags to a cache entry by chaining `tags()` before the usual call. Everything else works the same as before.

```php
use Illuminate\Support\Facades\Cache;

$posts = Cache::tags(['posts'])->remember('posts.latest', 600, function () {
    return Post::latest()->take(10)->get();
});
```

This caches the value for 600 seconds, just like plain `remember`, but it also records that this entry belongs to the `posts` group. You can tag several keys the same way.

```php
Cache::tags(['posts'])->remember('posts.popular', 600, fn () => Post::popular()->get());
Cache::tags(['posts'])->remember('posts.count', 600, fn () => Post::count());
```

Now three entries carry the `posts` tag. To read a tagged entry back, you must ask through the same tag:

```php
$posts = Cache::tags(['posts'])->get('posts.latest');
```

## Flushing a whole tag group at once

Here is the payoff. One call clears every entry that carries the tag, and nothing else:

```php
Cache::tags(['posts'])->flush();
```

All three `posts` entries are gone. Any cache you stored without that tag is untouched.

## Flush on change: a real posts example

Tag the reads, then flush the group whenever a post changes. A model event is a natural place for it.

```php
class Post extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::tags(['posts'])->flush());
        static::deleted(fn () => Cache::tags(['posts'])->flush());
    }
}
```

Every list of posts is cached and fast. The moment a post is created, edited, or deleted, the whole `posts` group is cleared and the next request rebuilds it fresh. You never track individual keys.

## Tags need a taggable store

Tags do not work on every cache store. They need a store that can track which keys belong to which tag. **Redis works. So does Memcached.** The `file` and `database` stores do **not** support tags at all.

If you set up Redis as your cache store in the earlier lesson, you are ready. If your cache is still on `file` or `database`, tag calls throw an error like "This cache store does not support tagging." One more reason Redis earns its place as the cache store for a real app.

Worth knowing what this costs under the hood: to make grouping possible, Redis keeps a small index tying each tag to the keys that carry it. So a tagged `remember` writes a touch more than a plain one - the value plus that bookkeeping. It is a fair price for flushing a group in a single call, but it is why you tag deliberately rather than tagging everything.

## Common mistake

Two traps catch people here.

First, calling `Cache::tags(...)` on a non-taggable store. If `CACHE_STORE` is `file` or `database`, the call throws. Switch to Redis (or Memcached) before you reach for tags.

Second, expecting `flush()` on a tag to clear one entry. It does not. `Cache::tags(['posts'])->flush()` clears **every** entry with the `posts` tag, not just one. To remove a single entry, use `forget` with the same tag: `Cache::tags(['posts'])->forget('posts.latest')`.

## FAQ

### Do cache tags work with the file cache driver?

No. Only stores that can track tag-to-key relationships support tags. Redis and Memcached do; `file` and `database` do not. Set `CACHE_STORE=redis` first.

### How do I read a value I stored with a tag?

Ask through the same tag: `Cache::tags(['posts'])->get('posts.latest')`. A plain `Cache::get('posts.latest')` will not find a tagged entry.

### Can one entry have more than one tag?

Yes. Pass an array: `Cache::tags(['posts', 'homepage'])->remember(...)`. Flushing either `posts` or `homepage` then clears that entry.
