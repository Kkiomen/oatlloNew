---
title: "RabbitMQ connection refused"
slug: connection-refused
seo_title: "RabbitMQ Connection Refused to 5672 - How to Fix"
seo_description: "Fix RabbitMQ 'Connection refused' / ECONNREFUSED on port 5672: broker not running, wrong host, wrong port or bad credentials. Diagnose it step by step."
---

A RabbitMQ connection refused error shows up the moment your app tries to reach the broker:

```text
Connection refused
ECONNREFUSED 127.0.0.1:5672
php_network_getaddresses: getaddrinfo failed
```

The message means your code tried to open a TCP connection to the broker on port **5672**
and nothing accepted it. Nine times out of ten it is about **where** you are connecting, not
a bug in your code. Four causes explain almost every case, listed in the order worth
checking.

## Cause 1: the broker isn't running

The simplest one. If there's no RabbitMQ listening, the connection is refused instantly.
Check it:

```bash
docker ps
```

You want a row for your `rabbitmq` container with status `Up`. One catch: `docker ps` lists
only running containers. If the row is missing, the container may have crashed rather than
never existed - run `docker ps -a` to see stopped ones and their exit codes before you
assume it was never started. If it's missing or shows `Exited`, start it:

```bash
docker start rabbitmq
```

If you're on the Docker stack from
[a Laravel + RabbitMQ Docker stack](/course/rabbitmq-basics/real-world-and-troubleshooting/a-laravel-rabbitmq-docker-stack),
a very common version of this is the app booting **before** the broker is ready. The fix
there is the healthcheck plus `depends_on ... condition: service_healthy`, so the app waits
until RabbitMQ answers.

## Cause 2: the wrong host (localhost vs the service name)

This is the biggest one, and it's subtle because the same value works in one place and
fails in another.

- Running a script **on your host machine** against a broker with a published port: the
  host is `localhost`.
- Running **inside a container** (your `app` or `worker`) on the compose network: the host
  is the broker's **service name**, `rabbitmq` - not `localhost`.

Inside a container, `localhost` means "this same container". There's no broker there, so
the connection is refused. If your Laravel worker in Docker says connection refused to
`127.0.0.1:5672`, this is why: `RABBITMQ_HOST` is still `localhost` when it should be
`rabbitmq`. This is the service-name networking point from the capstone.

```bash
# Inside a container on the compose network:
RABBITMQ_HOST=rabbitmq

# From a script on your host machine:
RABBITMQ_HOST=localhost
```

## Cause 3: the wrong port

RabbitMQ speaks AMQP on **5672**. The web dashboard is on **15672**. They are not
interchangeable, and mixing them up is common because the numbers look alike.

- Pointing your app at **15672** gives a connection that opens but immediately breaks,
  because that port speaks HTTP, not AMQP. You'll see a handshake or protocol error rather
  than a clean "refused".
- If you mapped a **different host port** (for example `-p 5673:5672` because 5672 was
  taken), your app must connect on `5673`, but *inside* the Docker network it's still
  `5672`. Published ports only rename the port on the host side.

Confirm the broker is actually listening on the port you expect:

```bash
docker exec rabbitmq rabbitmq-diagnostics listeners
```

You should see an `amqp` listener on 5672.

## Cause 4: bad credentials

Wrong username or password doesn't strictly "refuse" the TCP connection - it opens and then
fails the AMQP login. But the errors look similar to beginners, so rule it out. A classic
trap: the default `guest` user **only works from localhost**. From another container it's
rejected, which is exactly why the capstone creates a real `app` user instead.

```text
ACCESS_REFUSED - Login was refused using authentication mechanism PLAIN
```

If you see `ACCESS_REFUSED`, the connection reached the broker fine - it's your username,
password or vhost that's wrong. Check that `RABBITMQ_USER` / `RABBITMQ_PASSWORD` match the
user the broker was created with, and that `RABBITMQ_VHOST` exists (users get permissions
per vhost, as covered in the production chapter).

## A quick diagnosis routine

Work from the outside in:

```bash
# 1. Is the broker up?
docker ps

# 2. Is it listening on 5672?
docker exec rabbitmq rabbitmq-diagnostics listeners

# 3. Can you reach the port from where your code runs?
#    From the host:
curl -v telnet://localhost:5672
#    From inside the app container:
docker compose exec app php -r "fsockopen('rabbitmq', 5672) or print 'refused';"
```

If step 1 or 2 fails, it's the broker. If they pass but step 3 fails, it's your host or
port. If step 3 connects but the app still errors on login, it's credentials.

## FAQ

### Why does my code get "connection refused" only inside Docker?

Because `localhost` inside a container is the container itself, not your host. Set
`RABBITMQ_HOST` to the broker's service name (`rabbitmq`) for code running in containers,
and keep `localhost` only for scripts on your host machine.

### I can open the management UI but my app still can't connect.

The UI is on port 15672 and your app connects on 5672. Reaching the dashboard proves the
broker is up, but says nothing about the AMQP port. Confirm 5672 is published and that
your app is pointed at it, not at 15672.

### It worked yesterday and now says connection refused.

The broker container probably stopped - after a reboot, or because it was removed. Run
`docker ps`; if it's gone, `docker start rabbitmq` (or `docker compose up -d`). If you use
a data volume, your queues are still there waiting.
