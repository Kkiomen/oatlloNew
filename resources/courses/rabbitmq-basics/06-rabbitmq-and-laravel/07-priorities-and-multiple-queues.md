---
title: "Priorities and multiple queues"
slug: priorities-and-multiple-queues
seo_title: "Laravel Queue Priorities and Multiple Queues"
seo_description: "Route jobs to named queues with onQueue, then run queue:work --queue=high,low so a worker drains high-priority queues before lower ones in RabbitMQ."
---

## Why more than one queue

Laravel queue priorities and multiple queues solve a problem you hit the moment some jobs
matter more than others. So far every job went to a single queue. A password-reset email
should go out in seconds; a nightly report can wait a few minutes. Share one queue, and a
flood of slow report jobs makes the urgent email wait behind them. The fix is to put
different work on different **named queues** and let a worker drain the important one
first.

You already have the tools: `onQueue()` to route a job (Chapter 6, lesson 4) and `--queue`
to tell a worker what to consume (lesson 5). Here you combine them.

## Route jobs to different queues

Send each kind of work to its own queue when you dispatch:

```php
// urgent - front of the line
SendPasswordReset::dispatch($user)->onQueue('high');

// can wait
GenerateMonthlyReport::dispatch($account)->onQueue('low');
```

Now you have two queues in RabbitMQ, `high` and `low`, each holding a different kind of
job. The driver declares both on first publish, so there's nothing to set up in advance.

## Worker priority order

A single worker can watch several queues and drain them **in order**. List them
comma-separated, highest priority first:

```bash
php artisan queue:work rabbitmq --queue=high,low
```

The order is a strict priority. On each cycle the worker looks at `high` first: as long as
`high` has any job ready, it processes that. Only when `high` is empty does it take a job
from `low`. So a burst of report jobs on `low` never delays a password reset on `high` -
the urgent queue always gets served first.

That strictness cuts both ways. If `high` never fully empties - a steady trickle is
enough - a single `--queue=high,low` worker may never reach `low` at all, and those jobs
sit indefinitely. Strict priority is not fairness. When `low` still has to make progress
under sustained `high` load, that is the signal to give it its own worker, which the next
section covers.

```text
--queue=high,low

  high: [reset] [reset]      <- drained first, completely
  low:  [report] [report] [report]   <- only touched when high is empty
```

## One worker or several

The comma-separated form gives you priority with a single worker, which is simplest. When
`low` has heavy jobs you don't want blocking `high` at all, run **dedicated workers**
instead:

```bash
# a fast lane that only ever does urgent work
php artisan queue:work rabbitmq --queue=high

# a separate lane for the slow stuff
php artisan queue:work rabbitmq --queue=low
```

Now the two lanes run in parallel and can't starve each other - a long report job on `low`
can't hold up `high`, because a different process owns `high`. In Supervisor you'd define
two `program` blocks, one per queue, and give each the `numprocs` it needs. This is the
usual production shape: a small pool for urgent queues, a larger pool for bulk work.

## A note on true message priority

RabbitMQ also supports **priority inside a single queue** (a `x-max-priority` argument and
per-message priority levels), which is a different feature from named queues. Laravel's
`--queue=high,low` gives you priority by *ordering queues*, which is simpler to reason
about and enough for almost every app. Reach for in-queue message priorities only if you
specifically need fine-grained ordering within one queue; the multi-queue approach here is
the idiomatic Laravel way.

## Common mistake

Naming a queue in `onQueue()` but forgetting to give a worker that queue. If you dispatch
to `onQueue('high')` but every worker runs `--queue=default`, nothing ever consumes
`high`, and those jobs pile up unprocessed. The queue names in `onQueue()` and in
`--queue` must line up. When a specific queue's "Ready" count grows in the management UI,
check that some worker actually lists it.

## FAQ

### What does the order in --queue=high,low actually do?

It sets strict priority. The worker fully drains `high` before it takes anything from
`low`, rechecking `high` each cycle. Put your most time-sensitive queue first.

### Should I use one worker with multiple queues or separate workers?

Use one worker with `--queue=high,low` for simple priority. Use separate workers per queue
when heavy jobs on a low-priority queue must never block urgent work, since separate
processes run in parallel and can't starve each other.

### Is this the same as RabbitMQ message priorities?

No. This orders *whole queues*. RabbitMQ's `x-max-priority` orders *messages within one
queue* - a separate, more advanced feature. For typical Laravel apps, multiple named
queues with a priority order is the recommended approach.
