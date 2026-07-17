---
title: "Installing the PHP client"
slug: installing-the-php-client
seo_title: "Install php-amqplib and Connect to RabbitMQ in PHP"
seo_description: "Install the php-amqplib PHP client with Composer and open your first RabbitMQ connection and channel using AMQPStreamConnection, explained step by step."
---

## The problem

RabbitMQ speaks **AMQP**, a wire protocol your PHP code has no idea how to talk on its
own. It needs a library that knows how to open a connection, send frames, and read the
replies. The standard, battle-tested client is
[`php-amqplib/php-amqplib`](https://github.com/php-amqplib/php-amqplib), so the first job
in this chapter is to install php-amqplib and open a connection. Everything that follows
builds on it.

First, make sure a broker is actually running. If you followed
[run RabbitMQ with Docker](/course/rabbitmq-basics/getting-started/run-rabbitmq-with-docker),
you already have one listening on `localhost:5672`.

## Install with Composer

From your project folder, pull the library in with Composer:

```bash
composer require php-amqplib/php-amqplib
```

That adds the package to `composer.json` and generates the autoloader. From now on a
single `require 'vendor/autoload.php';` gives you access to the client's classes.

## Open a connection and a channel

Remember from
[connections and channels](/course/rabbitmq-basics/core-concepts/connections-and-channels)
that a **connection** is one TCP link to the broker, and a **channel** is a lightweight
virtual connection inside it where the real work happens. You almost always open one
connection and then one channel on top of it.

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

echo "Connected to RabbitMQ\n";

$channel->close();
$connection->close();
```

Let's read it line by line:

- `require 'vendor/autoload.php';` loads Composer's autoloader so the client classes are
  found without manual `require`s.
- `AMQPStreamConnection('localhost', 5672, 'guest', 'guest')` opens the connection. The
  four arguments are the **host**, the **port** (5672 is AMQP's default), and the
  **username** and **password**. `guest`/`guest` is RabbitMQ's built-in default account.
- `$connection->channel()` opens a channel. This is the object you'll call
  `queue_declare`, `basic_publish` and `basic_consume` on for the rest of the chapter.
- `$channel->close()` and `$connection->close()` release the resources when you're done.
  Always close them, in that order: channel first, then connection.

Run the script with `php your-file.php`. If it prints `Connected to RabbitMQ` and exits
cleanly, your client and broker are talking.

Form one habit now. Opening a connection is expensive, since it's a TCP handshake
followed by an AMQP handshake, while opening a channel on top of it is cheap. Real apps
hold a single long-lived connection and open channels as they go, rather than
reconnecting for every message.

## Common mistake

The `guest` account only works over a **localhost** connection. That's a RabbitMQ
security default: `guest`/`guest` is refused from any remote host. On your own machine
it's fine, but the moment RabbitMQ lives on another server you'll need to create a real
user ([covered later in the course](/course/rabbitmq-basics/operating-in-production/users-vhosts-permissions)) - otherwise you'll get an "ACCESS_REFUSED" error even
though the password is correct.

## FAQ

### What's the difference between AMQPStreamConnection and AMQPSocketConnection?

Both open a connection. `AMQPStreamConnection` uses PHP's stream functions and is the
default choice for almost everything - use it unless you have a specific reason not to.
`AMQPSocketConnection` uses the sockets extension and can be marginally faster for
high-throughput publishing, but it behaves slightly differently around timeouts.

### Do I need the RabbitMQ server installed to use php-amqplib?

You need a broker running *somewhere* the client can reach - it doesn't have to be on the
same machine. Running it locally with Docker is the easiest way while you learn.

### Why port 5672?

That's the default TCP port for AMQP, the protocol RabbitMQ uses for messaging. It's a
different port from the management UI (15672), which is a web dashboard, not the
messaging channel.
