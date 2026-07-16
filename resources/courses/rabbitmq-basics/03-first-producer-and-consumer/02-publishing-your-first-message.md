---
title: "Publishing your first message"
slug: publishing-your-first-message
seo_title: "Publish a RabbitMQ Message with php-amqplib in PHP"
seo_description: "Publish a message with php-amqplib: declare a queue, build an AMQPMessage, and send it to the default exchange with basic_publish, explained line by line."
---

## The goal

Time to send something real. To publish a message with php-amqplib the producer makes
sure a queue exists, drops one message into it, and disconnects. The
[next lesson](/course/rabbitmq-basics/first-producer-and-consumer/consuming-messages)
writes the consumer that reads it back.

Routing stays as simple as it gets here: the **default exchange**. As you saw in
[exchanges](/course/rabbitmq-basics/core-concepts/bindings-and-routing-keys), a producer
never publishes straight to a queue - it always publishes to an exchange. The default
exchange is a special built-in one that delivers a message to the queue whose name equals
the **routing key**. That gives us a "send straight to this queue" shortcut, perfect for
a first example. Named exchange types come later in the course.

## Declare the queue and publish

```php
<?php

require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

$message = new AMQPMessage('Hello World!');
$channel->basic_publish($message, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();
```

## What each part does

**Declaring the queue.** `queue_declare('hello', false, false, false, false)` makes sure a
queue named `hello` exists. If it isn't there, RabbitMQ creates it; if it already is,
nothing happens. The four `false` flags after the name are:

- **passive** (`false`) - actually create the queue, don't just check for it.
- **durable** (`false`) - don't survive a broker restart. We'll turn this on in
  [durability and persistence](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence).
- **exclusive** (`false`) - allow any connection to use it, not just this one.
- **auto_delete** (`false`) - keep the queue even when no consumer is attached.

Declaring is safe to repeat. It's good practice for **both** the producer and the consumer
to declare the queue, so whoever runs first, the queue is guaranteed to exist.

**Building the message.** `new AMQPMessage('Hello World!')` wraps your payload. To
RabbitMQ the body is just bytes - here it's a short string, but it's usually JSON.

**Publishing.** `basic_publish($message, '', 'hello')` sends it. The three arguments are:

- the **message** to send,
- the **exchange** name - an empty string `''` means the default exchange,
- the **routing key** - `'hello'`, the name of our queue.

Because we used the default exchange, the routing key *is* the queue name, so the message
lands in `hello`.

One thing that surprises people: `basic_publish` returns nothing and never blocks waiting
for the broker to confirm the message was stored. The call succeeds the moment the client
hands the bytes off. A publish that "worked" is not proof the message reached a queue -
which is exactly why the mistake below is so quiet.

Run it with `php send.php`. Nothing prints on the consumer side yet - the message just
waits in the queue. Open the [management UI](/course/rabbitmq-basics/getting-started/the-management-ui-tour)
and you'll see the `hello` queue with 1 message ready.

## Common mistake

Publishing to a queue that was never declared. A producer never talks to a queue
directly - it publishes to an exchange with a routing key. If you use the default
exchange but no queue with that name exists, the message is silently dropped: the default
exchange has nowhere to route it. There's no error. Always declare the queue before (or
alongside) publishing.

## FAQ

### Where does the message go if no one is consuming?

It sits in the queue and waits. That's the whole point of a broker - the producer and
consumer don't have to be online at the same time. Start a consumer later and the message
is delivered then.

### Can I send more than a string?

Yes. The body is arbitrary bytes, so encode structured data (usually with `json_encode`)
and decode it on the consumer side. RabbitMQ doesn't care what's inside.

### Do I have to close the connection?

You should. Closing flushes and releases the connection cleanly. Leaving connections open
in a long-running script is fine, but a short "publish and exit" script should close both
the channel and the connection.
