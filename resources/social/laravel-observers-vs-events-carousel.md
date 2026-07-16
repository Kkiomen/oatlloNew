---
slug: laravel-observers-vs-events-carousel
type: carousel
language: en
title: "Observers vs events"
topic: laravel
source_type: article
source: laravel-observers-vs-events
link: https://oatllo.com/laravel-observers-vs-events
publish_at: 2026-10-19 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, eloquent, php, architecture, backend]
caption: |
  My audit observer worked in the admin panel and logged nothing from the nightly job.

  A query-builder update compiles one SQL statement. No model is hydrated,
  so no Eloquent event fires and the observer never runs. That is a
  performance feature, not a bug.

  Full comparison linked in bio.

  Which side effect bit you three months later?
---

## Bulk updates skip observers, and your audit log goes silent.

The admin panel logged every change. The nightly archive command produced
zero rows. Same model, same observer.

<!-- slide -->

## Same outcome. Only one fires a hook.

```php
$user->update(['status' => 'archived']);
// observer fires

User::where('active', false)
    ->update(['status' => 'archived']);
// silence
```

Both archive accounts. Only the first one goes through a model instance.

<!-- slide -->

## No model hydrated, no event to fire

The query builder compiles a single `UPDATE` and sends it. There is
nothing to hook into. That is deliberate: you do not want 50,000 objects
instantiated to run one statement.

<!-- slide -->

## Four more ways to run past it

```php
$user->saveQuietly();
$user->updateQuietly(['name' => 'Ada']);
User::where('id', 1)->delete();
DB::table('users')->update(['a' => 1]);
```

The quiet methods mute events on purpose. If a side effect must happen on
every write, it cannot live only in an observer.

<!-- slide -->

## Dispatch it yourself from every path

```php
OrderShipped::dispatch($order);
```

The event does not care whether it came from the API, a queued job, an
admin action or a webhook. Observers cannot do that: they are pinned to
one model.

<!-- slide role="cta" -->

## Thin observer, real event

Observers for model bookkeeping: slugs, cache busting, cascade cleanup.
Events for business moments and anything queued. Write the assumption
down. Full comparison linked in bio.
