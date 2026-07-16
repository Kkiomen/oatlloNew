---
title: "Direct exchange"
slug: direct-exchange
seo_title: "RabbitMQ Direct Exchange Routing (php-amqplib)"
seo_description: "Declare a RabbitMQ direct exchange, bind a queue with a routing key and publish by exact match in php-amqplib. Route log messages by severity like error."
---

## How a direct exchange routes by exact match

A **RabbitMQ direct exchange** delivers a message to the queues whose binding key
**exactly matches** the message's routing key. It's the simplest routing you can control
yourself. The classic example is severity-based log routing: `info`, `warning`, `error`.

Say you want one queue that only receives `error` logs, and another that receives
everything. A direct exchange lets each queue subscribe to the keys it cares about.

## Declaring the exchange and binding a queue

First declare the exchange, then declare a queue, then bind the queue to the exchange
with the routing key you want to receive:

```php
use PhpAmqpLib\Message\AMQPMessage;

// name, type, passive, durable, auto_delete
$channel->exchange_declare('logs_direct', 'direct', false, true, false);

$channel->queue_declare('errors_only', false, true, false, false);
$channel->queue_bind('errors_only', 'logs_direct', 'error');
```

`queue_bind($queue, $exchange, $routingKey)` is the line that creates the routing rule:
"send messages published to `logs_direct` with routing key `error` to the `errors_only`
queue." Unlike the [default exchange](/course/rabbitmq-basics/exchanges-and-routing/the-default-exchange),
here you declare the binding explicitly, so the routing key does **not** have to equal
the queue name.

## Publishing with a routing key

The producer publishes to the exchange and sets the routing key to the severity:

```php
$msg = new AMQPMessage('disk is full', [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$channel->basic_publish($msg, 'logs_direct', 'error');
```

This message matches the `error` binding, so it lands in `errors_only`. A message
published with routing key `info` does not match that binding, so `errors_only` never
sees it.

## Many bindings, same key

A direct exchange isn't limited to one queue per key. You can bind several queues with
the **same** routing key, and all of them receive a copy. You can also bind one queue
with several keys:

```php
$channel->queue_bind('important', 'logs_direct', 'error');
$channel->queue_bind('important', 'logs_direct', 'warning');
```

Now the `important` queue receives both `error` and `warning` messages. This is how you
build flexible routing while still matching on exact keys.

Worth knowing: `queue_bind` is idempotent for a given (queue, exchange, key) triple.
Running the same bind twice does not create a second binding or a duplicate copy per
message, so a consumer that re-declares its bindings on every startup stays safe.

## Common mistake

Publishing with a routing key that no binding matches. A direct exchange has no fallback:
if nothing matches, the message is **silently dropped**. Publishing `critical` when only
`error` and `warning` are bound loses the message with no error. Always make sure a
binding exists for every key you publish, and remember the match is **case-sensitive** -
`Error` and `error` are different keys.

## FAQ

### What's the difference between the default exchange and a named direct exchange?

They're the same type. The default exchange is just a direct exchange with an automatic
queue-name binding you can't change. A named direct exchange lets you define your own
bindings, so routing keys and queue names can differ.

### Do I declare the exchange in both the producer and the consumer?

Yes, declare it in both. `exchange_declare` is idempotent - if the exchange already
exists with the same settings, the call does nothing. Declaring on both sides means
whichever process starts first sets it up.

### Can two queues share the same routing key?

Yes. Bind both queues to the exchange with the same key and each gets its own copy of
every matching message.
