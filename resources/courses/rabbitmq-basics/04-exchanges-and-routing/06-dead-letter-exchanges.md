---
title: "Dead-letter exchanges"
slug: dead-letter-exchanges
seo_title: "RabbitMQ Dead-Letter Exchange (DLX) Basics"
seo_description: "Configure a RabbitMQ dead-letter exchange via x-dead-letter-exchange in php-amqplib so rejected, expired or overflowing messages get routed instead of vanishing."
---

## What a RabbitMQ dead-letter exchange does

Sometimes a message can't be delivered normally. A consumer rejects it, it sits unconsumed
past a time limit, or the queue fills up. By default such a message is simply discarded. A
**RabbitMQ dead-letter exchange** (DLX) gives it somewhere to go instead: the broker
re-publishes the message to another exchange, where you can catch it, inspect it, or hand
it on.

A message becomes "dead-lettered" in three situations:

- A consumer **rejects** it with `basic_reject` or `basic_nack` and `requeue = false`.
- It **expires** because of a message or queue TTL (time-to-live).
- The queue is **full** and overflow pushes the message out.

## Attaching a DLX to a queue

The DLX is not a new kind of exchange - it's an ordinary exchange (usually direct or
fanout) that you nominate as a queue's destination for dead letters. You set it through
the queue's arguments when you declare the queue:

```php
use PhpAmqpLib\Wire\AMQPTable;

// 1. an ordinary exchange + queue to catch dead letters
$channel->exchange_declare('dlx', 'fanout', false, true, false);
$channel->queue_declare('orders_dead', false, true, false, false);
$channel->queue_bind('orders_dead', 'dlx');

// 2. the main queue, told to dead-letter into 'dlx'
$args = new AMQPTable([
    'x-dead-letter-exchange' => 'dlx',
]);

$channel->queue_declare('orders', false, true, false, false, false, $args);
```

The seventh argument to `queue_declare` is the arguments table. `x-dead-letter-exchange`
names the exchange that receives dead letters from `orders`. Now when a message in
`orders` is rejected or expires, RabbitMQ forwards it to `dlx`, which fans it into
`orders_dead` for you to look at.

## Rejecting a message into the DLX

A consumer sends a message to the dead-letter exchange by refusing it **without**
requeueing:

```php
$callback = function ($msg) {
    // requeue = false -> the message is dead-lettered, not put back
    $msg->nack(false, false);
};

$channel->basic_consume('orders', '', false, false, false, false, $callback);
```

If you passed `requeue = true` instead, the message would go back onto `orders` and be
redelivered immediately - often straight into the same failure, in a tight loop. Setting
`requeue = false` is what routes it to the DLX.

## Optional: override the routing key

By default the dead-lettered message keeps its original routing key. You can force a new
one with `x-dead-letter-routing-key`, which is handy when the DLX is a direct or topic
exchange and you want dead letters to land in a specific queue:

```php
$args = new AMQPTable([
    'x-dead-letter-exchange'    => 'dlx',
    'x-dead-letter-routing-key' => 'orders.dead',
]);
```

This is why a fanout DLX is the safe first choice. Since dead letters keep the original
routing key by default, pointing the DLX at a direct or topic exchange without setting
`x-dead-letter-routing-key` often means the dead letter matches no binding there and is
quietly dropped a second time. Fanout sidesteps that: it ignores the key and catches
everything.

## Common mistake

Nacking with `requeue = true` and expecting the message to reach the DLX. It won't - a
requeued message is redelivered to the original queue, not dead-lettered, and a message
that keeps failing will spin in an endless redelivery loop. Only messages rejected with
`requeue = false` (or expired, or overflowed) are dead-lettered. Also make sure the DLX
and its queue exist and are bound, or the dead letter is dropped once more.

## Where this leads

A DLX is the mechanism. The patterns built on top of it - **[retries](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues)** with a delay, a
proper **dead-letter queue (DLQ)** for messages that fail for good, and combining it with
[TTL](/course/rabbitmq-basics/reliability-and-delivery/message-and-queue-ttl) - are covered in the next chapter on reliability and delivery. For now, the important
idea is that a queue can hand its failures to another exchange instead of losing them.

## FAQ

### Is a dead-letter exchange a special exchange type?

No. It's an ordinary direct, fanout or topic exchange. What makes it a DLX is that another
queue points at it via `x-dead-letter-exchange`. The same exchange could serve other
purposes too.

### Does the dead-lettered message change?

The body stays the same. RabbitMQ adds an `x-death` header recording why and how many
times the message was dead-lettered, which is useful for building retry logic later.

### What happens if the DLX has no matching binding?

The dead letter is dropped, exactly like any message that matches no binding. Always
declare and bind the dead-letter queue so failed messages actually land somewhere.
