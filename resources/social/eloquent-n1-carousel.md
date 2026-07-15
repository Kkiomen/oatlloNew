---
slug: eloquent-n1-carousel
type: carousel
language: en
title: "Fix the Eloquent N+1 problem"
topic: laravel
source_type: article
source: eloquent-n1-query-problem
link: https://oatllo.com/eloquent-n1-query-problem
publish_at: 2026-07-16 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, eloquent, database, webdev]
caption: |
  Your Blade loop is running 51 queries and nothing is broken.

  Eloquent relationships are lazy. Touch one inside a loop and it quietly goes
  to the database, once per row. It never throws. The page just gets slower
  every week until someone asks why the dashboard takes four seconds.

  Full write-up linked in bio.

  What found yours first: Debugbar, Telescope, or a very slow Monday?
---

## Your Blade loop is running 51 queries

You didn't write a loop of queries. Eloquent wrote it for you.

<!-- slide -->

## Nothing here says "query"

```php
$posts = Post::all(); // 1 query

foreach ($posts as $post) {
    echo $post->author->name; // +1 each
}
```

50 posts. 51 queries. Nothing throws.

<!-- slide -->

## Lazy is the default. That's the trap.

Touch `$post->author` and Eloquent checks if it is already in memory. It is
not, so it goes to the database right then. Harmless once. Fatal in a loop.

<!-- slide -->

## One word fixes it

```php
$posts = Post::with('author')->get();
```

Two queries now. Still two at 5,000 posts.

<!-- slide -->

## Because it asks once

```sql
select * from posts;
select * from users
  where id in (1, 2, 3, ...);
```

Every author in one trip, stitched on in memory.

<!-- slide role="cta" -->

## Catch it before prod does

```php
Model::preventLazyLoading(
    ! app()->isProduction()
);
```

Dev throws on the first lazy load. Production stays quiet.
