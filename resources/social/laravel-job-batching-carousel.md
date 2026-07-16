---
slug: laravel-job-batching-carousel
type: carousel
language: en
title: "Job batching"
topic: laravel
source_type: article
source: laravel-job-batching
link: https://oatllo.com/laravel-job-batching
publish_at: 2026-09-28 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, queues, backend, webdev]
caption: |
  Eleven minutes, then a memory limit, and no idea how far the import actually got.

  A batch turns one fragile job into a group you can watch and cancel. Just
  remember allowFailures() and the cancelled() guard.

  Full guide linked in bio.

  Which queue bug cost you the most?
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: df9dc4f66850acb9d4f99519cb5263e3d5e129ba
  checks:
    - "numbers check out: 3 corrupt of 10,000 leaves 9,997 - matches the article arithmetic"
    - allowFailures() semantics correct, including the trap that then will NOT fire if anything failed, use hasFailures()
    - cancel is cooperative - cancel() sets a flag, queued jobs still get picked up; SkipIfBatchCancelled middleware is a real class
    - "batch API names are real: Bus::findBatch, progress() 0-100, finished(), failedJobs, Batchable trait"
    - sync driver cannot run a batch - matches the article and the reason (shared record many workers update)
  notes: |
    Clean. CTA doubles as a pitfall slide rather than a call to action, but that is a taste call, not a defect.
---

## One failed job kills the whole batch without allowFailures()

By default a single failure cancels the batch. The remaining jobs never run.
Three corrupt images out of 10,000, and the other 9,997 stop dead.

<!-- slide -->

## Let the rest finish

```php
Bus::batch($jobs)
    ->allowFailures()
    ->dispatch();
```

Failed jobs get recorded but no longer cancel the batch. The trade: `then` will
not fire if anything failed. Check `$batch->hasFailures()` instead.

<!-- slide -->

## Cancel is a flag, not a kill switch

```php
public function handle(): void
{
    if ($this->batch()->cancelled()) {
        return;
    }
    // real work here
}
```

`cancel()` does not stop queued jobs. A worker still picks them up. Every job
checks for itself, or you use the `SkipIfBatchCancelled` middleware.

<!-- slide -->

## A real progress bar, no websockets

```php
$batch = Bus::findBatch($id);

$batch->progress();  // 0-100
$batch->finished();  // bool
$batch->failedJobs;  // int
```

Store the batch id when you dispatch it. Poll it every two or three seconds.
That is the whole thing.

<!-- slide role="cta" -->

## The sync driver will not run a batch

Batching needs a shared record that many workers update: `database`, `redis`, a
real driver. Forget the `Batchable` trait and `$this->batch()` throws.

