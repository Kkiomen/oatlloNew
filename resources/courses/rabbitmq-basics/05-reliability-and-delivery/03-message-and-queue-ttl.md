---
title: "Message and queue TTL"
slug: message-and-queue-ttl
seo_title: "RabbitMQ Message TTL and Queue TTL in PHP (expiration)"
seo_description: "Set a RabbitMQ message TTL on messages or whole queues: per-message expiration, x-message-ttl, x-expires, and how expired messages can dead-letter."
---

Not every message stays useful forever. A "your code is compiling" notification is
worthless ten minutes later; a one-time login link should not sit in a queue all day. A
RabbitMQ message TTL (time to live) lets the broker expire messages on its own, so stale
work never reaches a consumer.

## Per-message TTL with expiration

The most targeted option is to give a single message an expiry when you publish it. Set
the `expiration` property, in **milliseconds, as a string**:

```php
use PhpAmqpLib\Message\AMQPMessage;

// This message expires 60 seconds after it reaches the queue.
$msg = new AMQPMessage('password reset link', [
    'expiration' => '60000',
]);

$channel->basic_publish($msg, '', 'notifications');
```

If no consumer has taken the message within 60 seconds, the broker drops it. The catch to
remember: a message only expires once it reaches the **head** of the queue and the broker
looks at it. A message stuck behind others may live slightly longer than its TTL before it
is actually removed.

## Per-queue TTL with x-message-ttl

Instead of setting TTL on each message, you can apply one TTL to **every** message in a
queue. This is a queue argument, so you set it when the queue is declared. Arguments go in
an `AMQPTable`:

```php
use PhpAmqpLib\Wire\AMQPTable;

$args = new AMQPTable([
    'x-message-ttl' => 30000, // 30 seconds, as an integer of milliseconds
]);

// queue_declare($queue, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments)
$channel->queue_declare('short_lived', false, true, false, false, false, $args);
```

Every message that lands in `short_lived` now expires after 30 seconds, no matter how it
was published. Note the difference in types: the per-message `expiration` is a **string**,
the queue's `x-message-ttl` is an **integer**.

If both are set, RabbitMQ uses whichever is **shorter**.

A TTL of `0` is a special case worth remembering. It means the message expires the instant
it lands unless a consumer is ready to take it right away. Paired with a dead-letter
exchange, that gives you a "deliver now or dead-letter immediately" behaviour with no
waiting at all.

## Expiring the queue itself with x-expires

`x-message-ttl` expires messages. `x-expires` expires the **whole queue** after a period of
not being used - no consumers, no gets, no redeclares:

```php
$args = new AMQPTable([
    'x-expires' => 1800000, // delete the queue after 30 minutes of no use
]);

$channel->queue_declare('temp_session_q', false, false, false, false, false, $args);
```

This is handy for temporary, per-user or per-session queues that you would otherwise have
to clean up by hand. When the queue goes idle for long enough, it simply disappears.

"Unused" is stricter than "empty", and that trips people up. A queue with a consumer still
attached counts as in use even when it holds zero messages, so `x-expires` never fires
while anyone is listening. The clock only starts once the last consumer disconnects and no
one touches the queue.

## Expired messages can dead-letter

Here is the part that makes TTL powerful rather than just a delete button: if a queue has a
dead-letter exchange configured (from
[chapter 4](/course/rabbitmq-basics/exchanges-and-routing/dead-letter-exchanges)), an
expired message is **not thrown away**. It is routed to the dead-letter exchange instead:

```php
$args = new AMQPTable([
    'x-message-ttl'          => 10000,
    'x-dead-letter-exchange' => 'dlx',
]);

$channel->queue_declare('wait_10s', false, true, false, false, false, $args);
```

Now a message sits in `wait_10s` for 10 seconds, and then RabbitMQ moves it on. That "wait,
then move" behaviour is the whole trick behind retry queues and delayed messages, which we
build in the next lessons.

## Common mistake: changing a queue's TTL in place

Queue arguments like `x-message-ttl` are fixed when the queue is created. Re-running
`queue_declare` with a different TTL against an existing queue does **not** update it - it
throws a `PRECONDITION_FAILED` error because the arguments do not match. To change TTL you
must delete and recreate the queue, or declare a new one with a different name. Decide TTL
up front.

## FAQ

### Is TTL exact to the millisecond?

No. TTL is a lower bound, not a precise timer. A message will not be delivered *after* its
TTL, but it may linger a little past it if it is stuck behind other messages and the broker
has not yet reached it. Do not rely on TTL for precise timing.

### What is the difference between x-message-ttl and x-expires?

`x-message-ttl` expires individual **messages** inside a queue. `x-expires` deletes the
entire **queue** after it has been unused for a while. One cleans up messages, the other
cleans up the queue.

### Where does an expired message go?

By default it is discarded. If the queue has an `x-dead-letter-exchange`, the expired
message is routed there instead, which is how retry and delay patterns are built.
