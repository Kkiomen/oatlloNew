---
title: "Configuring the connection"
slug: configuring-the-connection
seo_title: "Configure the Laravel RabbitMQ Queue Connection"
seo_description: "Add a rabbitmq connection to config/queue.php, set RABBITMQ_HOST, RABBITMQ_PORT, user, password and vhost in .env, then set QUEUE_CONNECTION=rabbitmq."
---

## Add a rabbitmq connection

With the [driver installed](/course/rabbitmq-basics/rabbitmq-and-laravel/installing-the-laravel-rabbitmq-driver), the next step is to configure the Laravel RabbitMQ queue
connection so Laravel knows how to reach your broker. Connections live in the `connections`
array of `config/queue.php`. Add one that uses the `rabbitmq` driver:

```php
// config/queue.php
'connections' => [

    // ... database, redis, sqs, etc.

    'rabbitmq' => [
        'driver' => 'rabbitmq',
        'queue' => env('RABBITMQ_QUEUE', 'default'),
        'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

        'hosts' => [
            [
                'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                'port' => env('RABBITMQ_PORT', 5672),
                'user' => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'vhost' => env('RABBITMQ_VHOST', '/'),
            ],
        ],

        'options' => [
            'ssl_options' => [],
            'queue' => [
                'job' => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
            ],
        ],
    ],

],
```

The package's readme has a fuller example, but this is the core: a driver, a default
queue name, and a list of `hosts` with the address and credentials. Every value reads
from the environment so you never hard-code a password.

Notice `AMQPLazyConnection`. Lazy means the TCP socket to the broker is not opened when
the app boots - it opens on the first publish or consume. Handy on `php artisan` commands
that never touch the queue, but it has a sharp edge: a wrong host or password does **not**
blow up at startup. The error surfaces later, at the first `dispatch()`, which can make a
bad credential look like a dispatch bug rather than a config one.

## Set the environment variables

Now fill those variables in `.env`. For a local Docker broker with the default `guest`
user, the values look like this:

```ini
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=default
```

A few things worth knowing:

- **Port 5672** is the AMQP protocol port - the one applications use. It is not `15672`,
  which is the management UI in your browser. Sending jobs to `15672` will fail.
- **The vhost** is a "virtual host", a namespace inside the broker. The default is a
  single slash `/`. Because a bare `/` can be awkward in some env parsers, keep it quoted
  or exactly `RABBITMQ_VHOST=/`.
- The `guest` user only works from `localhost` by default in RabbitMQ. On a remote broker
  you must create a real user, which is [covered in the production chapter](/course/rabbitmq-basics/operating-in-production/users-vhosts-permissions).

## Select the connection

Defining the connection is not enough - Laravel still uses its default. Point the default
at RabbitMQ:

```ini
QUEUE_CONNECTION=rabbitmq
```

From now on, every `dispatch()` that doesn't name a connection goes to RabbitMQ. Because
Laravel caches config in production, run `php artisan config:clear` (or
`config:cache` again) after editing `.env` so the new values are picked up.

## Common mistake

The most common failure here is leaving `QUEUE_CONNECTION` unset (or on `sync`). If it is
missing, Laravel falls back to the framework default and your jobs quietly go somewhere
other than RabbitMQ - often running inline on `sync`, which makes the queue look like it
"isn't working". Set `QUEUE_CONNECTION=rabbitmq` explicitly, then clear config. The second
common mistake is pointing `RABBITMQ_PORT` at `15672`; use `5672` for the app.

## FAQ

### What is the vhost and why is it a single slash?

A vhost is a namespace that isolates queues, exchanges and permissions inside one broker,
so different apps can share a server without colliding. RabbitMQ ships with one default
vhost named `/`, which is what `RABBITMQ_VHOST=/` selects.

### Why does connecting as guest fail on a remote server?

RabbitMQ restricts the built-in `guest` account to `localhost` connections for safety. On
any non-local broker, create a dedicated user with a password and set `RABBITMQ_USER` and
`RABBITMQ_PASSWORD` to it. Users and permissions are covered in the production chapter.

### I changed .env but nothing changed. Why?

Config is often cached. Run `php artisan config:clear`, and if you use `config:cache`,
rebuild it. Also restart any running worker - a worker reads config once at boot and will
not see the new values until it restarts.
