---
title: "Delayed messages"
slug: delayed-messages
seo_title: "RabbitMQ Delayed Messages in PHP: TTL vs Plugin (x-delay)"
seo_description: "Send a RabbitMQ delayed message: the TTL plus dead-letter trick, and the rabbitmq-delayed-message-exchange plugin with x-delay, plus when to use each."
---

Sometimes you do not want a message processed now - you want it processed *later*. "Send a
reminder in 24 hours." "Retry this payment in 5 minutes." "Release the seat hold in 15
minutes if unpaid." RabbitMQ has no built-in "publish at time T", so a RabbitMQ delayed
message is something you build, and there are two solid ways to do it.

## Option 1: the TTL plus dead-letter trick

You already built the moving part of this in
[retries and dead-letter queues](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues).
A message sits in a queue with a TTL, and when it expires it is dead-lettered onward. Point
that "onward" at the queue your consumer actually reads, and you have a delay:

```php
use PhpAmqpLib\Wire\AMQPTable;

// The queue your worker consumes from.
$channel->exchange_declare('work', 'direct', false, true, false);
$channel->queue_declare('jobs', false, true, false, false);
$channel->queue_bind('jobs', 'work', 'jobs');

// The delay queue: hold for 5 minutes, then dead-letter into 'work' -> 'jobs'.
$delayArgs = new AMQPTable([
    'x-message-ttl'             => 300000, // 5 minutes
    'x-dead-letter-exchange'    => 'work',
    'x-dead-letter-routing-key' => 'jobs',
]);
$channel->queue_declare('jobs.delay.5m', false, true, false, false, false, $delayArgs);

// Publish into the delay queue instead of the work queue.
$channel->basic_publish($msg, '', 'jobs.delay.5m');
```

The message waits out the TTL in `jobs.delay.5m`, then lands in `jobs` and gets processed.

The big limitation: the TTL is **per queue**, so every message in that queue shares the same
delay. Because a queue processes its head first, a message with a shorter TTL sitting behind
one with a longer TTL will not expire early - it waits for the one in front. To support many
different delays you need a separate queue per delay value (5m, 1h, 24h), which gets clumsy.

## Option 2: the delayed-message-exchange plugin

For arbitrary per-message delays, install the community plugin
`rabbitmq-delayed-message-exchange`. It adds a new exchange type, `x-delayed-message`, that
holds each message until its own delay elapses, then routes it normally.

Enable it once on the broker:

```bash
rabbitmq-plugins enable rabbitmq_delayed_message_exchange
```

Declare an exchange of type `x-delayed-message`. The `x-delayed-type` argument tells it how
to route once the delay is up (behave like a `direct` exchange, a `topic`, and so on):

```php
use PhpAmqpLib\Wire\AMQPTable;

$channel->exchange_declare(
    'scheduled',
    'x-delayed-message',
    false,
    true,
    false,
    false,
    false,
    new AMQPTable(['x-delayed-type' => 'direct'])
);

$channel->queue_declare('reminders', false, true, false, false);
$channel->queue_bind('reminders', 'scheduled', 'remind');
```

Now set the delay **per message** with the `x-delay` header, in milliseconds:

```php
use PhpAmqpLib\Message\AMQPMessage;

$msg = new AMQPMessage('send reminder to user 42', [
    'delivery_mode'      => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    'application_headers' => new AMQPTable(['x-delay' => 3600000]), // 1 hour
]);

$channel->basic_publish($msg, 'scheduled', 'remind');
```

Every message can carry a different `x-delay`, and the exchange releases each one on its own
schedule. That is the flexibility the TTL trick cannot give you.

One thing surprises people the first time: a message waiting out its delay does not sit in
the target queue. The plugin holds it inside the exchange until the timer fires, so it will
not show up in your queue length or the management UI as a queued message until it is
released. Worth knowing before you go looking for a scheduled message and think it vanished.

## Which one should you use?

- **TTL plus dead-letter** needs no plugin and is perfect when you have a small number of
  fixed delays (a retry ladder, a single "15 minute" hold). It is standard RabbitMQ and
  works everywhere.
- **The plugin** is the right choice when delays vary per message or you need many different
  values. The cost is a plugin to install and maintain, and it is a community add-on rather
  than a core feature.

## Common mistake: expecting per-message delays from a shared TTL queue

The classic trap is putting messages with different intended delays into one `x-message-ttl`
queue and expecting each to fire on time. They will not. Head-of-line ordering means a
message cannot "jump ahead" of an earlier one that is still counting down. If you need
per-message timing, use the plugin or one queue per delay value - never one queue with mixed
expectations.

## FAQ

### Is the delay exact?

Neither method is precise to the second. TTL is a lower bound that can drift under load, and
the plugin is close but still best-effort. Use delays for "roughly later", not for anything
that needs stopwatch accuracy.

### Do delayed messages survive a broker restart?

If the queue or exchange is durable and the messages are persistent, yes - the same
durability rules from
[chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence)
apply. A non-persistent delayed message can still be lost on restart.

### Can I cancel a delayed message before it fires?

Not easily - RabbitMQ has no "unschedule" call. If you need to cancel, make the consumer
check current state when the message finally arrives (for example, "is this reminder still
needed?") and skip it if not, rather than trying to pull it back out.
