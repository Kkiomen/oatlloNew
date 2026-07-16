---
title: "Publisher confirms"
slug: publisher-confirms
seo_title: "RabbitMQ Publisher Confirms in PHP (confirm_select)"
seo_description: "RabbitMQ publisher confirms tell a producer the broker stored a message. Learn confirm_select, wait_for_pending_acks, and how they differ from consumer acks."
---

Acknowledgements so far have all sat on the **consumer** side: the consumer tells the
broker "I processed this". A second gap opens earlier in the journey, though. How does the
**producer** know the broker even received and stored the message it published? That gap is
exactly what RabbitMQ publisher confirms close.

## The problem: publishing is fire-and-forget

By default, `basic_publish` returns immediately and tells you nothing. The message is
handed to the socket and your code moves on. If the broker was mid-restart, the connection
dropped, or a durable queue could not persist the message to disk, you would never find
out. Your producer thinks the job is queued; in reality it vanished.

For a welcome email that might be fine. For "the customer paid, ship the order", losing a
message silently is not acceptable.

## Turning on publisher confirms

Publisher confirms put the channel into a mode where the broker sends back an ack for every
message once it has taken responsibility for it (stored it, or routed it to all its
queues). You enable it once per channel with `confirm_select`:

```php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('orders', false, true, false, false);

// Put the channel into confirm mode. Do this once, before publishing.
$channel->confirm_select();

$msg = new AMQPMessage('order 42 paid', ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
$channel->basic_publish($msg, '', 'orders');

// Block until the broker has confirmed every message published on this channel.
$channel->wait_for_pending_acks();

echo "Broker confirmed the message is safely stored\n";
```

`wait_for_pending_acks()` blocks until the broker has acked (or nacked) everything you
published since the last wait. When it returns without throwing, you know the broker
accepted your messages.

One detail worth knowing before it bites you: the call takes an optional timeout, and if
the broker stays silent past it you get an `AMQPTimeoutException`, not a nack. A timeout is
not a "no" - it means you never learned the answer, so treat it the same cautious way you
would a dropped connection rather than assuming the publish failed.

## Reacting to acks and nacks

For most code, "publish then wait" is enough. If you want to react per message - log a
failure, retry a nack - register handlers before publishing:

```php
$channel->confirm_select();

$channel->set_ack_handler(function (AMQPMessage $message) {
    // The broker stored this message.
});

$channel->set_nack_handler(function (AMQPMessage $message) {
    // The broker could NOT take responsibility. Republish or alert.
});

$channel->basic_publish($msg, '', 'orders');
$channel->wait_for_pending_acks();
```

A **nack** here means the broker failed to handle the message (for example an internal
error). It is rare, but it is the signal that tells you not to assume success.

## Confirms are the mirror image of consumer acks

It helps to see the two acknowledgement systems side by side:

- **Publisher confirms** protect the **first hop**: producer to broker. The *broker* acks
  the *producer*.
- **Consumer acks** (from the [previous lesson](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive))
  protect the **last hop**: broker to consumer. The *consumer* acks the *broker*.

You need both for end-to-end reliability. Confirms alone still lose the message if the
consumer crashes; consumer acks alone still lose it if the broker never got it. Combined
with durable, persistent messages from
[chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence),
they give you a message that survives every step.

## Common mistake: publishing without waiting

Calling `confirm_select()` and then never calling `wait_for_pending_acks()` gives you no
protection at all - you enabled the machinery but never read the result. Just as bad is
waiting after *every single* message in a tight loop, which is slow. The usual pattern is:
publish a batch, then wait once for the whole batch.

Batching earns its keep most with persistent messages on a durable queue. The broker only
confirms such a message once it has been written to disk, so a per-message wait pauses your
producer for a disk round trip every single time. Confirm the batch instead and the broker
can flush many messages with one write.

## FAQ

### Does a confirm mean the consumer processed the message?

No. A confirm only means the broker took responsibility for the message. Whether a consumer
later receives and processes it is a completely separate step covered by consumer
acknowledgements.

### Do confirms slow down publishing?

Waiting after each message does, because you pause for a round trip every time. Publishing
a batch and calling `wait_for_pending_acks()` once amortises that cost and stays fast while
still catching failures.

### Is a confirm the same as knowing the message reached a queue?

By default a confirm means the broker accepted the message, but a message routed to no
queue is still confirmed - it was simply discarded. If you must know it landed in a queue,
combine confirms with the mandatory flag and a return handler, or publish to a queue you
know is bound.
