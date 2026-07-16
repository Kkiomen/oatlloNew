---
title: "Invalidation strategies"
slug: invalidation-strategies
seo_title: "Cache Invalidation Strategies in Laravel: 5 Ways"
seo_description: "Five cache invalidation strategies in Laravel: delete-on-write, write-through, cache tags, versioned keys, and model-event busting - with trade-offs."
---

The [previous lesson](/course/redis-basics/caching-patterns-and-invalidation/why-cache-invalidation-is-hard)
explained why invalidation is hard: one change can touch many copies, and you have to
remember them all. This lesson gives you the practical tools that shrink that problem. None
of them is magic. Each trades some effort or freshness for something else, so the goal is to
know which to reach for.

## Strategy 1: delete-on-write

The simplest strategy. When a record changes, delete the cache key that holds its copy, in
the same request. The next read misses and rebuilds from fresh data. You met this as explicit
invalidation.

```php
$article->title = $request->title;
$article->save();

Cache::forget("article:{$article->id}:rendered"); // drop the stale copy
```

**Trade-off:** dead simple and precise, but you own the bookkeeping. You have to `forget` in
every place the data can change, and you have to know every key that copied it. Miss one and
that copy goes stale. Great for one or two obvious keys; painful when the data has many
dependents.

## Strategy 2: write-through

Instead of deleting the copy and letting the next reader rebuild it, you update the cache
*at the same time* you update the database. The write goes "through" the cache to the store.

```php
$article->title = $request->title;
$article->save();

// Refresh the copy immediately instead of just clearing it.
Cache::put("article:{$article->id}", $article->fresh(), 3600);
```

**Trade-off:** the cache is never empty, so there is no miss-and-rebuild after a change - the
next reader gets a warm value. The cost is that you do the work on *every* write even if
nobody reads it soon, and you still have to know which key to update. It shines for hot data
that is written rarely and read constantly.

## Strategy 3: cache tags

Delete-on-write breaks down when one change should clear *many* keys. That is what
[cache tags](/course/redis-basics/redis-and-laravel/cache-tags) solve. You tag related
entries with a shared label, then flush the whole group in one call.

```php
Cache::tags(['posts'])->remember('posts.latest', 600, fn () => Post::latest()->get());
Cache::tags(['posts'])->remember('posts.popular', 600, fn () => Post::popular()->get());

// One line clears every entry tagged 'posts', however many there are.
Cache::tags(['posts'])->flush();
```

**Trade-off:** you no longer have to enumerate each key, only the tag - a huge win for the
"how many copies?" problem. The costs: tags need a taggable store (Redis or Memcached, not
`file`), and `flush()` clears the *whole* group, so tag with the right granularity or you
throw away more than you meant to.

## Strategy 4: versioned (namespaced) keys

Sometimes you want to invalidate a whole group **without deleting anything**. The trick: put
a version number in the key, and bump it when the group changes. The old keys are instantly
orphaned - no reader ever asks for them again, and their TTL cleans them up later.

```php
// The current version lives in its own key.
$version = Cache::get('posts.version', 1);

$posts = Cache::remember("posts.latest.v{$version}", 600, fn () => Post::latest()->get());

// To invalidate the ENTIRE group, just bump the version:
Cache::increment('posts.version'); // now every read builds 'posts.latest.v2', a clean miss
```

After the bump, every key built from the old version (`...v1`) is unreachable. You did not
delete a single entry; you changed the address everyone reads from.

**Trade-off:** invalidating a group is one cheap atomic increment (recall `INCR` from
[atomic counters](/course/redis-basics/keys-values-and-expiration/atomic-counters)), and it
never races with a flush. The cost is that stale entries linger in Redis until their TTL
expires, using memory until then. Give versioned keys a sensible TTL so the orphans get
collected.

## Strategy 5: event-driven busting with model events

Every strategy above has the same weak spot: **someone has to remember to call it.** The
fix is to move invalidation next to the data itself, using Laravel model events. When any
code saves or deletes the model, the cache clears automatically - no caller has to remember.

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

Now it does not matter whether the post is changed from a controller, a command, a queued
job, or Tinker - `saved` fires, and the group is busted. You wrote the invalidation once, in
the one place that always runs.

**Trade-off:** this is the most reliable option because it removes human memory from the loop,
and it pairs perfectly with tags or versioned keys. The catch: model events only fire when
you go through Eloquent. A raw query builder update (`Post::where(...)->update([...])`) does
**not** trigger `saved`, so those paths still need manual busting.

## Which one do I use?

Here is the lens that makes the choice obvious once you have it. The real axis running through
all five is not freshness - it is **who has to remember**. Delete-on-write and write-through
put the burden on you at every single write site. Tags and versioned keys shrink what you have
to remember from "every key" down to "one label." Model events remove human memory from the
loop entirely. As a system grows and more people touch it, that axis is the one that decides
whether your cache stays correct, so bias toward the strategies that ask the least of memory.

With that in mind, they combine rather than compete:

- One or two obvious keys - **delete-on-write**.
- Hot data read constantly, written rarely - **write-through**.
- One change clears many related keys - **cache tags**.
- Clear a whole group cheaply without deleting - **versioned keys**.
- Never want to rely on remembering - **model events** driving any of the above.

And whatever you choose, keep a TTL underneath it as the safety net, exactly as
[the TTL lesson](/course/redis-basics/caching-patterns-and-invalidation/ttl-vs-explicit-invalidation)
argued. The strategies make invalidation reliable; the TTL forgives the day one of them
misses.

## Common mistake

Reaching for model events but caching outside Eloquent. If you build a cached value from a
raw `DB::table('posts')->...` query or a bulk `->update()`, the `saved` event never fires and
your automatic busting silently does nothing. Either go through Eloquent so the events run, or
clear the cache by hand on those specific paths.

## FAQ

### What is the difference between write-through and delete-on-write?

Delete-on-write clears the copy and lets the next reader rebuild it. Write-through rebuilds
it right away as part of the write. Delete is cheaper if the value might not be read soon;
write-through avoids a cache miss for hot values that are read constantly.

### Do versioned keys leave garbage in Redis?

Yes, temporarily. Old-version keys are orphaned but stay until their TTL expires, so always
give versioned entries a TTL. In exchange you get whole-group invalidation with one atomic
increment and no race.

### Are model events enough on their own?

Almost, but not quite. They cover every change that goes through Eloquent, which is most of
them. Bulk query-builder updates and raw SQL bypass the events, so those paths still need
manual invalidation.
