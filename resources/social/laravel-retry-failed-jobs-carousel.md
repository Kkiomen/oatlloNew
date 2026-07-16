---
slug: laravel-retry-failed-jobs-carousel
type: carousel
language: en
title: "Laravel job retries"
topic: laravel
source_type: article
source: laravel-retry-failed-jobs
link: https://oatllo.com/laravel-retry-failed-jobs
publish_at: 2026-11-16 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, queues, jobs, backend]
caption: |
  Set neither `--tries` nor `$tries` and the default is effectively one attempt. First transient blip, straight to failed_jobs.

  Then it gets subtle: `retryUntil()` silently wins over `$tries`, and
  `$maxExceptions` counts something different from `$tries` entirely.

  Full write-up linked in bio.

  What is sitting in your failed_jobs table right now?
---

## Without --tries set, a worker gives every job just one shot

A payment webhook times out, an S3 upload hiccups, an API returns a 503. One
uncaught exception and the job is in `failed_jobs` permanently.

<!-- slide -->

## Make the limit explicit

```bash
php artisan queue:work --tries=3 --backoff=10
```

Three attempts, ten seconds apart. `$tries` on the job class wins over the
worker default, so the config travels with the code.

<!-- slide -->

## Define both and $tries is ignored

```php
public int $tries = 5;

public function retryUntil(): \DateTime
{
    return now()->addMinutes(10);
}
```

The worker checks the clock first. It only falls back to the attempt count once
the deadline has passed.

<!-- slide -->

## These two count different events

```php
public int $tries = 25;
public int $maxExceptions = 3;
```

`$tries` counts every attempt, including released locks and timeouts.
`$maxExceptions` counts only uncaught exceptions. Up to 25 attempts, but three
real errors kill it for good.

<!-- slide -->

## An array turns backoff exponential

```php
public function backoff(): array
{
    return [10, 30, 120];
}
```

10s, then 30s, then 120s. More attempts than entries and Laravel reuses the
last value. A single integer gives you a flat delay instead.

<!-- slide role="cta" -->

## queue:retry does not run anything

It pushes the job back onto its queue and removes the row. With no worker
consuming that queue, nothing happens. And `queue:flush` deletes instead of
retrying.
