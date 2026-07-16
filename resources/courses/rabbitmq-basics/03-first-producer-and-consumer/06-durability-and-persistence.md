---
title: "Durability and persistence"
slug: durability-and-persistence
seo_title: "RabbitMQ Durable Queues and Persistent Messages in PHP"
seo_description: "Make RabbitMQ survive a broker restart in PHP: declare a durable queue, publish persistent messages with delivery_mode 2, and why you need both together."
---

## The problem

Manual acks save you when a *worker* crashes. They do nothing when the *broker itself*
restarts - a server reboot, a crash, a `docker restart`. Surviving that takes a durable
queue and persistent messages in RabbitMQ, and here's why: by default your queue and its
messages live only in memory, so a restart wipes them out. Every task still waiting is
gone.

To survive a broker restart you need two separate things, and you need **both**:

1. a **durable queue**, so the queue definition survives the restart, and
2. **persistent messages**, so the messages inside it survive too.

Turning on one without the other doesn't help. A durable queue full of non-persistent
messages comes back empty; persistent messages sent to a non-durable queue vanish with the
queue.

## Declare a durable queue

Durability is the **third** argument of `queue_declare` (the `durable` flag). Turn it on:

```php
// queue, passive, durable, exclusive, auto_delete
$channel->queue_declare('task_queue', false, true, false, false);
```

One catch: a queue's durability is fixed when it's created and **can't be changed later**.
If you already declared `task_queue` as non-durable in earlier lessons, RabbitMQ will
refuse to redeclare it with different settings. Either use a new queue name, or delete the
old queue in the [management UI](/course/rabbitmq-basics/getting-started/the-management-ui-tour)
first.

That refusal is worth understanding, because it isn't a normal exception you can shrug
off. A mismatched redeclare fails with `PRECONDITION_FAILED`, and RabbitMQ closes the
**whole channel** to enforce it - not just that one call. Any code after it on the same
channel then fails too, so you have to open a fresh channel before you can continue.

## Publish persistent messages

A message is marked persistent through its `delivery_mode` property. Set it to `2`
(persistent); the default is `1` (transient). php-amqplib exposes the value as a constant
so you don't have to remember the number:

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

$message = new AMQPMessage('A durable task...', [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$channel->basic_publish($message, '', 'task_queue');

echo " [x] Sent a persistent message\n";

$channel->close();
$connection->close();
```

`AMQPMessage::DELIVERY_MODE_PERSISTENT` is just `2` - both forms mean the same thing. With
this, RabbitMQ writes the message to disk instead of keeping it only in memory.

Your consumer needs no changes for this. Now restart the broker while a message is waiting
(`docker restart <container>` if you're using Docker), reconnect, and the message is still
there.

## Common mistake

Declaring the queue durable but forgetting `delivery_mode`. This is the single most common
durability bug: the queue survives the restart, so everything *looks* configured for
persistence, but the messages inside were transient and disappeared. Both switches must be
on. Whenever a task must not be lost, declare the queue **durable** and publish the message
**persistent** - together, every time.

## An honest caveat

Persistence makes loss *very unlikely*, not impossible. There's a tiny window between
RabbitMQ accepting a message and actually writing it to disk. If the broker dies inside
that window, that one message can still be lost, because a plain `basic_publish` doesn't
wait for confirmation that the write happened. Closing that last gap needs **publisher
confirms**, which the course covers later. For most work queues, durable + persistent is
the right level of safety.

## FAQ

### Does making messages persistent slow things down?

A little - writing to disk is slower than keeping messages in memory. For most
applications the safety is worth it, and RabbitMQ batches disk writes to keep the cost
low. Only very high-throughput systems need to weigh the trade-off carefully.

### Why can't I just redeclare an existing queue as durable?

A queue's properties are set at creation and are immutable. Redeclaring with different
flags throws an error rather than silently changing them. To change durability, delete the
queue and declare it again, or use a different name.

### Is a durable queue the same as a persistent message?

No, and that's the whole point of this lesson. **Durable** describes the *queue* surviving
a restart. **Persistent** describes a *message* being written to disk. You need both for
messages to actually survive.
