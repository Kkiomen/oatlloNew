---
title: "Fanout exchange"
slug: fanout-exchange
seo_title: "RabbitMQ Fanout Exchange for Pub/Sub (php-amqplib)"
seo_description: "A RabbitMQ fanout exchange ignores the routing key and broadcasts every message to all bound queues. Build pub/sub in php-amqplib with a queue per consumer."
---

## How a fanout exchange broadcasts to every queue

A **RabbitMQ fanout exchange** does one thing: it copies every message to **every** queue
bound to it, and it **ignores the routing key completely**. Bind five queues and all five
get a copy. This is how you build publish/subscribe (pub/sub): one producer announces an
event, and any number of consumers react to it.

A good example is a "user signed up" event. The email service wants it, the analytics
service wants it, and the audit log wants it. None of them should have to know about the
others. Fanout lets the producer announce once and each service listen independently.

## Declaring a fanout exchange

```php
// name, type, passive, durable, auto_delete
$channel->exchange_declare('user_events', 'fanout', false, true, false);
```

Publishing is the same as any exchange, except the routing key is **meaningless** - pass
an empty string, since fanout never looks at it:

```php
use PhpAmqpLib\Message\AMQPMessage;

$msg = new AMQPMessage('{"user_id": 42}');
$channel->basic_publish($msg, 'user_events', ''); // routing key ignored
```

## Each consumer gets its own queue

This is the part that trips people up. For pub/sub you do **not** want several consumers
sharing one queue - that's a work queue, where each message goes to only one of them
(you saw this in [chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/work-queues)).

For fanout you want each consumer to have **its own queue**, so each one receives its own
copy of every event. The trick is to let RabbitMQ generate a fresh, exclusive queue for
each consumer:

```php
// empty name -> RabbitMQ generates a unique name
list($queueName, ,) = $channel->queue_declare('', false, false, true, true);

$channel->queue_bind($queueName, 'user_events');

$channel->basic_consume($queueName, '', false, true, false, false, function ($msg) {
    echo 'got event: ', $msg->body, "\n";
});
```

Passing an empty queue name makes RabbitMQ invent one like `amq.gen-JzTY...`. The
`exclusive` flag (the last `true`) means the queue belongs to this connection and is
deleted when the consumer disconnects. Notice `queue_bind` here has **no routing key** -
fanout doesn't use one, so you can leave it off.

Run this consumer in two terminals and each terminal declares its own generated queue,
so each one receives every message. That's the difference between fanout (everyone gets a
copy) and a work queue (the copies are shared out).

There's a catch worth naming out loud: an exclusive, auto-generated queue dies with its
connection and comes back under a fresh `amq.gen-...` name. A subscriber that drops off
and reconnects therefore misses everything published while it was gone - fanout has no
memory of the past, it only serves queues that exist at publish time.

## Common mistake

Binding several consumers to the **same named queue** and expecting all of them to
receive every message. They won't - one queue with multiple consumers is a work queue,
and RabbitMQ round-robins messages between them. For a true broadcast, every consumer
needs a separate queue bound to the fanout exchange.

## FAQ

### Does the routing key matter at all for fanout?

No. A fanout exchange delivers to all bound queues regardless of the routing key. Set it
to an empty string to make your intent clear.

### Should the temporary queues be durable?

Usually not. A pub/sub subscriber typically only wants events while it's running, so an
exclusive, auto-generated, non-durable queue is the right choice. If a consumer must not
miss events while it's offline, give it a named, durable queue instead and bind that.

### How is fanout different from a work queue?

A work queue is one queue shared by many consumers - each message goes to exactly one of
them. Fanout is many queues, each getting a full copy of every message. Same word
"consumer", very different delivery.
