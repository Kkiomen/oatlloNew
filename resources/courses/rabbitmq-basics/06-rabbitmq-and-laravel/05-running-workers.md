---
title: "Running workers"
slug: running-workers
seo_title: "Running Laravel Queue Workers on RabbitMQ"
seo_description: "Run php artisan queue:work rabbitmq to consume jobs from RabbitMQ. Learn how the worker acks messages, the --queue flag, and keeping it alive with Supervisor."
---

## Start a worker

A dispatched job sits in RabbitMQ doing nothing until a **worker** consumes it. To run a
Laravel queue worker on RabbitMQ, start one with:

```bash
php artisan queue:work rabbitmq
```

The argument `rabbitmq` is the **connection** name from `config/queue.php`. The worker
connects to the broker, subscribes to a queue, and runs each job's `handle()` method as
messages arrive. It is a long-running process: it stays up and processes job after job
until you stop it.

Because RabbitMQ pushes messages to consumers, the worker doesn't poll a table the way the
database driver does - it waits and the broker delivers, which is why pickup feels
instant.

## How the worker acks a job

This is the part that [ties back to Chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements). When the worker takes a message, RabbitMQ
marks it unacknowledged - held, but not gone. If `handle()` finishes without throwing, the
worker sends an **ack** and the broker deletes the message. If `handle()` throws, the
worker does not ack; the job is marked failed or released back for another try, depending
on your retry settings.

The safety property you learned earlier applies directly: if the worker process is killed
mid-job, before it acks, RabbitMQ still has the message and re-delivers it to another
worker. A crash does not silently lose work. Retries and failures are the
[next lesson](/course/rabbitmq-basics/rabbitmq-and-laravel/failed-jobs-and-retries).

## Pick which queue to consume

By default the worker consumes the connection's default queue. Point it at a specific
queue with `--queue`:

```bash
php artisan queue:work rabbitmq --queue=emails
```

Now this worker only handles jobs on the `emails` queue. Run separate workers for separate
queues, and you can scale each kind of work independently.

## work vs listen, and reloading code

Use `queue:work`, not the older `queue:listen`. `work` is far more efficient because it
boots the framework once and reuses it. The trade-off is that a long-running worker holds
your **code in memory** - so after you deploy new code, the old worker keeps running the
old code. Restart workers on every deploy:

```bash
php artisan queue:restart
```

This tells running workers to finish their current job and exit gracefully; your process
manager then starts fresh ones.

Worth knowing how that signal travels: `queue:restart` writes a timestamp to the **cache**,
and each worker checks it between jobs. If your cache driver is misconfigured - or set to
`array`, which is per-process and shared with nothing - the signal never reaches the
worker, and it keeps serving stale code while the deploy "looks" done. When a restart
seems ignored, check the cache before blaming the worker.

## Keep it alive with Supervisor

In production you never run `queue:work` in a terminal by hand - if it stops, jobs stop.
On Linux, **Supervisor** keeps the worker running and restarts it if it dies. A minimal
config:

```ini
[program:laravel-worker]
command=php /var/www/app/artisan queue:work rabbitmq --queue=default --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
stopwaitsecs=3600
```

`numprocs=2` runs two worker processes for more throughput; RabbitMQ shares the queue
fairly between them. After changing the config, reload Supervisor with
`supervisorctl reread`, `supervisorctl update` and `supervisorctl start laravel-worker:*`.
Deeper production operation is Chapter 7.

## Common mistake

The classic mistake is dispatching jobs and never starting a worker - messages pile up in
RabbitMQ and nothing runs. Check the management UI: if the queue's "Ready" count keeps
climbing, no worker is consuming it. The second mistake is deploying new code but not
restarting the worker, so it keeps running the old version. Run `php artisan queue:restart`
after every deploy.

## FAQ

### What does the argument after queue:work mean?

It's the connection name, so `queue:work rabbitmq` uses the `rabbitmq` connection from
`config/queue.php`. If you omit it, the worker uses your default `QUEUE_CONNECTION`.

### Do I need to call queue:restart after deploying?

Yes. A `queue:work` worker keeps compiled code in memory, so it won't pick up new code
until it restarts. `php artisan queue:restart` signals workers to exit gracefully so your
process manager launches fresh ones.

### How do I run more than one worker?

Start multiple `queue:work` processes, or set `numprocs` in Supervisor. RabbitMQ delivers
each message to only one of them and balances load across them, exactly like the [work
queues from Chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/work-queues).
