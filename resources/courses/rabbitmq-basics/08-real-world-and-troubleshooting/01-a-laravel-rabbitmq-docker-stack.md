---
title: "A Laravel + RabbitMQ Docker stack"
slug: a-laravel-rabbitmq-docker-stack
seo_title: "Laravel RabbitMQ Docker Compose Stack Example"
seo_description: "A full annotated docker-compose.yml running a Laravel app, RabbitMQ and a queue worker, with the .env wiring, a data volume and service-name networking."
---

Everything the course taught - a broker, a producer, a consumer, acknowledgements, a
Laravel driver - collapses into three containers described in one file. Here you get a
`docker-compose.yml` you can copy, the `.env` that wires Laravel to the broker, and the
reasoning behind every line. By the end, one command brings the whole system up.

## The three services

A real setup is not one container, it's a small system that talks to itself:

- **`app`** - your Laravel code running under PHP-FPM. This is where you `dispatch()` jobs
  from web requests.
- **`rabbitmq`** - the broker, using the `rabbitmq:3-management` image so you also get the
  dashboard you toured in [the management UI tour](/course/rabbitmq-basics/getting-started/the-management-ui-tour).
- **`worker`** - a second copy of the same Laravel code, but instead of serving web
  requests it runs `php artisan queue:work rabbitmq` forever, pulling jobs off the broker.

The `app` puts messages in, the `worker` takes them out, and `rabbitmq` sits in the
middle. That is the exact producer-consumer-broker triangle from
[producers, consumers, broker](/course/rabbitmq-basics/core-concepts/producers-consumers-broker),
now in containers.

## The docker-compose.yml

```yaml
services:
  # Your Laravel application, served by PHP-FPM.
  # This container dispatches jobs; it does not process them.
  app:
    build: .
    volumes:
      - ./:/var/www/html
    depends_on:
      rabbitmq:
        condition: service_healthy
    environment:
      QUEUE_CONNECTION: rabbitmq
      RABBITMQ_HOST: rabbitmq

  # The broker. The :3-management tag includes the web dashboard on 15672.
  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "5672:5672"    # AMQP - apps connect here
      - "15672:15672"  # management UI - you connect here in a browser
    environment:
      RABBITMQ_DEFAULT_USER: app
      RABBITMQ_DEFAULT_PASS: secret
    volumes:
      - rabbitmq-data:/var/lib/rabbitmq  # queues survive a restart
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "-q", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # The worker: the SAME image as app, but its job is to run queue:work forever.
  worker:
    build: .
    command: php artisan queue:work rabbitmq --tries=3 --timeout=90
    volumes:
      - ./:/var/www/html
    depends_on:
      rabbitmq:
        condition: service_healthy
    environment:
      QUEUE_CONNECTION: rabbitmq
      RABBITMQ_HOST: rabbitmq
    restart: unless-stopped

volumes:
  rabbitmq-data:
```

## Service-name networking: why RABBITMQ_HOST is "rabbitmq"

This is the single most important line to understand. Compose puts all three services on
one private network and gives each a hostname equal to its **service name**. So from
inside the `app` and `worker` containers, the broker is reachable at the host `rabbitmq`,
not `localhost`.

That is why the environment sets `RABBITMQ_HOST: rabbitmq`. A container's `localhost`
means "this container", so `localhost:5672` from `app` would try to find a broker inside
the app container, where none exists. This mix-up is the number-one cause of the connection
error in [connection refused](/course/rabbitmq-basics/real-world-and-troubleshooting/connection-refused).

The published ports (`5672`, `15672`) are only for **you** on the host machine - so you
can point a local script or your browser at `localhost`. Containers talking to each other
ignore those and use the service name.

## The .env wiring

Laravel reads these values. In development they live in `.env`; in the compose file above
we override the two that must point at the container, so the app works whether you run it
in Docker or from your host.

```bash
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=app
RABBITMQ_PASSWORD=secret
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=default
```

`QUEUE_CONNECTION=rabbitmq` is what makes `dispatch()` and `queue:work` use the RabbitMQ
driver at all, exactly as covered in
[configuring the connection](/course/rabbitmq-basics/rabbitmq-and-laravel/configuring-the-connection).
The `RABBITMQ_USER` and `RABBITMQ_PASSWORD` must match the `RABBITMQ_DEFAULT_USER` and
`RABBITMQ_DEFAULT_PASS` you set on the broker service - those two variables create that
user on first boot. We use a real `app` user instead of `guest` because `guest` only works
from localhost and would be refused from another container.

## The data volume

Look at this line on the broker:

```yaml
volumes:
  - rabbitmq-data:/var/lib/rabbitmq
```

RabbitMQ stores its queues, exchanges, users and **durable** messages under
`/var/lib/rabbitmq`. Without a volume, all of that lives inside the container and vanishes
the moment you run `docker compose down`. The named volume `rabbitmq-data` keeps it on
disk, so a durable queue with persistent messages (the setup from
[durability and persistence](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence))
survives a restart. Durability inside the broker and a Docker volume are two halves of the
same promise: mark a message persistent *and* give the broker somewhere permanent to keep
it.

One trap worth knowing before you rely on this: `docker compose down` keeps the named
volume, but `docker compose down -v` deletes it. The `-v` flag is exactly how people wipe
their queues by accident while "just resetting" the stack. Plain `down` stops the
containers; `down -v` throws away the data directory with them.

## Bringing it up

```bash
docker compose up -d --build
```

Compose builds your image, starts the broker, waits for its healthcheck to pass, then
starts `app` and `worker`. The `depends_on ... condition: service_healthy` blocks the app
and worker until `rabbitmq-diagnostics ping` succeeds, so they never race the broker and
crash with a connection error on the first boot.

Watch the worker pick up jobs:

```bash
docker compose logs -f worker
```

Now dispatch a job from the app - for example via `php artisan tinker` inside the app
container - and you'll see the worker log it. Open `http://localhost:15672` and log in
with `app` / `secret` to watch the queue fill and drain in real time.

## Why the worker is a separate service

A common question: why not run the worker inside the `app` container? Because they have
different lifecycles. PHP-FPM answers web requests and can be restarted or scaled for
traffic; the worker is a long-running process that must keep going between requests. Making
it its own service lets you **scale consumers independently** - this is the competing
consumers idea from [work queues](/course/rabbitmq-basics/first-producer-and-consumer/work-queues).
Need to clear a backlog faster? Run more workers:

```bash
docker compose up -d --scale worker=3
```

Three worker containers now share one queue, and RabbitMQ round-robins jobs between them.
Nothing else changes.

## FAQ

### Why does the app use "rabbitmq" as the host but I use "localhost"?

Inside the Docker network, each service is reachable by its service name, so `app` reaches
the broker at `rabbitmq`. On your host machine there is no such name, so you use
`localhost` with the published port. Same broker, two names, depending on who is asking.

### Do I need a separate worker container, or can queue:work run in the app one?

You can run it in the app container, but a separate `worker` service is cleaner: it has its
own logs, its own restart policy, and can be scaled to more replicas without touching your
web tier. That separation is the whole point of a queue.

### My messages disappear after docker compose down. Why?

Either you have no `rabbitmq-data` volume, or your queues and messages are not durable.
You need both: a named volume so the broker's data directory persists, and durable queues
with persistent messages so the broker actually writes them there.

### The worker container keeps restarting on first boot.

It is almost certainly starting before the broker is ready and failing to connect. Add the
healthcheck and `depends_on ... condition: service_healthy` shown above so the worker waits
until RabbitMQ answers a ping.
