---
title: "Failed jobs and retries"
slug: failed-jobs-and-retries
seo_title: "Laravel Failed Jobs and Retries on RabbitMQ"
seo_description: "Use --tries and --backoff, store dead jobs in the failed_jobs table, and re-run them with queue:retry. See how Laravel retries map to RabbitMQ redelivery."
---

## Jobs fail, so plan for it

A job can throw: an API is down, a record is missing, the network hiccups. Handling Laravel
failed jobs and retries well means a temporary failure doesn't lose work, and a permanent
failure isn't silently dropped. Laravel gives you both - retries for the transient case, a
**failed jobs** record for the terminal one. This builds directly on the [redelivery you
saw in Chapter 5](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive).

## Limit attempts with --tries

By default a worker will keep retrying a throwing job forever, which is dangerous - a
truly broken job loops endlessly. Set a maximum number of attempts:

```bash
php artisan queue:work rabbitmq --tries=3
```

Now Laravel runs a job up to three times. Each time `handle()` throws, the job is put back
for another attempt. After the third failure it is considered **failed** and moved aside.
You can also set the limit on the job class itself:

```php
class SendWelcomeEmail implements ShouldQueue
{
    public int $tries = 3;
}
```

## Wait between attempts with --backoff

Retrying instantly rarely helps - if an API is down, hammering it again in the same
millisecond just fails again. `--backoff` tells Laravel to wait before the next attempt:

```bash
php artisan queue:work rabbitmq --tries=3 --backoff=10
```

That waits 10 seconds between attempts. You can grow the delay per attempt with a property
on the job:

```php
public array $backoff = [10, 30, 60];
```

The first retry waits 10 seconds, the next 30, the next 60 - an ["exponential backoff"](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues) that
gives a struggling downstream service room to recover.

## The failed_jobs table

When a job exhausts its attempts, Laravel writes it to the `failed_jobs` database table
with the payload and the exception, so nothing is lost and you can inspect what happened.
Laravel 11 ships the migration by default; if the table doesn't exist yet, create it and
migrate:

```bash
php artisan make:queue-failed-table
php artisan migrate
```

The failed jobs table is a **database** record even though the queue itself is RabbitMQ.
That's deliberate: a failed job needs to be stored somewhere durable and queryable for you
to review later, and a database table is the natural home.

One hook is easy to miss: add a `failed(Throwable $e)` method to the job class and Laravel
calls it the moment the job lands in `failed_jobs`. It runs once, after the last attempt -
the right place to fire an alert or clean up a half-finished side effect, rather than
polling the table by hand.

## Inspect and retry failed jobs

List what failed and re-run it once the underlying problem is fixed:

```bash
php artisan queue:failed
php artisan queue:retry 5             # retry the failed job with this id
php artisan queue:retry all           # retry every failed job
php artisan queue:forget 5            # delete one failed job record
php artisan queue:flush               # delete all failed job records
```

`queue:retry` takes the stored payload and **re-dispatches** it back onto RabbitMQ, where
a worker picks it up again. It's the manual "try this again now" button.

## How Laravel retries map to RabbitMQ redelivery

You met redelivery in Chapter 5: when a consumer doesn't ack, the broker re-delivers the
message. Laravel's retry sits on top of that. When a job throws and still has attempts
left, the driver **releases** the message back to the queue so it can be delivered again -
that is the same redelivery mechanism, driven by Laravel's attempt counter rather than a
raw nack. When `--tries` is exhausted, Laravel stops releasing it and records it in
`failed_jobs` instead, so RabbitMQ isn't left redelivering a hopeless message forever. In
short: RabbitMQ provides the redelivery, Laravel decides when to stop.

## Common mistake

Running a worker without `--tries` (or a `$tries` property) means an always-failing job
retries endlessly, and it never reaches `failed_jobs` for you to see. Always cap attempts.
The other common surprise is a "Table 'failed_jobs' doesn't exist" error - run
`php artisan make:queue-failed-table` and migrate before you rely on failure logging.

## FAQ

### What's the difference between --tries and --backoff?

`--tries` is how many attempts a job gets before it's marked failed. `--backoff` is how
long to wait between those attempts. Use both: a small number of tries with a growing
backoff.

### Where do failed jobs go - RabbitMQ or the database?

The message leaves RabbitMQ once it's exhausted its attempts, and a record of it is
written to the `failed_jobs` database table. You review and retry from there with
`queue:failed` and `queue:retry`.

### Does queue:retry re-run the job immediately?

It re-dispatches the job back onto the queue. A worker then picks it up as soon as it's
free, so it runs again shortly after - not necessarily the same instant, but right away in
practice.
