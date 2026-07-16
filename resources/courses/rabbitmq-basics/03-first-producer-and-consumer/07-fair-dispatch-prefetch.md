---
title: "Fair dispatch and prefetch"
slug: fair-dispatch-prefetch
seo_title: "RabbitMQ Prefetch in PHP: Fair Dispatch with basic_qos"
seo_description: "Stop RabbitMQ overloading one busy worker. Set a prefetch count of 1 with basic_qos for fair dispatch, so slow workers aren't handed more messages, in PHP."
---

## The problem

Back in [work queues](/course/rabbitmq-basics/first-producer-and-consumer/work-queues),
RabbitMQ handed messages to workers in plain round-robin - message 1 to worker A, message
2 to worker B, message 3 to worker A, and so on. It counts messages, not effort, and the
RabbitMQ prefetch setting is what lets you change that.

That causes a real imbalance. Imagine the odd-numbered tasks are heavy and the
even-numbered ones are quick. Round-robin still alternates blindly, so worker A ends up
buried under all the slow jobs while worker B races through its light ones and then sits
idle. RabbitMQ dispatched fairly by *count*, but the *work* landed unevenly.

The reason is that RabbitMQ dispatches a message the moment it arrives, without waiting to
see whether a worker is still busy with the previous one.

## Fix it with prefetch

The fix is to tell RabbitMQ: "don't give a worker a new message until it has acknowledged
the one it's holding." That's the **prefetch count**, set with `basic_qos`:

```php
// prefetch_size, prefetch_count, a_global
$channel->basic_qos(null, 1, null);
```

A prefetch count of `1` means each worker is handed at most one unacknowledged message at
a time. RabbitMQ won't send it another until it acks the current one. Now a message goes to
the **next free worker**, not the next worker in line - so a busy worker is skipped, and a
worker that just finished picks up the next task. This is called **fair dispatch**.

## The full worker

Prefetch works hand in hand with manual acknowledgements from the
[acks lesson](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements) -
without acks, RabbitMQ never learns a worker is "free again", so prefetch would have
nothing to go on. Here's the complete fair-dispatch worker:

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

// Fair dispatch: at most one unacknowledged message per worker.
$channel->basic_qos(null, 1, null);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    $body = $message->getBody();
    echo ' [x] Received ', $body, "\n";

    sleep(substr_count($body, '.'));

    echo " [x] Done\n";

    $message->ack();
};

$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
```

Run two of these, then send a mix of heavy and light tasks. This time whichever worker is
free grabs the next job, so the load stays balanced instead of one worker drowning while
the other loafs.

One placement rule matters: call `basic_qos` **before** `basic_consume`. The prefetch
limit applies to the consumer at the time it subscribes, so setting it after the consumer
is already running has no effect on it. And be aware fairness has a price - prefetch 1
leaves a worker idle during the round-trip of acking one message and being handed the
next. For quick, uniform tasks that gap can dominate, which is why the fix here targets
slow, uneven work specifically.

## Round-robin vs fair dispatch

- **Round-robin** (the default) distributes by *order*: next message to the next consumer,
  regardless of whether it's busy. Simple, but it can overload a slow worker.
- **Fair dispatch** (`basic_qos` with prefetch 1) distributes by *availability*: next
  message to a worker that isn't already busy. It evens out uneven workloads.

For work queues where tasks take different amounts of time, fair dispatch is almost always
what you want.

## Common mistake

Setting `basic_qos` but leaving the consumer on auto-ack. Prefetch limits how many
*unacknowledged* messages a worker may hold. With auto-ack, messages are acknowledged the
instant they're delivered, so there are never any unacknowledged messages for the limit to
apply to - and prefetch does nothing. Fair dispatch only works together with manual acks.

## FAQ

### What number should the prefetch count be?

Start with `1` for fair dispatch when tasks are slow and uneven. For fast, uniform tasks a
higher value (like 10 or more) can improve throughput by keeping a small buffer of work
ready per worker. It's a tuning knob - measure with your real workload.

### What are the first and third arguments of basic_qos?

The first is `prefetch_size` (a byte limit, almost always left as `null`), and the third is
the `global` flag (whether the limit applies per-consumer or across the whole channel).
Passing `null` for both is the normal case; the middle argument, the message count, is the
one you set.

### Does prefetch replace acknowledgements?

No - it depends on them. Prefetch counts *unacknowledged* messages, so it only makes sense
with manual acks. Use the two together: manual acks for safety, prefetch for fair
distribution.
