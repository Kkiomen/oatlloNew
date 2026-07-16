---
title: "Queues and background jobs"
slug: queues-and-background-jobs
seo_title: "Redis Queue in Laravel: Background Jobs with queue:work"
seo_description: "Set up a Laravel Redis queue: dispatch background jobs, run queue:work, and retry failed jobs with queue:retry. Why Redis lists fit a queue."
---

Some work does not need to happen while the user waits. Sending an email, resizing an
image, or calling a slow API can run in the background. Laravel calls this a queue, and a
Laravel Redis queue is one of the fastest ways to store that pending work until a worker
gets to it.

## Point Laravel's queue at Redis

You already have Redis wired into Laravel from
[Chapter 5](/course/redis-basics/redis-and-laravel/connecting-redis-to-laravel). Switching
the queue over is one line in your `.env`:

```ini
QUEUE_CONNECTION=redis
```

Now every job you dispatch is stored in Redis instead of the default `sync` or `database`
driver. Nothing else in your code has to change.

## Dispatch a job

A job is a small class that does one piece of work. Generate one with `php artisan
make:job SendWelcomeEmail`, then dispatch it from anywhere:

```php
use App\Jobs\SendWelcomeEmail;

SendWelcomeEmail::dispatch($user);
```

That call returns almost instantly. Laravel serialises the job, pushes it into Redis, and
your controller responds right away. The actual email is sent later, by a separate process.

## Run a worker with queue:work

Something has to pull jobs back out and run them. That is the worker:

```bash
php artisan queue:work
```

Leave it running. It watches the Redis queue, picks up each job in order, runs its
`handle()` method, and removes it when done. In production you run this under a process
manager like Supervisor so it restarts if it ever stops.

## Why Redis lists fit a queue

A queue is first in, first out: the oldest job runs next. That is exactly what a Redis
[list](/course/redis-basics/core-data-types/lists) gives you. Laravel pushes new jobs onto
one end and the worker pops them off the other. Both operations are fast and atomic, so two
workers never grab the same job. You already saw lists in Chapter 3; the queue is that data
type doing a real job.

One detail worth knowing: a *delayed* job (`SendWelcomeEmail::dispatch($user)->delay(60)`)
does not sit in the list. Laravel parks it in a sorted set scored by the time it should
run, then moves it into the list once that time passes. So the Redis queue driver is really
a list plus a sorted set working together, not a single structure.

## Failed jobs and retries

Jobs fail. An API times out, a record is missing, the network blips. When a job throws an
exception, Laravel can retry it a set number of times:

```bash
php artisan queue:work --tries=3
```

After the last attempt fails, the job is moved to a `failed_jobs` table so it is not lost.
You can inspect failures and push them back onto the queue once you have fixed the cause:

```bash
php artisan queue:failed
php artisan queue:retry all
```

`queue:retry all` re-queues every failed job; pass an id to retry just one.

## Common mistake

Changing a job's code and expecting the running worker to notice. A worker loads your code
once and keeps it in memory, so old jobs keep running the old logic. After any deploy or
code change, restart the worker:

```bash
php artisan queue:restart
```

This tells workers to finish their current job and shut down cleanly, ready to be started
again with fresh code.

## FAQ

### Do I need a worker running all the time?

Yes, for jobs to actually run. If no worker is running, jobs pile up in Redis and wait.
They are not lost, they just sit there until a worker picks them up.

### What is the difference between a queue and rate limiting?

They both use Redis but solve different problems. A queue defers work to run later;
[rate limiting](/course/redis-basics/beyond-cache-and-production/rate-limiting-with-redis)
counts requests to block abuse. Different patterns, same store.

### Where did the job go after I dispatched it?

Into a Redis list. You can peek at it with the
[redis-cli console](/course/redis-basics/managing-redis-from-the-console/the-redis-cli-console)
before a worker runs, though Laravel manages the exact key names for you.
