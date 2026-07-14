---
name: "Laravel Job Batching for Large Background Jobs"
slug: laravel-job-batching
short_description: "A practical guide to Laravel job batching: dispatching batches, then/catch/finally callbacks, progress tracking, and cancelling running batches."
language: en
published_at: 2026-08-03 09:00:00
is_published: true
tags: [laravel, queues, php, background-jobs]
---

The first time I had to import a 400,000-row CSV into a Laravel app, I did the naive thing: one giant queued job that looped over every row. It ran for eleven minutes, timed out on a memory limit near the end, and left me with no idea how far it had actually gotten. That is exactly the problem **Laravel job batching** solves. Instead of one monolithic job, you dispatch a *group* of small jobs, track them as a single unit, and react when the whole group finishes, or when one of them blows up.

This guide walks through the whole feature: what batching is, how to dispatch a batch, the `then` / `catch` / `finally` callbacks, progress tracking, adding jobs to a running batch, and cancelling one mid-flight. Everything here is runnable, and I'll flag the pitfalls that cost me real time.

## What Laravel job batching actually is

A batch is a collection of queued jobs that Laravel tracks together. Each job runs independently on your workers, but the framework keeps a shared record — how many jobs there are, how many finished, how many failed — in the database. That shared record is what lets you register callbacks that fire when the *group* reaches a certain state, not just when a single job ends.

Two things are non-negotiable before you write any batch code:

- **Your jobs need the `Batchable` trait.** Without it, the job has no reference back to the batch record.
- **You need the batch metadata tables.** Batching stores its bookkeeping in `job_batches` (and a `batches` table exists in older setups). Publish and run the migration:

```bash
php artisan make:queue-batches-table
php artisan migrate
```

One more constraint that trips people up: **batching does not work on the `sync` driver.** The whole point is a shared, persistent record that multiple workers update concurrently. You need a real queue driver that supports batching: `database`, `redis`, or a compatible cloud queue. If you dispatch a batch while `QUEUE_CONNECTION=sync`, you'll get errors or silently wrong behavior. Set a proper connection first.

Here's a minimal batchable job:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportRows implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public array $rows) {}

    public function handle(): void
    {
        // If the batch was cancelled, stop early instead of doing work.
        if ($this->batch()->cancelled()) {
            return;
        }

        foreach ($this->rows as $row) {
            // ... persist the row
        }
    }
}
```

The `$this->batch()` call is available only because of the `Batchable` trait. It returns the live batch record, which is how a job knows whether the batch it belongs to has been cancelled.

## Dispatching a batch

You build a batch with the `Bus` facade. Pass an array of jobs (or nested arrays, more on that below), chain your callbacks, and finish with `dispatch()`:

```php
use App\Jobs\ImportRows;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

$batch = Bus::batch([
    new ImportRows($chunkA),
    new ImportRows($chunkB),
    new ImportRows($chunkC),
])->then(function (Batch $batch) {
    // All jobs completed successfully.
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure detected.
})->finally(function (Batch $batch) {
    // The batch has finished executing.
})->name('CSV import')->dispatch();

return $batch->id;
```

`Bus::batch()` returns a `Batch` object *before* the jobs run. Grab `$batch->id` and store it somewhere (a `dashboards` row, cache, wherever) so you can look the batch up later for progress checks. Retrieve it anytime with `Bus::findBatch($id)`.

A note on structure: **the array you pass defines how jobs run in parallel vs. in sequence.** A flat array of jobs runs them all in parallel. If you nest an array inside, that inner array becomes a *chain* that runs serially, while sitting alongside the other top-level entries. That is handy when some work must happen in order but the rest can fan out.

## then, catch, and finally callbacks

These three callbacks are the heart of why batching is nicer than firing off loose jobs. Their timing matters and it's easy to get the mental model wrong:

- **`then`** runs only if **every** job in the batch completed without throwing. This is where you send the "import finished" email or flip a status to `done`.
- **`catch`** runs on the **first** failed job within the batch. It receives the `Throwable`. Note the word *first*. It fires once, when the initial failure is detected, not once per failing job.
- **`finally`** runs when the batch has finished executing, **regardless** of success or failure. Use it for cleanup: delete the temp upload file, release a lock, unset a "processing" flag.

An important detail about the closures: they are serialized and executed by a queue worker later, **not** in the web request that dispatched the batch. So `$this` from your controller is not available inside them, and you shouldn't rely on request-scoped state. Pass what you need through the constructor of your jobs or reference the `$batch` argument.

### Letting jobs fail without killing the batch

By default, **a single failed job cancels the batch**. Remaining jobs won't run, and `catch` fires. Sometimes that's wrong. If you're processing 10,000 independent images and three are corrupt, you probably want the other 9,997 to finish anyway. Opt into that with `allowFailures()`:

```php
$batch = Bus::batch($jobs)
    ->allowFailures()
    ->then(fn (Batch $batch) => report_done())
    ->dispatch();
```

With `allowFailures()`, failed jobs are recorded but do not cancel the batch, so the rest keep processing. Keep in mind the trade-off: because failures don't stop the batch, **`then` will not fire if any job ultimately failed**. A batch with failures never counts as fully successful. Check `$batch->hasFailures()` and `$batch->failedJobs` afterward to decide what to do.

## Tracking batch progress

This is the payoff for that painful CSV story. Once you have a batch object (via `Bus::findBatch($id)`), it exposes a clean set of read-only properties and helpers:

```php
$batch = Bus::findBatch($id);

$batch->totalJobs;      // total jobs in the batch
$batch->pendingJobs;    // jobs not yet finished
$batch->processedJobs();// totalJobs - pendingJobs
$batch->failedJobs;     // number that failed
$batch->progress();     // integer percentage 0–100
$batch->finished();     // bool: has the batch completed?
$batch->cancelled();    // bool: was it cancelled?
```

`progress()` returns a whole-number percentage, which is exactly what a front-end progress bar wants. A simple status endpoint looks like this:

```php
public function status(string $id)
{
    $batch = Bus::findBatch($id);

    abort_if($batch === null, 404);

    return response()->json([
        'progress'  => $batch->progress(),
        'finished'  => $batch->finished(),
        'failed'    => $batch->failedJobs,
        'cancelled' => $batch->cancelled(),
    ]);
}
```

Poll that endpoint from the client every two or three seconds and you have a live progress bar. No websockets, no extra infrastructure, just a row in `job_batches` that the workers keep updating for you.

## Adding jobs to a running batch

Sometimes you don't know all the work up front. A classic case: a "dispatcher" job that reads a source in pages and, for each page, queues more jobs into the same batch. You add jobs from *inside* a batched job using `$this->batch()->add()`:

```php
public function handle(): void
{
    if ($this->batch()->cancelled()) {
        return;
    }

    $this->batch()->add([
        new ImportRows($nextChunk),
        new ImportRows($anotherChunk),
    ]);
}
```

The catch — and it's a real one — is that **you can only add jobs from within a job that already belongs to the batch.** You cannot append to a batch from outside once it's running. Because of that, the batch's `finally`/`then` callbacks correctly wait for the newly added jobs too, since the batch isn't "finished" while a member job is still queuing more work. Structure this so your dispatcher job is part of the initial batch.

## Cancelling a batch

To stop a batch, call `cancel()` on the batch instance:

```php
$batch = Bus::findBatch($id);
$batch->cancel();
```

Here's the part people miss: **cancelling does not forcibly kill jobs that are already running or queued.** It sets a flag. Jobs that have not started yet will still be picked up by a worker, so each job must *check* whether the batch is cancelled and bail out itself. That's why the guard at the top of `handle()` matters:

```php
public function handle(): void
{
    if ($this->batch()->cancelled()) {
        return;
    }

    // real work here
}
```

Even cleaner: add the `SkipIfBatchCancelled` middleware (available in recent Laravel versions) so the framework skips the job for you instead of writing the guard by hand. Either way, without that check, a cancelled batch keeps chewing through queued jobs.

## Pitfalls worth remembering

- **`sync` driver won't work.** Use `database`, `redis`, or another supported connection.
- **Forgetting the `Batchable` trait** means `$this->batch()` throws. It's the most common first error.
- **Missing migration**: no `job_batches` table, no batching. Run `make:queue-batches-table` then `migrate`.
- **Callbacks run on a worker, not the request.** Don't close over request-scoped objects or `$this` from a controller.
- **Cancel is cooperative.** Jobs must check `cancelled()`; cancelling alone doesn't halt queued work.
- **`then` won't fire with `allowFailures()` if anything failed.** Inspect `hasFailures()` instead of assuming success.
- **Store the batch ID.** If you lose it, you lose your handle on progress and cancellation.
- **Prune old batch rows.** The `job_batches` table grows forever otherwise; schedule `queue:prune-batches`.

## FAQ

### Do I need Horizon to use job batching?
No. Batching is a core queue feature and works with any batching-capable driver and a plain `queue:work` worker under Supervisor. Horizon is just a nicer dashboard and manager for Redis queues. If you're weighing your options, see our post comparing running queues with Horizon vs. Supervisor.

### What happens to a batch when a job fails?
By default the batch is cancelled, remaining jobs are skipped, and `catch` fires with the exception. The failed job also goes to your `failed_jobs` table like any other failed job, so you can retry it. If you want the batch to continue despite failures, call `allowFailures()`.

### Can I retry the failed jobs from a batch?
Yes. Failed batch jobs land in `failed_jobs` like any other failure, so you retry them the usual way: by UUID (`php artisan queue:retry 9f8e...`) or all at once with `php artisan queue:retry all`. There's no "retry this whole batch" command; you're retrying the individual failed jobs. Grab the failed UUIDs from `$batch->failedJobIds` if you want to script it. For the full workflow, read our guide on retrying failed queue jobs.

### How do I clean up old batch records?
Run `php artisan queue:prune-batches` on a schedule. By default it removes finished batches older than 24 hours; pass `--hours` and `--unfinished`/`--cancelled` to tune what gets pruned.

## Conclusion

Laravel job batching turns "one huge fragile job" into a group of small jobs you can watch, react to, and control. The recipe is short: add the `Batchable` trait, run the batch migration, pick a real queue driver, then `Bus::batch([...])->then()->catch()->finally()->dispatch()`. Store the returned batch ID, poll `progress()` for a live bar, guard against `cancelled()` in every job, and reach for `allowFailures()` when partial success is acceptable.

Go back to whatever "giant loop job" you've been avoiding and split it into chunks behind a batch. The first time you watch a real progress bar climb to 100%, and get a clean `then` callback at the end instead of a timeout, you won't write a monolithic import job again.