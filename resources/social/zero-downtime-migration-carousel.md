---
slug: zero-downtime-migration-carousel
type: carousel
language: en
title: "Zero-downtime database migrations in Laravel"
topic: laravel
source_type: article
source: zero-downtime-migration-laravel
link: https://oatllo.com/zero-downtime-migration-laravel
publish_at: 2026-08-10 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, database, devops, mysql, backend]
caption: |
  The deploy was green, the migration was "just adding a column," and checkout threw 500s for 40 seconds.

  The alter rewrote a 30-million-row table and held the lock the whole time.
  Your laptop has a hundred rows, so nothing about it looked wrong in review.

  Full write-up linked in bio.

  What is the biggest table you have run a naive ALTER against?
---

## The migration locked the table. The site was down for 40 seconds.

The deploy was green. The users disagreed.

<!-- slide -->

## Your laptop has a hundred rows. Prod has 30 million.

Some alters copy the entire table into a new one and hold the lock until they
finish. The migration works. It just blocks every other query on that table
while it runs.

<!-- slide -->

## Add the column. Ship nothing else.

```php
Schema::table('users', function ($table) {
    $table->string('full_name')->nullable();
});
```

Nullable, no default. On MySQL 8 that is an instant metadata change for most
cases. No rewrite, no long lock.

<!-- slide -->

## Never change and remove in the same deploy

Expand. Write to both shapes. Backfill in chunks from a queued job. Switch
reads. Only then drop the old column. The slow part happens where it cannot
hurt anyone.

<!-- slide -->

## Rollback is a deploy, not a restore

You keep writing the old column until reads on the new shape have soaked. If
they misbehave, you revert one deploy. The old column never stopped being
current.

<!-- slide role="cta" -->

## Make MySQL refuse to block you

```sql
ALTER TABLE orders
  ADD INDEX idx_orders_status (status),
  ALGORITHM=INPLACE, LOCK=NONE;
```

LOCK=NONE rejects the statement instead of silently taking a blocking lock.
Fail in review, not in prod.
