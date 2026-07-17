---
title: "Retries and dead-letter queues"
slug: retries-and-dead-letter-queues
seo_title: "RabbitMQ Retries and Dead-Letter Queues in PHP (TTL)"
seo_description: "Build a RabbitMQ retry and dead-letter queue pattern: a wait queue with TTL that dead-letters back to the main queue, plus a DLQ for poison messages."
---

In [acknowledgements deep dive](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive)
we saw that requeuing a failing message with `requeue = true` creates a tight, CPU-burning
loop. Here we fix that properly with a RabbitMQ retry and dead-letter queue setup: retry a
message *after a delay*, and after enough failed attempts, park it somewhere safe instead
of retrying forever.

## The building block: reject without requeue

Everything here rests on one fact from
[chapter 4](/course/rabbitmq-basics/exchanges-and-routing/dead-letter-exchanges): when you
reject a message **without** requeue and the queue has a dead-letter exchange, the message
is routed to that exchange instead of being discarded.

```php
$callback = function ($msg) {
    try {
        process($msg->body);
        $msg->getChannel()->basic_ack($msg->getDeliveryTag());
    } catch (\Throwable $e) {
        // Do NOT requeue. Let the dead-letter exchange take it.
        $msg->getChannel()->basic_nack($msg->getDeliveryTag(), false, false);
    }
};
```

That single `false` for requeue is the hinge the whole pattern turns on.

## The retry pattern: a wait queue with TTL

We want a failed message to come back and be retried, but *after a pause*. We combine two
things you already know: dead-lettering and [per-queue TTL](/course/rabbitmq-basics/reliability-and-delivery/message-and-queue-ttl). The setup is two queues:

- **Main queue** (`tasks`) - dead-letters to a `retry` exchange when a message is rejected.
- **Wait queue** (`tasks.wait`) - has a TTL and dead-letters *back* to the main queue.

```php
use PhpAmqpLib\Wire\AMQPTable;

// Exchanges that move messages between the two queues.
$channel->exchange_declare('tasks.dlx', 'direct', false, true, false);
$channel->exchange_declare('tasks.retry', 'direct', false, true, false);

// Main queue: on reject, dead-letter to tasks.dlx.
$mainArgs = new AMQPTable([
    'x-dead-letter-exchange'    => 'tasks.dlx',
    'x-dead-letter-routing-key' => 'wait',
]);
$channel->queue_declare('tasks', false, true, false, false, false, $mainArgs);
$channel->queue_bind('tasks', 'tasks.retry', 'tasks');

// Wait queue: hold for 10s, then dead-letter back to tasks.retry -> tasks.
$waitArgs = new AMQPTable([
    'x-message-ttl'             => 10000,
    'x-dead-letter-exchange'    => 'tasks.retry',
    'x-dead-letter-routing-key' => 'tasks',
]);
$channel->queue_declare('tasks.wait', false, true, false, false, false, $waitArgs);
$channel->queue_bind('tasks.wait', 'tasks.dlx', 'wait');
```

The flow: a message fails in `tasks`, is rejected without requeue, and dead-letters into
`tasks.wait`. It sits there for 10 seconds (the TTL), then expires and dead-letters back
into `tasks`, where your consumer tries again. You get automatic retries with a cooldown,
and no busy loop.

## Counting attempts so retries are not infinite

A retry loop with no limit is just a slower poison-message loop. Each time a message is
dead-lettered, RabbitMQ adds an `x-death` header recording how many times it happened. Read
it and give up after a few tries:

```php
$callback = function ($msg) {
    $deaths = 0;
    $headers = $msg->get_properties()['application_headers'] ?? null;
    if ($headers) {
        $data = $headers->getNativeData();
        $deaths = $data['x-death'][0]['count'] ?? 0;
    }

    if ($deaths >= 3) {
        // Give up: send it to the poison queue for a human to look at.
        $msg->getChannel()->basic_publish($msg, 'tasks.parking', 'dead');
        $msg->getChannel()->basic_ack($msg->getDeliveryTag());
        return;
    }

    try {
        process($msg->body);
        $msg->getChannel()->basic_ack($msg->getDeliveryTag());
    } catch (\Throwable $e) {
        $msg->getChannel()->basic_nack($msg->getDeliveryTag(), false, false);
    }
};
```

Read `x-death` carefully, because it is not a single number. It is an array with one entry
per queue-and-reason pair the message has passed through, and each entry carries its own
`count`. Grabbing `[0]['count']` works for the simple one-hop retry loop above, but once a
message bounces through several queues that first entry may not be the total you assume. The
`reason` field is useful too: it says `expired` when the TTL fired and `rejected` when your
consumer nacked, so you can tell a timed-out retry apart from a genuine failure.

## The dead-letter queue for poison messages

After the last attempt, the message goes to a final resting place - the **dead-letter
queue** (DLQ), sometimes called a parking or poison queue. Nothing consumes it
automatically. It is where messages that can never succeed wait for a human:

```php
$channel->exchange_declare('tasks.parking', 'direct', false, true, false);
$channel->queue_declare('tasks.dead', false, true, false, false);
$channel->queue_bind('tasks.dead', 'tasks.parking', 'dead');
```

You inspect this queue in the management UI, fix the underlying bug, and either replay or
delete the messages. The key win: one broken message never blocks the main queue and never
loops forever.

## Common mistake: retrying a message that can never succeed

Not every failure should be retried. A malformed payload or a validation error will fail
identically on every attempt, so retrying it just wastes 3 delays before it lands in the
DLQ anyway. Retries are for *transient* problems (a timeout, a locked row). For a message
that is simply wrong, skip straight to the dead-letter queue on the first failure.

## FAQ

### Why use a wait queue instead of just requeuing?

A plain requeue retries instantly, so a message that fails because a downstream service is
down will hammer it again and again with no pause. The wait queue adds a delay between
attempts, giving the transient problem time to clear.

### How do I get a longer delay for each retry?

Use several wait queues with increasing TTLs (10s, 60s, 300s) and route a message to the
next one based on its `x-death` count. That gives you exponential backoff without any
plugin.

### What consumes the dead-letter queue?

Usually nobody, on purpose. It is a holding area you monitor. When messages pile up there,
that is your alert that something is genuinely broken and needs a person to decide what to
do.
