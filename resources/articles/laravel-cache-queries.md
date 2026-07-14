---
name: "How to Cache Expensive Queries in Laravel"
slug: laravel-cache-queries
short_description: "Learn to cache Laravel queries the right way: Cache::remember, TTLs, cache keys, Redis tags, and invalidation that doesn't serve stale data."
language: en
published_at: 2026-08-17 09:00:00
is_published: true
tags: [laravel, cache, performance, redis, php]
---

If you want to **cache Laravel queries** without introducing subtle bugs, the hard part isn't storing the result — it's knowing when the cached copy is wrong. Caching an expensive query is a two-line change. Keeping that cache honest as your data changes is the part that keeps people up at night. This guide covers both: the mechanics of `Cache::remember`, how to pick a TTL, how to build keys that won't collide, tag-based invalidation with Redis, and how to bust the cache the moment a model changes.

Everything below is runnable. Swap in your own model and column names and it works as-is.

## Start with Cache::remember

The core helper you'll reach for is `Cache::remember`. It takes a key, a TTL, and a closure. If the key exists in the cache, Laravel returns it. If not, it runs the closure, stores the return value, and hands it back.

```php
use Illuminate\Support\Facades\Cache;

$stats = Cache::remember('dashboard:stats', now()->addMinutes(10), function () {
    return [
        'orders'  => Order::whereMonth('created_at', now()->month)->count(),
        'revenue' => Order::whereMonth('created_at', now()->month)->sum('total'),
        'signups' => User::whereDate('created_at', today())->count(),
    ];
});
```

The closure only runs on a cache miss. On every hit for the next ten minutes, those three aggregate queries never touch the database. That's the whole win: you trade a little freshness for a lot less load.

A few things worth knowing:

- The TTL accepts a `DateTime`, a number of seconds, or a `DateInterval`. `now()->addMinutes(10)` and `600` mean the same thing.
- Whatever the closure returns gets serialized, so Eloquent models and collections are fine, but see the note on caching models below.
- If you want the value to live until you explicitly delete it, use `Cache::rememberForever('key', fn () => ...)`.

`rememberForever` is tempting for data that "never" changes, but "never" is a strong word. I've been burned by a forever-cached settings blob that outlived three deploys because nobody remembered it was there. If you use it, pair it with a deliberate invalidation path.

## Cache the query result, not the loop

One mistake I see constantly: caching *inside* a loop instead of caching the collection.

```php
// Don't do this — one cache lookup per iteration, and N queries on a cold cache
foreach ($productIds as $id) {
    $product = Cache::remember("product:$id", 3600, fn () => Product::find($id));
    // ...
}
```

That generates a separate key per product and, on a cold cache, still fires one query each. Cache the whole result set once:

```php
$products = Cache::remember('products:active', 3600, function () {
    return Product::where('active', true)->get()->keyBy('id');
});

foreach ($productIds as $id) {
    $product = $products[$id] ?? null;
}
```

One key, one query, one round trip. This ties directly into the [Eloquent N+1 query problem](/blog/eloquent-n1-query-problem). Caching won't save you if the underlying query is already firing hundreds of times. Fix the query first, then cache the fixed version.

## Choosing a TTL

The TTL is a judgment call, not a formula. Ask two questions: how expensive is the query, and how stale can the data get before someone complains?

- **Seconds (30–120s):** high-traffic data that changes often: a live feed, a "trending" list. Short TTLs smooth out traffic spikes without letting data drift far.
- **Minutes (5–30m):** dashboards, reports, aggregate counts. Nobody minds if the revenue widget is ten minutes behind.
- **Hours or forever:** reference data that barely moves: country lists, category trees, feature flags. Cache these long and invalidate them explicitly on change.

When in doubt, start short. A too-short TTL costs you a few extra queries. A too-long one serves wrong data to users, and that's the failure that generates support tickets.

## Cache keys that won't collide

A cache is a giant shared key-value store. If two features pick the same key, they'll read each other's data. Build keys deliberately:

```php
// Include everything that changes the result
$key = "user:{$user->id}:orders:page:{$page}:status:{$status}";

$orders = Cache::remember($key, 300, function () use ($user, $page, $status) {
    return $user->orders()->where('status', $status)->paginate(15, ['*'], 'page', $page);
});
```

Rules I stick to:

- **Namespace with colons.** `user:42:orders` reads clearly and groups logically.
- **Put every input that changes the output into the key:** the user id, filters, page number, sort order. A key that ignores the `$status` filter will happily serve pending orders to someone who asked for shipped ones.
- **Keep keys short but readable.** If the inputs are large, hash them: `'report:' . md5(json_encode($filters))`.

## Cache tags with Redis

Deleting one key is easy. Deleting *everything related to a user* when their data changes is where tags come in.

```php
$orders = Cache::tags(['orders', "user:{$user->id}"])->remember(
    "user:{$user->id}:orders",
    600,
    fn () => $user->orders()->latest()->get()
);
```

Later, when that user's orders change, you flush the whole tag in one call:

```php
Cache::tags(["user:{$user->id}"])->flush();
```

Every entry tagged with `user:42` is gone, regardless of how many keys you created under it. No need to track them individually.

**The catch:** cache tags require a taggable store. That means **Redis or Memcached**. The `file` and `database` drivers do **not** support tags; call `Cache::tags(...)` on them and you'll get a `BadMethodCallException`. Check your `CACHE_STORE` (or `CACHE_DRIVER` on older Laravel) before you build a design around tags. In production you almost certainly want Redis anyway.

## Invalidating on model change

Here's the part that actually matters. A TTL is a lazy fallback — it says "this data is allowed to be wrong for up to N minutes." For anything users edit, you want the cache to update the *instant* the source changes. That means hooking into the model lifecycle.

The cleanest way is an observer:

```php
namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->flush($product);
    }

    public function deleted(Product $product): void
    {
        $this->flush($product);
    }

    private function flush(Product $product): void
    {
        Cache::forget('products:active');
        Cache::forget("product:{$product->id}");
        // With Redis:
        // Cache::tags(['products'])->flush();
    }
}
```

Register it in a service provider:

```php
use App\Models\Product;
use App\Observers\ProductObserver;

public function boot(): void
{
    Product::observe(ProductObserver::class);
}
```

The `saved` event fires on both `created` and `updated`, so one method covers new and edited records. `deleted` handles removals. If you'd rather not create an observer class, you can drop the same logic into the model's `booted` method with `static::saved(...)` and `static::deleted(...)` closures: same events, less ceremony.

One honest warning from experience: model events **only fire when you go through Eloquent**. A `Product::where(...)->update([...])` mass update, a raw `DB::table('products')->update(...)`, or a direct SQL migration will silently skip your observer and leave a stale cache behind. The first time this bit me, I spent an afternoon convinced Redis was broken before realizing a seeder was bulk-inserting rows that never triggered a single event. If you do bulk operations, flush the relevant keys manually right after.

## Cache stampede

There's one failure mode worth designing against up front. When a popular key expires, every request that arrives in that split second sees a miss, and they *all* run the expensive query at once. That's a cache stampede (or "dogpile"), and it can hammer your database exactly when traffic is highest.

Laravel gives you two tools here. On Laravel 11 and up there's `Cache::flexible`, and on anything older there's `Cache::lock`. `flexible` implements stale-while-revalidate: it keeps serving slightly stale data while a single deferred refresh runs, instead of letting every request pile onto the database at once.

```php
// Fresh for 60s; between 60s and 120s, serve stale and refresh once in the background
$feed = Cache::flexible('home:feed', [60, 120], function () {
    return Post::published()->latest()->limit(20)->get();
});
```

If you're on an older version, gate the rebuild behind an atomic lock so exactly one process hits the database and the rest wait for it:

```php
$feed = Cache::get('home:feed');

if ($feed === null) {
    $lock = Cache::lock('home:feed:rebuild', 10);

    if ($lock->get()) {
        // We won the lock, so we're the one process allowed to run the query.
        try {
            $feed = Post::published()->latest()->limit(20)->get();
            Cache::put('home:feed', $feed, 60);
        } finally {
            $lock->release();
        }
    } else {
        // Someone else is already rebuilding — wait a moment, then read their result.
        $feed = $lock->block(5, fn () => Cache::get('home:feed'));
    }
}
```

The important bit is that the query lives *inside* the `if ($lock->get())` branch. If you leave it outside, every request still runs the expensive query on a miss and the lock only stops them from writing over each other, which defeats the entire purpose.

For most apps you won't need this until a specific key is both expensive and very hot. Reach for it then, not before.

## Pitfalls to watch for

- **Stale data.** The default risk. A TTL guarantees your data can be wrong for its full duration. For anything editable, back the TTL with event-based invalidation instead of relying on expiry alone.
- **Missed invalidation on bulk updates.** As above: mass updates and raw queries skip model events. Flush manually.
- **Caching full Eloquent models.** A serialized model can carry loaded relationships and stale attributes. Often you're better off caching a plain array or a slim DTO, then rehydrating. It also survives model changes better across deploys.
- **Tags on the wrong driver.** `file`/`database` can't do tags. Confirm Redis or Memcached first.
- **Keys that ignore inputs.** Leave a filter, user id, or locale out of the key and you'll serve one user's data to another. That's a data-leak bug, not just a caching bug.
- **Forgetting the cache exists.** `rememberForever` values outlive deploys. Document every long-lived key or it becomes a ghost.

There's an old joke that there are only two hard things in computer science: cache invalidation and naming things. It's a joke because it's true. The storing is easy; knowing when your copy went bad is the whole discipline.

## When caching isn't the answer

Caching hides a slow query. It doesn't fix it. Before you cache, make sure the query itself is reasonable. A missing index can turn a sub-millisecond lookup into a full table scan, and no amount of caching helps the first (uncached) request or the moment the cache expires. If your query is slow because of how it's written or indexed, start there (see the [database indexing guide](/blog/database-indexing-explained)) and cache the already-fast result.

## FAQ

**Does `Cache::remember` cache database queries automatically?**
No. It caches whatever the closure returns. You decide what goes in: a query result, an array, a computed value. There's no automatic query interception; you wrap the specific expensive calls you want cached.

**What's the difference between `remember` and `rememberForever`?**
`remember` takes a TTL and expires the value automatically. `rememberForever` stores it with no expiry. It lives until you call `Cache::forget` or flush the store. Use `rememberForever` only when you have a reliable invalidation path.

**Why does `Cache::tags()` throw an exception?**
Your cache store doesn't support tagging. Tags need a taggable driver — Redis or Memcached. The `file` and `database` drivers throw `BadMethodCallException` the moment you call `->flush()` on a tag. Point `CACHE_STORE` at `redis` (or `memcached`) and the same code starts working.

**How do I clear a cached query when the data changes?**
Hook into the model's `saved` and `deleted` events, either through an observer or the model's `booted` method, and call `Cache::forget()` on the affected keys (or `Cache::tags([...])->flush()` with Redis). Remember that bulk updates and raw SQL bypass these events.

## Wrapping up

Caching expensive queries in Laravel comes down to three moving parts: `Cache::remember` with a sensible TTL to store the result, well-namespaced keys that include every input, and disciplined invalidation through model events or Redis tags so nobody ever sees stale data. Get the invalidation right and caching is one of the highest-leverage performance changes you can make. Get it wrong and you've built a very fast way to show people the wrong numbers.

Start small: find your slowest, most-repeated query, wrap it in `Cache::remember` with a short TTL, and add a `forget` call in the relevant observer. Measure, then tune the TTL. That single loop — cache, invalidate, measure — will carry you a long way before you ever need stampede protection or anything fancier.