---
title: "Consuming messages"
slug: consuming-messages
seo_title: "Consume RabbitMQ Messages in PHP with basic_consume"
seo_description: "Consume RabbitMQ messages in PHP with basic_consume and a callback, then keep the consumer alive with a wait loop so it processes messages as they arrive."
---

## The goal

The producer from the [last lesson](/course/rabbitmq-basics/first-producer-and-consumer/publishing-your-first-message)
left a message sitting in the `hello` queue. To consume RabbitMQ messages in PHP you
write a consumer that connects, subscribes to the queue, and prints every message it
receives. Unlike the producer, a consumer is a **long-running** program: it stays
connected and waits for messages instead of exiting.

## Subscribe with a callback

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    echo ' [x] Received ', $message->getBody(), "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
```

## What each part does

**Declare the queue again.** The consumer might start before the producer has ever run,
so it declares `hello` too. Declaring is idempotent, so this is safe.

**The callback.** `$callback` is a plain function that RabbitMQ calls once for every
delivered message. It receives an `AMQPMessage`, and `$message->getBody()` returns the
payload you published. For now we just print it; in a real app this is where you'd do the
work (send an email, resize an image, and so on).

**Subscribing.** `basic_consume` registers the callback against the queue. Its arguments
are worth knowing:

```php
$channel->basic_consume(
    'hello',     // queue to consume from
    '',          // consumer tag - empty lets the server generate one
    false,       // no_local
    true,        // no_ack - auto-acknowledge (more on this soon)
    false,       // exclusive
    false,       // nowait
    $callback    // the function to run per message
);
```

The `true` in the fourth slot is **auto-acknowledge**: RabbitMQ considers a message
delivered the instant it's handed over. That's the simplest mode and fine for now - we'll
see why it's risky, and how to fix it, in
[message acknowledgements](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements).

**The wait loop.** `basic_consume` only registers interest; it doesn't block. The loop

```php
while ($channel->is_consuming()) {
    $channel->wait();
}
```

does the actual work: `wait()` blocks until the next message (or protocol frame) arrives,
runs your callback, and loops again. `is_consuming()` stays true as long as at least one
consumer is active, so the script keeps running until you stop it with `CTRL+C`.

Run `php receive.php`. It prints the waiting message, then sits there. Run the producer
again in another terminal and you'll see the new message appear instantly.

Worth knowing before you build anything real: your callback runs on the very thread that
`wait()` is blocking on. There's no concurrency inside one consumer process. A slow
callback - a five-second HTTP call, say - freezes the whole loop, and nothing else gets
processed until it returns. When one worker isn't keeping up, you run more of them, which
is precisely the next lesson.

## Common mistake

Forgetting the wait loop. If you call `basic_consume` and then let the script end, nothing
happens - you registered a callback but never gave the client a chance to receive
anything. A consumer needs a loop that keeps calling `wait()` (or the shorthand
`$channel->consume()`, which wraps the same loop for you).

## FAQ

### Why doesn't the consumer just exit like the producer did?

Because its job is to keep processing messages as they arrive. A producer sends and
leaves; a consumer is a worker that stays up. In production you'd run it as a supervised
process that restarts if it dies.

### What is the consumer tag for?

It's a name for this subscription. Passing an empty string lets the server assign one.
You only need to set it yourself if you plan to cancel a specific consumer later by name.

### Can several consumers read the same queue?

Yes - and that's exactly what we do next in
[work queues](/course/rabbitmq-basics/first-producer-and-consumer/work-queues), where two
workers share the load of one queue.
