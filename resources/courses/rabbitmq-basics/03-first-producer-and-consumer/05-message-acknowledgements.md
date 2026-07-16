---
title: "Message acknowledgements"
slug: message-acknowledgements
seo_title: "RabbitMQ Message Acknowledgement in PHP: ack & nack"
seo_description: "Stop losing RabbitMQ messages when a worker crashes. Use manual message acknowledgement: turn off auto-ack, ack after processing, and nack to requeue on failure."
---

## The problem

The last two lessons leaned on **auto-acknowledge**: RabbitMQ marked each message as
delivered the moment it handed it over, before your callback even ran. Manual message
acknowledgement exists to fix what that costs you. If the worker crashed - a bug, a killed
process, a server reboot - the message it was holding is **gone**, because RabbitMQ had
already forgotten about it.

For a work queue that's often unacceptable. You want a message to stay in the queue until
a worker has actually *finished* processing it. That's what
[acknowledgements](/course/rabbitmq-basics/core-concepts/messages-and-acknowledgements)
are for, and now we'll use them properly.

## Turn off auto-ack and ack manually

Two changes: flip the `no_ack` flag from `true` to `false`, and call `ack()` yourself once
the work is done.

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    $body = $message->getBody();
    echo ' [x] Received ', $body, "\n";

    sleep(substr_count($body, '.'));

    echo " [x] Done\n";

    $message->ack();
};

// The 4th argument (no_ack) is now false: we acknowledge manually.
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
```

The key line is `$message->ack()`. It runs **after** `sleep(...)`, so RabbitMQ only
removes the message once the work has genuinely completed. If the worker dies before
reaching that line, the message was never acknowledged - RabbitMQ notices the connection
dropped and hands the message to another worker. Nothing is lost.

## Rejecting a message on failure

Sometimes the work fails - an external API is down, the data is temporarily invalid. In
that case, don't ack. Use `nack()` instead and ask RabbitMQ to put the message back on the
queue so it can be tried again:

```php
$callback = function ($message) {
    try {
        // ... do the work ...
        $message->ack();
    } catch (\Throwable $e) {
        // requeue = true: put it back so another worker (or a retry) picks it up
        $message->nack(false, true);
    }
};
```

`nack(false, true)` means: don't touch multiple messages at once (`false`), and **requeue
this one** (`true`). Pass `false` for the second argument instead and the message is
dropped rather than retried.

Two things that bite people here. First, a requeued message goes back near the **front**
of the queue, not the end, so a message that fails instantly comes straight back to a
worker - a tight retry loop, not a patient one. Second, the ack has to happen on the same
channel that delivered the message; the delivery tag your `AMQPMessage` carries is only
meaningful on that channel, so acking from a different one throws.

## Common mistake

The big one is the auto-ack data-loss trap. With `no_ack` set to `true`, a message is
acknowledged on *delivery*, not on *completion*. It looks fine in testing - messages flow,
work gets done - but the day a worker crashes mid-task, that message vanishes with no
error and no trace. If losing a message matters, always use manual acks: turn `no_ack`
off and call `ack()` only after the work succeeds.

The second mistake is the mirror image: turning manual acks on but **forgetting to call**
`ack()`. RabbitMQ then thinks the message is still being worked on forever. It's never
redelivered and never removed, and these "unacknowledged" messages pile up until the
worker disconnects. Every successful path must end in exactly one `ack()`.

## FAQ

### Does a message stay in the queue until I ack it?

RabbitMQ moves it to an "unacknowledged" state - still owned by the consumer, not yet
removed. If that consumer's connection drops before acking, the message goes back to
"ready" and is delivered to another consumer. Your `ack()` is what finally deletes it.

### What's the difference between nack and reject?

Both refuse a message. `reject()` handles one message; `nack()` can refuse several at once
and is the more flexible, commonly used call. Both take a `requeue` flag deciding whether
the message goes back on the queue or is discarded.

### Won't a failing message that keeps requeuing loop forever?

It can - a "poison message" that always fails will bounce back endlessly. Handling that
properly (retry limits and dead-letter queues) comes later in the course. For now, just be
aware that blindly requeuing every failure can create an infinite loop.
