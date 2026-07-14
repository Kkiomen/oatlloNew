---
name: "How to Retry Failed Laravel Queue Jobs the Right Way"
slug: laravel-retry-failed-jobs
short_description: "Learn to retry failed jobs in Laravel with queue:retry, tries, backoff, and retryUntil so transient errors stop breaking your queue."
language: en
published_at: 2026-07-08 09:00:00
is_published: true
tags: [laravel, queues, php, jobs]
---

If you run background workers long enough, a job will fail. A payment webhook times out, an S3 upload hiccups, a third-party API returns a 503. Learning how to **retry failed jobs in Laravel** properly is the difference between a queue that self-heals and one that quietly drops work on the floor. This guide covers the `queue:retry` command, the `failed_jobs` table, and the retry knobs (`$tries`, `$backoff`, `$maxExceptions`, and `retryUntil()`) with the Laravel 11/12 APIs.

The short version: most retries should be automatic, and the manual `queue:retry` command is your safety net for the ones that slipped through.

## How Laravel decides a job has failed

A queued job fails when it throws an uncaught exception and runs out of retry attempts. When that happens, Laravel writes a row to the `failed_jobs` table and stops touching the job. It does **not** silently retry forever — it retries up to the limit you set, then gives up and records the failure.

Two things control the automatic attempts:

- The `--tries` option on the worker command.
- The `$tries` property on the job class (this wins over the worker default).

If you never set either, the default is effectively a single attempt: the job runs once and fails on the first exception. That surprises a lot of people, so make the limit explicit.

```bash
php artisan queue:work --tries=3 --backoff=10
```

This tells the worker to attempt each job up to three times, waiting ten seconds between attempts. Run the same worker under Supervisor or Horizon in production so it restarts after deploys and crashes.

## Setting up the failed_jobs table

Fresh Laravel apps ship with the `failed_jobs` migration already. If you are on an older upgrade path and it is missing, generate and run it:

```bash
php artisan make:queue-failed-table
php artisan migrate
```

The table stores the connection, queue, the serialized job payload, and the full exception. That payload is what makes retrying possible. `queue:retry` re-pushes the exact same job instance back onto its queue. The failed-job storage is configured under `config/queue.php`:

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'failed_jobs',
],
```

The `database-uuids` driver gives each failed job a UUID rather than an auto-increment ID, which is what you pass to `queue:retry`.

## Retrying failed jobs from the command line

Start by listing what actually failed:

```bash
php artisan queue:failed
```

You get a table of UUIDs, the connection, the queue name, the job class, and when it failed. To retry one specific job, pass its ID:

```bash
php artisan queue:retry 5ea9dda3-661f-4a45-a6d3-6d99c6a8c58a
```

You can pass several IDs at once, separated by spaces. When you have triaged a batch of failures (say a downstream API was down for ten minutes and everything queued against it died), retry the whole lot:

```bash
php artisan queue:retry all
```

`queue:retry` does not run the job inline. It pushes the failed job back onto its original queue and removes it from `failed_jobs`. A running worker then picks it up like any other job. **If no worker is running, nothing happens** — this bites people in local testing constantly. Start a worker, then retry.

You can also scope a retry to one queue, which is handy when a single integration blew up:

```bash
php artisan queue:retry --queue=webhooks
```

Once you have confirmed a failure is genuinely dead — bad data, a bug you have since fixed elsewhere, whatever — clean it up:

```bash
php artisan queue:forget 5ea9dda3-661f-4a45-a6d3-6d99c6a8c58a  # delete one
php artisan queue:flush                                        # delete all failed jobs
```

`queue:flush` is destructive and irreversible. I have watched someone flush a table they meant to `queue:retry all`. Read the command twice.

## Controlling retries inside the job class

Command-line retries are the manual fallback. The better place to define retry behavior is on the job itself, so it travels with the code.

### Limiting attempts with $tries and retryUntil

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }

    public function handle(): void
    {
        // ... call the billing API
    }
}
```

`$tries` caps the number of attempts. `retryUntil()` sets a time-based deadline instead: the job keeps retrying until that moment, regardless of attempt count. Here's the part that trips people up: if you define both, `retryUntil()` wins and `$tries` is ignored while the deadline is still in the future. The worker checks the clock first and only falls back to the attempt count when there is no `retryUntil()`. (A `$maxExceptions` limit still applies either way, more on that next.) Time-based limits fit work that only matters within a window, like a sync that has to land before an hourly report runs.

### Guarding against a flood of exceptions with $maxExceptions

`$tries` counts every attempt, including ones that time out or hit a released lock. `$maxExceptions` counts only uncaught exceptions. Combining them lets a job retry many times for slow responses while still bailing quickly if it keeps genuinely erroring:

```php
public int $tries = 25;
public int $maxExceptions = 3;
```

Here the job may be attempted up to 25 times (useful if you release it back to the queue while waiting on a rate limit), but three real exceptions will fail it for good.

## Exponential backoff so you stop hammering a dying service

Retrying instantly is often the wrong move. If an API is rate-limiting you, retrying in the same second just burns another attempt. **Backoff** spaces out retries.

A single integer sets a fixed delay in seconds. An array gives you **exponential backoff** — a growing delay per attempt:

```php
/**
 * Seconds to wait before the 1st, 2nd, and 3rd+ retry.
 */
public function backoff(): array
{
    return [10, 30, 120];
}
```

With this, the first retry waits 10 seconds, the second 30, the third 120. If there are more attempts than array entries, Laravel reuses the last value. You can also declare a static `public $backoff = 60;` property if a flat delay is enough.

Backoff pairs naturally with `retryUntil()`: give the job a generous window and let growing delays smooth out a temporary outage instead of pounding it.

## Handling failure explicitly with failed()

When a job exhausts its retries, Laravel calls the job's `failed()` method if it exists. This is where you alert, compensate, or clean up:

```php
use Throwable;

public function failed(?Throwable $exception): void
{
    Log::error('Invoice sync failed permanently', [
        'invoice' => $this->invoiceId,
        'error' => $exception?->getMessage(),
    ]);

    // notify the team, mark the record, etc.
}
```

Do not put retry logic in here; by the time `failed()` runs, retries are over. Use it for the "we truly gave up, now what" path. Pairing this with a Slack or PagerDuty notification means a full `failed_jobs` table never goes unnoticed.

## Common pitfalls

- **No `--tries` set.** Workers default to a single attempt; one transient blip fails the job permanently. Always set `--tries` or `$tries`.
- **Editing a job after it failed, then retrying.** `queue:retry` deserializes the *old* payload. Model IDs are re-fetched, but constructor arguments are frozen at dispatch time.
- **Retrying with no worker running.** The job goes back on the queue and sits there. Confirm a worker is consuming that queue.
- **Forgetting to restart workers after a deploy.** Long-running workers hold old code in memory. Run `php artisan queue:restart` in your deploy script (Horizon handles this for you).
- **Treating `$tries` and `$maxExceptions` as the same thing.** They count different events. Mixing them up leads to jobs that retry far more or far less than you expect.
- **Deleting a soft-related model.** If a job references a model that was deleted, add `public bool $deleteWhenMissingModels = true;` so it fails cleanly instead of throwing a `ModelNotFoundException` on every retry.

## Where this fits in a bigger queue setup

Retry configuration is the foundation, but it connects to a few neighbors. **Laravel Horizon** gives you a dashboard over Redis queues, including a one-click retry for failed jobs and metrics on throughput. It is the friendlier face of everything covered here. If you are not on Redis, plain **Supervisor** keeps your `queue:work` processes alive. And when you dispatch related jobs together with **job batching**, the batch exposes its own `catch()` and retry semantics on top of per-job retries.

You do not need any of those to retry jobs correctly. But once your queues get busy, Horizon's retry button beats hunting UUIDs on the command line.

## FAQ

### What is the difference between queue:retry all and queue:flush?

`queue:retry all` re-queues every failed job so workers can attempt them again, and removes them from `failed_jobs`. `queue:flush` deletes every failed job permanently without re-running anything. Use `retry` to give jobs another chance; use `flush` only when you have accepted the failures.

### Why does my failed job not retry even after running queue:retry?

`queue:retry` only pushes the job back onto its queue; it does not execute it. You need a worker (`php artisan queue:work`) actively consuming that queue. If the worker listens to a different queue than the one the job is on, use `--queue=` to target the right one, or point the worker at it.

### How do I set a maximum retry time instead of a number of attempts?

Add a `retryUntil()` method to the job returning a `DateTime`. The job retries until that moment regardless of how many attempts it takes. Because `retryUntil()` takes precedence over `$tries`, you don't need to touch `$tries` at all when a deadline is set; it won't be consulted until the deadline passes. A `$maxExceptions` limit still bites, though, so keep that in mind if you want a hard exit on repeated real errors.

### Does $backoff apply to manual retries via queue:retry?

Backoff governs the delay between *automatic* attempts within a single job lifecycle. When you run `queue:retry`, the job is pushed back immediately and treated as a fresh run, so the first `backoff` value applies only if it fails again and retries automatically from there.

## Conclusion

Reliable queues come from setting retry limits on the job, spacing attempts with exponential backoff, and defining a `failed()` path for the ones that still die, then using `queue:retry` as the manual recovery lever when an outage floods `failed_jobs`. Start by adding an explicit `$tries` and a `backoff()` array to your most fragile job today, and wire `queue:restart` into your deploy so workers never run stale code. Once that is solid, install Horizon and get the retry dashboard for free.