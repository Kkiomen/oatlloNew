---
title: "Sessions and queues on Redis"
slug: sessions-and-queues-on-redis
seo_title: "Laravel Sessions and Queues on Redis: the .env setup"
seo_description: "Run Laravel sessions and queues on Redis with SESSION_DRIVER=redis and QUEUE_CONNECTION=redis. Why Redis fits both, plus dispatch and queue:work."
---

## Sessions and queues, both on Redis

You have used Redis as a cache. That same server makes a great home for two more Laravel systems: **sessions** and **queues**. Running Laravel sessions and queues on Redis is one line of `.env` each, and both work for reasons you already know. Redis is fast, it is shared across every server, and it expires keys on its own.

## Sessions on Redis with SESSION_DRIVER

A session holds per-user data between requests, like the logged-in user and flash messages. By default Laravel stores this in files or the database. Point it at Redis instead:

```ini
SESSION_DRIVER=redis
```

That is the whole change. Every session now lives in Redis.

Why Redis fits sessions well:

- **Fast.** Sessions are read on almost every request, and Redis reads are in-memory quick.
- **Shared.** If you run more than one web server, they all read the same sessions. File sessions would be stuck on one machine.
- **Self-expiring.** Sessions have a lifetime, and Redis drops expired keys for you (the TTL you learned earlier), so there is no cleanup job to run.

## Queues on Redis with QUEUE_CONNECTION

A queue lets you push slow work (sending email, processing an upload) into the background so the user does not wait. Set the queue connection to Redis:

```ini
QUEUE_CONNECTION=redis
```

Now dispatched jobs are stored in Redis until a worker picks them up. Dispatching a job looks like this:

```php
SendWelcomeEmail::dispatch($user);
```

That call returns instantly. The job sits in Redis, waiting. To actually run queued jobs, start a worker in a terminal:

```bash
php artisan queue:work
```

The worker pulls jobs off Redis one at a time and runs them. Leave it running and it handles each job as it arrives.

Why Redis fits queues well:

- **Fast push and pop.** Redis list operations are quick, so dispatching and pulling jobs adds almost no overhead.
- **Shared.** Many workers on many machines can pull from the same Redis queue.
- **Persistent enough.** Jobs stay in Redis until a worker finishes them, so they are not lost the moment they are queued.

One detail that surprises people: the cache lives on its own Redis database (`1`), but sessions and queues default to the `default` connection on database `0`, right next to any keys you set through the `Redis` facade. They still never collide, because every key carries the `REDIS_PREFIX` and its own naming. So `php artisan cache:clear` leaves your sessions and queued jobs completely untouched - it only flushes the cache database.

## There is much more to queues

This lesson only shows you the switch. Queues are a big topic on their own: delayed jobs, retries, failed jobs, multiple queues, and running workers in production. All of that gets a proper treatment in the next chapter. Here you just need to know that `QUEUE_CONNECTION=redis` makes Redis the backend.

## Common mistake

Changing `QUEUE_CONNECTION=redis` and expecting jobs to run on their own. They do not. A queue only stores the work. Nothing happens until a worker is running:

```bash
php artisan queue:work
```

If you dispatch jobs and nothing seems to happen, check that a worker is actually running. The other classic trap is editing `.env` while a worker is already up. A worker loads its config once at boot, so restart it after any config change. Run `php artisan queue:restart` to have workers gracefully finish and reload.

## FAQ

### Do I need to run anything for sessions on Redis?

No. Unlike queues, sessions need no worker. Set `SESSION_DRIVER=redis` and Laravel reads and writes sessions on Redis automatically.

### Can sessions, cache, and queues all share one Redis?

Yes. One Redis server handles all three. Laravel keeps them apart by connection and key prefix, so they do not collide.

### Why does the queue worker have to keep running?

The worker is the process that pulls jobs from Redis and executes them. Redis only holds the jobs. In production you keep the worker alive with a process manager like Supervisor, which the next chapter covers.
