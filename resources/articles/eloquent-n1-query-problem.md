---
name: "Eloquent N+1 Query Problem: How to Detect and Fix It"
slug: eloquent-n1-query-problem
short_description: "Fix the Eloquent N+1 problem for good: why lazy loading in a loop kills performance, how to detect it, and eager loading patterns that scale."
language: en
published_at: 2026-07-17 09:00:00
is_published: true
tags: [laravel, eloquent, performance, php, database]
---

The **Eloquent N+1 problem** is the single most common performance bug I find when a Laravel page that felt instant in development crawls to a halt with real data. It rarely throws an error. Nothing is broken. The page just gets slower and slower as the table grows, and one day someone asks why the dashboard takes four seconds to load. Nine times out of ten, the answer is a relationship being lazy-loaded inside a loop.

This post walks through what the N+1 problem actually is, how to catch it before it ships, and the exact Eloquent tools that fix it. Everything here is runnable. Copy it, adapt the model names, and watch your query count drop.

## What the Eloquent N+1 problem is

Say you're listing 50 blog posts and showing each author's name:

```php
$posts = Post::all(); // 1 query

foreach ($posts as $post) {
    echo $post->author->name; // 1 query, per post
}
```

That looks harmless. But it fires **51 queries**: one to load the posts, then one more every time you touch `$post->author`. That relationship hasn't been loaded yet, so Eloquent quietly runs a `SELECT` to fetch it on the spot.

That's the "N+1": **1** query for the parent collection, plus **N** queries for the N children. With 50 posts it's 51 queries. With 5,000 posts it's 5,001. The query count scales linearly with your data, which is exactly what you don't want.

### Why it happens: lazy loading in a loop

Eloquent relationships are lazy by default. When you access `$post->author`, Eloquent checks whether that relation is already loaded in memory. If it isn't, it goes to the database right then and there and fetches it. This is convenient; you don't have to think about it. It's also the whole trap.

Inside a single loop iteration, one lazy query is nothing. Multiply it by every row in the collection and you've got a pile of round trips to the database, each with its own latency. The SQL itself is trivial; the cost is the sheer number of separate trips.

## How to detect the N+1 problem

You can't fix what you can't see, and N+1 is invisible until you count queries. Three ways to make it visible, from quickest to most thorough.

### Laravel Debugbar

[Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) is the fastest feedback loop. Install it as a dev dependency:

```bash
composer require barryvdh/laravel-debugbar --dev
```

Load any page and the "Queries" tab shows the total count plus every statement that ran. When you see the same `select * from users where id = ?` repeated 40 times with only the bound value changing, that's your N+1 staring back at you. This repeated-query pattern is the signature. Once you've seen it, you'll spot it instantly.

### Laravel Telescope

For a running app, especially staging, [Telescope](https://laravel.com/docs/telescope) gives you the same query visibility per request, recorded so you can inspect it after the fact. Its "Requests" and "Queries" tabs let you sort by query count and hunt down the heaviest endpoints. I lean on Telescope when a problem only shows up with production-shaped data that I can't easily reproduce locally.

### Make Eloquent yell at you: preventLazyLoading()

The best detection is the kind that fails loudly before code reaches production. Eloquent can throw an exception the moment any relationship is lazy-loaded, forcing you to eager-load it explicitly. Add this to `AppServiceProvider::boot()`:

```php
use Illuminate\Database\Eloquent\Model;

public function boot(): void
{
    Model::preventLazyLoading(! app()->isProduction());
}
```

The `! app()->isProduction()` guard is important. In development and testing you get a `LazyLoadingViolationException` the instant you access an unloaded relation, and the bug becomes impossible to miss. In production it stays off, so a missed eager-load degrades performance instead of crashing a live page. You want the failure in your face while coding, not in front of a customer.

Once this is on, every N+1 turns into a test failure or an obvious exception during local clicking-around. It's the closest thing to a permanent guardrail.

## How to fix the N+1 problem

The fix is **eager loading**: tell Eloquent up front which relationships you'll need, so it loads them in a small, fixed number of queries instead of one-per-row.

### with(): eager load at query time

When you know at query time that you'll need the relation, use `with()`:

```php
$posts = Post::with('author')->get();

foreach ($posts as $post) {
    echo $post->author->name; // no extra query
}
```

Now Eloquent runs **2 queries total**, no matter how many posts:

1. `select * from posts`
2. `select * from users where id in (1, 2, 3, ...)`

It grabs every author in one `IN` query and stitches them onto the posts in memory. Fifty posts, two queries. Five thousand posts, still two queries. That's the whole game: a constant number of queries instead of one per row.

### load(): eager load after the fact

Sometimes you already have a model or collection in hand and *then* realize you need a relation. Use `load()` to eager-load onto the existing instances:

```php
$posts = Post::all();

// ...later, conditionally
if ($needsAuthors) {
    $posts->load('author');
}
```

`load()` is also handy on a single model you were handed, for example inside a controller that received a route-model-bound instance.

### Nested and constrained eager loading

Relations chain with dot notation, and you can constrain what gets loaded so you don't drag back rows you don't need:

```php
$posts = Post::with([
    'author',
    'comments' => fn ($query) => $query->where('approved', true)->latest(),
    'comments.user',
])->get();
```

That loads authors, only approved comments (newest first), and the user behind each comment, all in a handful of queries rather than a cascade of hundreds. Constrained eager loading is the part people forget: you're allowed to filter and order the eager-loaded relation, not just name it.

### withCount(): when you only need the number

A subtle N+1 hides in counting. If you call `$post->comments->count()` in a loop, you load every comment row just to count them, which is wasteful even when it isn't strictly N+1. Use `withCount()` instead:

```php
$posts = Post::withCount('comments')->get();

foreach ($posts as $post) {
    echo $post->comments_count; // aggregated in the original query
}
```

Eloquent adds a `comments_count` column via a subquery. No comment rows loaded, no extra trips.

### loadMissing(): don't reload what's already there

When code paths might already have loaded a relation, `loadMissing()` loads it only if it's absent — so you never fire a redundant query:

```php
$post->loadMissing('author', 'tags');
```

This is my default in shared helper methods and view composers, where I can't be sure what the caller already eager-loaded. It keeps the query count honest without double work.

## Before and after: the query count

Here's the same author-listing feature over 100 posts, measured with Debugbar.

**Before** — lazy loading in a loop:

```php
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}
// Queries: 101
```

**After** — eager loading with `with()`:

```php
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name;
}
// Queries: 2
```

101 queries down to 2. The page render time drops right along with it. And this is the part that matters: it *stays* flat as the table grows. That's the difference between a query pattern that scales and one that quietly rots.

## FAQ

### Is eager loading always faster than lazy loading?

Almost always when you're iterating a collection and touching a relation on each item. The exception is when you eager-load a relation you never actually use. Then you've paid for a query and a chunk of memory for nothing. Load what the page needs, and nothing more.

### Does with() cause a JOIN?

No. `with()` runs a **separate** query per relationship and matches the results in PHP using an `IN (...)` clause. That's usually a good thing: no row duplication from joins, and each relation is a clean, indexable lookup. If you specifically need a join (for filtering or sorting by a related column), reach for `join()` or `whereHas()` instead.

### Will preventLazyLoading break production?

Not if you guard it with `! app()->isProduction()`. It throws only in your local and testing environments, turning silent N+1 bugs into failures you catch before merging. Production keeps working; just tune the eager loads before you get there.

### How is this different from slow individual queries?

N+1 isn't about any one query being slow. Each is trivially fast. The problem is *volume*: hundreds of fast queries add up to more latency than a couple of well-shaped ones. For genuinely slow individual queries, the fix is different, so see the related posts below.

## Wrapping up

The Eloquent N+1 problem comes down to one habit: lazy-loading a relationship inside a loop. Detect it by counting queries with Debugbar or Telescope, and make it impossible to ship by turning on `Model::preventLazyLoading()` in development. Then fix it with the right tool — `with()` at query time, `load()` and `loadMissing()` after the fact, constrained and nested eager loading when you need to be precise, and `withCount()` when you only want a number.

Do that and your query count stops tracking your row count. Pages stay fast at 50 records and at 50,000.

Eager loading solves the *count* of queries. Once the count is under control, the next levers are the queries themselves: [caching expensive queries](/blog/laravel-cache-queries) so you don't rerun them, and [database indexing](/blog/database-indexing-explained) so the ones you do run stay fast. Together those three cover most of the database performance problems a Laravel app will ever hit.