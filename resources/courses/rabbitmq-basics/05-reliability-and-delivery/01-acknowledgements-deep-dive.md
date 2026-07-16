---
title: "Acknowledgements deep dive"
slug: acknowledgements-deep-dive
seo_title: "RabbitMQ Manual Acknowledgements in PHP Deep Dive"
seo_description: "How RabbitMQ manual acknowledgements work: the unacked state, redelivery when a consumer crashes, basic_nack and basic_reject with requeue, and redelivered."
---

In [chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements)
you turned manual acknowledgements on and called ack after the work was done. Now we go
deeper into how RabbitMQ manual acknowledgements actually behave: the states a message
passes through, what happens when a consumer crashes mid-job, and how to reject a message
on purpose.

## The lifecycle of one delivery

When you consume with manual acks (the `no_ack` flag set to `false`), every message you
receive goes through three states inside the broker:

1. **Ready** - sitting in the queue, waiting for a consumer.
2. **Unacked** - handed to your consumer, but not yet acknowledged. The broker still owns
   it and remembers who has it.
3. **Gone** - you acknowledged it, so the broker deletes it for good.

The whole point of manual acks is that step 2 is a holding pen. The broker keeps a copy
of every unacked message. Nothing is deleted just because it was delivered.

## Acknowledging after the work is done

```php
$channel->basic_qos(null, 1, false);

$callback = function ($msg) {
    // Do the real work first.
    process($msg->body);

    // Only now tell the broker it can forget this message.
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};

// The fourth argument (no_ack) is false, so acks are manual.
$channel->basic_consume('tasks', '', false, false, false, false, $callback);
```

`getDeliveryTag()` is a number the broker assigns per channel to identify this exact
delivery. You pass it back so the broker knows *which* unacked message you mean.

## What happens when a consumer dies

Say your worker pulls a message, starts processing, and the process is killed before it
acks - a crash, a deploy, a lost network connection. Because the message was still in the
**unacked** state, the broker notices the channel closed and puts the message **back to
ready**. Another consumer (or the same one after restart) will get it again.

This is why acking *after* the work matters. Ack before, and a crash mid-job loses the
message. Ack after, and a crash just means the message is redelivered.

## The redelivered flag

When a message comes back a second time, the broker sets a `redelivered` flag on it. You
can read it to know "I may have seen this before":

```php
$callback = function ($msg) {
    if ($msg->isRedelivered()) {
        // This might be a second attempt. Be careful about repeating side effects.
    }
    process($msg->body);
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};
```

The flag is a hint, not a guarantee of duplication - it means "this was handed out before
and not acked". One thing it is *not* is a counter: `redelivered` is a boolean, so it tells
you a message has been delivered at least twice, never that this is attempt number five. If
you need an attempt count, you have to track it yourself. We build on this idea in
[delivery guarantees](/course/rabbitmq-basics/reliability-and-delivery/delivery-guarantees).

## Rejecting a message: nack and reject

Sometimes the work fails and you do not want to silently ack. You have two methods:

```php
// basic_reject: one message, with or without requeue.
$msg->getChannel()->basic_reject($msg->getDeliveryTag(), true);  // put it back
$msg->getChannel()->basic_reject($msg->getDeliveryTag(), false); // drop it

// basic_nack: the same idea, but can also nack many at once.
$msg->getChannel()->basic_nack($msg->getDeliveryTag(), false, true); // requeue this one
```

- `requeue = true` sends the message back to the front of the queue to be tried again.
- `requeue = false` discards it - or, if the queue has a dead-letter exchange, routes it
  there instead (see [chapter 4](/course/rabbitmq-basics/exchanges-and-routing/dead-letter-exchanges)).

`basic_nack` is the RabbitMQ extension; `basic_reject` is the standard AMQP method. The
practical difference: only `basic_nack` supports the `multiple` flag.

## Multiple ack

Both `basic_ack` and `basic_nack` take a `multiple` flag. When `true`, it acts on **every
unacked message up to and including** this delivery tag on the channel:

```php
// Ack this message and all earlier unacked ones in one call.
$msg->getChannel()->basic_ack($msg->getDeliveryTag(), true);
```

This is a throughput optimisation for consumers that process in batches. Use it only when
you are sure every earlier message really is done - one `multiple` ack can wipe a whole
run of unacked messages. Delivery tags are also scoped per channel: a tag you got on one
channel is meaningless on another, so never cache a tag and try to ack it from elsewhere.

## Common mistake: infinite requeue of a poison message

If a message always fails and you always requeue it with `requeue = true`, it comes
straight back, fails again, and requeues forever - a "poison message" spinning in a tight
loop and burning CPU. Requeue is for *transient* failures (a database blip). For a message
that can never succeed, reject it **without** requeue and send it to a dead-letter queue,
which we set up in
[retries and dead-letter queues](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues).

## FAQ

### What is the difference between basic_reject and basic_nack?

They do the same job for a single message. `basic_nack` is a RabbitMQ extension that adds
a `multiple` flag so you can reject a whole batch at once. `basic_reject` only ever acts
on one message.

### If I never ack, do messages pile up forever?

They stay in the unacked state as long as the consumer is connected, counting against your
prefetch limit. Once that channel or connection closes, every unacked message goes back to
ready and is redelivered. Never acking is a bug that quietly stalls the queue.

### Does requeue put the message at the back of the line?

No. A requeued message goes back close to its original position (typically the front), so
it is usually retried almost immediately. That is exactly why a poison message loops so
fast without a dead-letter strategy.
