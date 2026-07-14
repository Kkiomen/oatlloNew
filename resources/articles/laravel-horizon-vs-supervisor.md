---
name: "Laravel Horizon vs Supervisor: Queue Setup Guide"
slug: laravel-horizon-vs-supervisor
short_description: "Laravel Horizon vs Supervisor explained: what each one does, when to use them, and why most Redis setups run Horizon under Supervisor."
language: en
published_at: 2026-07-10 09:00:00
is_published: true
tags: [laravel, queues, horizon, supervisor, redis]
---

If you have ever searched **Laravel Horizon vs Supervisor**, you probably framed it as an either/or decision. It usually isn't. The two tools solve different problems, and on a lot of production servers they end up working together rather than competing. Supervisor is an operating-system process manager. Horizon is a Redis-only dashboard and configuration layer that sits on top of your queue workers. Once that clicks, the rest of the decision is easy.

Get that distinction wrong and you either bolt Horizon onto a database queue that will never run it, or you leave a bare `horizon` command with nothing to restart it after a reboot. Both are things I've fixed on other people's servers. Here's the config for each, and a straight rule for choosing.

## What Supervisor actually does

Supervisor is a process control system written in Python. It has nothing to do with Laravel specifically. Its job is simple and boring in the best way: start a process, watch it, and restart it if it dies.

That matters because `php artisan queue:work` is a long-running process. It boots the framework once and then loops, pulling jobs off the queue. If it crashes, hits a fatal error, or you deploy new code and stop it, the worker is just gone. Nothing pulls jobs anymore. Supervisor is what keeps that process alive.

A typical worker program looks like this:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
directory=/var/www/app
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker.log
stopwaitsecs=3600
```

A few things worth calling out:

- **`numprocs=4`** runs four independent worker processes. Supervisor treats each as a separate managed program.
- **`autorestart=true`** is the reason Supervisor exists. Worker dies, Supervisor brings it back.
- **`stopwaitsecs=3600`** gives a worker up to an hour to finish its current job before Supervisor force-kills it. Set this longer than your slowest job so deploys don't chop a job in half.

After editing the config you reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

That's the whole Supervisor story: it works with **any** queue driver Laravel supports (database, Redis, SQS, Beanstalkd) because it doesn't know or care what your workers are doing. It only knows how to keep a command running.

## What Horizon actually does

Horizon is a first-party Laravel package. It does not replace Supervisor's core role; it replaces the raw `queue:work` command with its own supervised worker pool that you configure in PHP, plus a real-time dashboard.

Install it with Composer and publish its assets:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Instead of scattering `--queue`, `--tries`, and `--timeout` flags across shell commands, you define everything in `config/horizon.php`:

```php
<?php

return [
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'emails', 'exports'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 20,
                'balance' => 'auto',
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
```

The payoff:

- **`balance => 'auto'`** lets Horizon scale worker processes up and down based on queue load. Busy queue gets more workers; idle queue releases them. Plain `queue:work` can't do this.
- **Per-environment config** means production and local can behave differently from one file, tracked in Git.
- The **dashboard** at `/horizon` shows throughput, runtime, failed jobs, tags, and recent job payloads. You get metrics instead of grepping logs.

The catch that trips people up: **Horizon requires the Redis queue driver.** If your `QUEUE_CONNECTION` is `database` or `sqs`, Horizon simply won't run against it. That's not a bug you can work around — Horizon leans on Redis data structures for its balancing and metrics. No Redis, no Horizon.

You start it with a single command:

```bash
php artisan horizon
```

Which raises the obvious question.

## The part everyone misses: Horizon still needs a supervisor

`php artisan horizon` is *also* a long-running process. If it crashes or the server reboots, it stays down until something restarts it. Horizon manages your workers, but nothing manages Horizon.

So on most production servers you run **Horizon under Supervisor**. Supervisor keeps the `horizon` command alive; Horizon keeps the workers balanced. One Supervisor program, not one per worker:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/app/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/horizon.log
stopwaitsecs=3600
```

Note there's no `numprocs` here — Horizon spawns and balances the worker processes itself, so you only supervise the single master command. For zero-downtime deploys, call `php artisan horizon:terminate` in your deploy script; Horizon finishes the current jobs, exits gracefully, and Supervisor restarts it with the new code.

This is the mental model to keep: it's rarely Horizon *instead of* Supervisor. It's Horizon *on top of* Supervisor.

## Laravel Horizon vs Supervisor: side-by-side

| | Supervisor (bare `queue:work`) | Laravel Horizon |
|---|---|---|
| **What it is** | OS-level process manager | Laravel package: worker pool + dashboard |
| **Keeps processes alive** | Yes, this is its whole job | No — needs a process manager itself |
| **Queue drivers** | Any (database, Redis, SQS, Beanstalkd) | Redis only |
| **Config location** | `.conf` files + CLI flags | `config/horizon.php` (in Git) |
| **Auto-scaling workers** | No (fixed `numprocs`) | Yes (`balance => auto`) |
| **Monitoring / metrics** | Logs only | Web dashboard: throughput, runtime, failures |
| **Failed job insight** | `queue:failed` CLI | Dashboard with payload + retry button |
| **Setup effort** | Low (one `.conf`) | Medium (Composer + config + still needs Supervisor) |
| **Best fit** | Non-Redis queues, minimal stacks | Redis-backed apps that want visibility |

## When to use which

Reach for **plain Supervisor + `queue:work`** when:

- Your queue driver isn't Redis and you have no plans to switch. A database queue on a low-traffic app is perfectly fine.
- You want the smallest possible moving-parts count. No dashboard, no extra package.
- You're on serverless or a platform where a persistent dashboard doesn't fit the model.

Reach for **Horizon (under Supervisor)** when:

- You're already on Redis, or can move to it without pain.
- Job volume is high or spiky enough that fixed worker counts waste money or fall behind. Auto-balancing earns its keep here.
- You want to *see* what the queue is doing — a designer asking "did my export run?" can check the dashboard instead of pinging you to read logs.

One honest trade-off from real setups: Horizon's dashboard is genuinely useful, but it adds Redis memory pressure because it stores recent job data and metrics for the retention window you configure. On a memory-tight box, watch your `trim` settings in `config/horizon.php`. It's not free.

## How this connects to failed jobs and batches

Whichever setup you land on, the queue features underneath are the same Laravel primitives. If jobs are dying and you need them back, the retry mechanics don't change between Supervisor and Horizon. The difference is that Horizon gives you a button and Supervisor gives you a CLI command. We cover the full workflow in [handling failed jobs in Laravel queues](/blog/laravel-retry-failed-jobs).

Likewise, if you're dispatching large groups of related jobs, [job batching](/blog/laravel-job-batching) works identically under both, but Horizon's dashboard shows batch progress visually, which is a real quality-of-life win when a batch has thousands of jobs.

## FAQ

### Do I need Supervisor if I use Horizon?

In practice, yes. `php artisan horizon` is a long-running process that will not restart itself after a crash or reboot. Supervisor (or systemd) keeps it running. Horizon manages your workers; something still has to manage Horizon.

### Can Horizon work with a database or SQS queue?

No. Horizon only supports the Redis queue driver. If you can't or won't use Redis, stick with `queue:work` under Supervisor, which works with any driver Laravel supports.

### Is Horizon slower than plain queue workers?

The workers themselves aren't slower. Horizon runs the same queue processing. The overhead is the metrics and job data it writes to Redis for the dashboard. On most apps that's negligible; on memory-constrained servers, tune the retention/trim settings.

### Can I run both Supervisor workers and Horizon at once?

You can, but avoid pointing both at the same Redis queues: they'll compete for the same jobs and your metrics will be misleading. If you mix them, give each its own dedicated queues.

## Conclusion

The framing of "Laravel Horizon vs Supervisor" is a bit of a false choice. Supervisor is the process manager that keeps things alive; Horizon is a Redis-only layer that gives you balanced worker pools and a dashboard — and it needs a process manager of its own to survive a reboot. On a non-Redis or minimal stack, plain Supervisor is the right, lean answer. On a Redis-backed app with real job volume, run Horizon under Supervisor and take the visibility.

**Next step:** check your `QUEUE_CONNECTION`. If it's already `redis`, install Horizon on a staging box, point Supervisor at the `horizon` command, and watch the dashboard for a day before deciding. The setup takes about fifteen minutes and the metrics will tell you fast whether the auto-balancing helps your workload.