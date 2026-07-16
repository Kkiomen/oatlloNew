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
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: baba80dc48fbabbf0b76f5cdd16ba3872eb8e1c4
  checks:
    - 40 seconds and 30 million rows are the article numbers exactly, not rounded
    - nullable, no default being an instant metadata change on MySQL 8 for most cases matches the article and keeps the article hedge - the post also drops after(email), which removes the mid-table add that the article lists as expensive
    - ALTER TABLE with ADD INDEX plus ALGORITHM=INPLACE, LOCK=NONE is valid MySQL 8 syntax and a secondary index does support INPLACE with LOCK=NONE
    - LOCK=NONE rejecting the statement rather than silently taking a blocking lock is correct behaviour, verb included - it errors out, it does not downgrade
    - expand / dual-write / chunked backfill in a queued job / switch reads / drop last is the article five-step order unchanged
  notes: |
    Rollback slide (revert one deploy, old column never stopped being current) traces to the article FAQ. Code slide is safer than the article version because it omits after(email).
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
