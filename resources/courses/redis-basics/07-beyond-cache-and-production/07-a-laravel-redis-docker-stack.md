---
title: "A Laravel + Redis Docker stack"
slug: a-laravel-redis-docker-stack
seo_title: "Laravel Redis Docker Compose Stack (php-fpm + worker)"
seo_description: "A full annotated docker-compose.yml for Laravel with a php-fpm app, redis:7, and a queue worker - plus the .env wiring and how services find each other."
---

You have learned the pieces one at a time: running
[Redis with Docker](/course/redis-basics/getting-started/run-redis-with-docker),
[connecting it to Laravel](/course/redis-basics/redis-and-laravel/connecting-redis-to-laravel),
using it as the [cache driver](/course/redis-basics/redis-and-laravel/redis-as-cache-driver),
and running [background jobs](/course/redis-basics/beyond-cache-and-production/queues-and-background-jobs).
This lesson is the capstone: one Laravel Redis Docker Compose file that runs all of it
together in a single `docker-compose.yml`, with every part explained.

## The full docker-compose.yml

Here is the whole stack. Read it once top to bottom, then we will walk through each part.

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    depends_on:
      - redis
    networks:
      - app-net

  redis:
    image: redis:7
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - app-net

  worker:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan queue:work redis --tries=3
    volumes:
      - .:/var/www/html
    depends_on:
      - redis
    networks:
      - app-net

volumes:
  redis-data:

networks:
  app-net:
```

Three services (`app`, `redis`, `worker`), one named volume, one network. That is the
whole production shape of a Laravel app that uses Redis. Now the walkthrough.

## The app service (php-fpm)

```yaml
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    depends_on:
      - redis
    networks:
      - app-net
```

This is your Laravel application running PHP-FPM. Instead of a ready-made `image:`, it
uses `build:` to build from your own `Dockerfile` (where you install PHP, the `redis`
extension or `predis`, and your dependencies). The `volumes` line mounts your project
code into the container at `/var/www/html`, so edits on your machine show up inside.

`depends_on: redis` tells Compose to start the `redis` service first. Note this only
controls **start order**, not readiness - your app should still retry the connection if
Redis is a moment slow to accept clients. If you want Compose to wait for Redis to be
genuinely ready, give the redis service a `healthcheck` that runs `redis-cli ping` and
change this to the long form `depends_on: { redis: { condition: service_healthy } }`.
Plain `depends_on` fires the moment the container starts, before Redis is answering.

## The redis service

```yaml
  redis:
    image: redis:7
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - app-net
```

This runs the official `redis:7` image - no custom build needed. The `command` overrides
the default startup so Redis requires a password, reading it from the `REDIS_PASSWORD`
environment variable (the `${...}` syntax pulls it from your `.env` file next to the
compose file). This is the `requirepass` setting from
[securing Redis](/course/redis-basics/beyond-cache-and-production/securing-redis),
applied on the command line.

Notice there is **no `ports:` line**. We deliberately do not publish `6379` to the host.
Redis is reachable only by the other containers on `app-net`, which is exactly the
private-network safety you want in production. If you need to poke at it locally during
development, you can add `ports: ["6379:6379"]` temporarily - but never on a public
server.

## The redis-data named volume

```yaml
  redis:
    ...
    volumes:
      - redis-data:/data
```

```yaml
volumes:
  redis-data:
```

Redis writes its persistence files (RDB snapshots and the AOF log, from the
[persistence lesson](/course/redis-basics/beyond-cache-and-production/persistence-rdb-aof))
to `/data`. A container's own filesystem is thrown away when the container is recreated,
so we mount a **named volume** there. The volume lives outside the container and survives
`docker compose down` and image rebuilds, so your data is still there after a restart or
an upgrade. The bottom-level `volumes: redis-data:` block is what declares that named
volume to Compose.

## The worker service

```yaml
  worker:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan queue:work redis --tries=3
    volumes:
      - .:/var/www/html
    depends_on:
      - redis
    networks:
      - app-net
```

Queued jobs need a process that pulls them off the queue and runs them. That is what
`php artisan queue:work` does, and it must run as its own long-lived process - separate
from the web request cycle. So we build the **same image** as `app` (same code, same
dependencies) but give it a different `command`: run the worker instead of PHP-FPM. It
reads jobs from the `redis` queue connection and retries a failed job up to three times.

Running it as a separate service means you can scale or restart your workers without
touching the web app, and vice versa.

## How the services find each other

This is the part that trips people up, so it gets its own section. Inside a Docker
Compose network, **each service is reachable by its service name as a hostname.** Docker
runs an internal DNS that resolves those names to the right container.

So from the `app` and `worker` containers, the Redis host is literally `redis` - the name
of the service in the compose file. Not `localhost`, not an IP address. `localhost`
inside the `app` container means the `app` container itself, where no Redis is running.
This is the single most common Docker + Redis mistake, and it is why the
[troubleshooting lesson](/course/redis-basics/beyond-cache-and-production/troubleshooting)
opens with `Connection refused`.

Because all three services share `networks: app-net`, they can all talk to each other by
name, and nothing outside that network can reach them.

## The .env wiring

The compose file builds the plumbing; your Laravel `.env` tells the app to use it:

```ini
REDIS_HOST=redis
REDIS_PASSWORD=a-long-random-password-not-this-one
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

`REDIS_HOST=redis` is the key line - it matches the **service name** from the compose
file, so Laravel connects to the right container over the private network. `REDIS_PASSWORD`
must match the one you passed to `redis-server --requirepass`. `CACHE_STORE=redis` sends
the cache to Redis (from the [cache driver lesson](/course/redis-basics/redis-and-laravel/redis-as-cache-driver)),
`QUEUE_CONNECTION=redis` sends jobs to Redis so the `worker` service can pick them up, and
`SESSION_DRIVER=redis` keeps sessions there too. The same `${REDIS_PASSWORD}` value feeds
both the redis service and the app, so define it once in `.env`.

## Bringing it up

```bash
docker compose up -d
```

That starts all three services in the background. Redis comes up first, then the app and
the worker connect to it by name. Check that everything is running:

```bash
docker compose ps
```

To confirm Redis itself is healthy, open a shell in the redis container and ping it:

```bash
docker compose exec redis redis-cli -a a-long-random-password-not-this-one ping
```

```text
PONG
```

## Common mistake

Setting `REDIS_HOST=localhost` (or `127.0.0.1`) in `.env` while running under Compose.
Inside the container, `localhost` is the container itself, so Laravel tries to connect to
a Redis that is not there and you get `Connection refused`. Under Docker Compose the host
is always the **service name** - here, `redis`.

## FAQ

### Why is the worker a separate service instead of part of the app container?

A container runs one main process. PHP-FPM serves web requests; `queue:work` is a
separate long-running loop. Splitting them lets you restart or scale workers on their own
and keeps a crash in one from taking down the other. They share the same built image, so
there is no code duplication.

### Do I need to publish the Redis port to use it?

No - and you should not on a server. Services on the same Compose network reach Redis by
name (`redis:6379`) without any `ports:` entry. Publishing the port only exposes Redis to
the host and, if the host is public, to the internet. See
[securing Redis](/course/redis-basics/beyond-cache-and-production/securing-redis).

### Will my cached data survive a restart?

Cache data, yes, as long as the `redis-data` named volume is intact - it persists across
`docker compose down` and rebuilds. Remember that cache is still cache: it can also be
evicted under memory pressure (see
[eviction policies](/course/redis-basics/beyond-cache-and-production/eviction-policies)),
so your app must always be able to rebuild a missing entry.
