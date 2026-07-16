---
title: "Delivery guarantees"
slug: delivery-guarantees
seo_title: "RabbitMQ Delivery Guarantees: At-Least-Once vs At-Most-Once"
seo_description: "RabbitMQ at-least-once vs at-most-once delivery, why duplicates happen on redelivery, why exactly-once is a myth, and how idempotent consumers fix it."
---

People often ask "does RabbitMQ deliver each message exactly once?" It is the wrong
question, and chasing the wrong answer leads to fragile systems. This lesson explains the
two guarantees you can actually pick - at-most-once and at-least-once - why duplicates are
unavoidable under at-least-once delivery, and the one design habit that makes them a
non-issue.

## Two honest guarantees

There are two delivery guarantees a message system can realistically offer:

- **At-most-once**: every message is delivered zero or one times. It is never duplicated,
  but it *can* be lost. This is what you get with no acknowledgements - fire and forget.
- **At-least-once**: every message is delivered one or more times. It is never lost, but it
  *can* be duplicated. This is what you get with acknowledgements turned on.

Notice you are choosing which risk to accept: **losing** messages, or **repeating** them.
There is no free option that avoids both.

## RabbitMQ's default is at-least-once

Once you use manual consumer acks (from
[acknowledgements deep dive](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive)),
RabbitMQ gives you at-least-once. The reason a message can be delivered more than once is
exactly the safety mechanism that stops it being lost:

- A consumer receives a message and does the work.
- Before it can send the ack, the process crashes (or the network drops).
- The broker never got the ack, so it assumes the message was not handled and
  **redelivers** it to another consumer.

The work happened, but from the broker's point of view it did not - so the same message
runs a second time. This is not a bug. It is the price of never losing a message.

## Exactly-once is a myth to design around

"Exactly-once delivery" over a network is essentially impossible, because the acknowledgement
itself can be lost. Look again at the crash above: the broker genuinely cannot tell the
difference between "the consumer died before doing the work" and "the consumer did the work
and died before acking". To be safe it must redeliver, which means a duplicate.

No amount of configuration removes this. Systems that advertise "exactly-once" achieve it by
making the *processing* effectively-once on top of at-least-once *delivery* - which is
exactly what you will do next.

## The fix: make consumers idempotent

**Idempotent** means running the same operation twice has the same effect as running it
once. If your consumer is idempotent, a duplicate delivery is harmless - the second run
simply does nothing new.

The usual technique is to give each message a unique id and record the ids you have already
processed:

```php
$callback = function ($msg) {
    $props = $msg->get_properties();
    $id = $props['message_id'] ?? null;

    if ($id !== null && alreadyProcessed($id)) {
        // Seen it. Ack and move on - do not repeat the work.
        $msg->getChannel()->basic_ack($msg->getDeliveryTag());
        return;
    }

    process($msg->body);
    markProcessed($id);

    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};
```

The producer sets the id when publishing:

```php
use PhpAmqpLib\Message\AMQPMessage;

$msg = new AMQPMessage($body, [
    'message_id'   => bin2hex(random_bytes(16)),
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);
```

Storing processed ids in a database with a unique constraint - or letting the operation
itself be naturally idempotent (an `UPDATE` that sets a fixed value, an upsert) - means a
redelivered message changes nothing the second time. Now duplicates do not matter, and you
can enjoy the safety of at-least-once without the downside.

Lean on the database, not the `if` check. The `alreadyProcessed()` read above hides a race:
if two workers pick up copies of the same message at once, both can read "not processed"
before either writes, and both run the work. The version that actually holds under
concurrency is to make the id a unique key and let the *insert* fail on the duplicate - a
caught constraint violation is your real signal that the message was already handled, not a
prior `SELECT`.

## Common mistake: assuming exactly-once and skipping idempotency

The most expensive bug in this chapter is writing a consumer that charges a card, sends an
email, or ships an order, and assuming each message arrives once. It will not. One day a
worker restarts at the wrong moment and a customer is charged twice. Treat every consumer
as if it *will* see duplicates, because eventually it will.

## FAQ

### Can I just turn off redelivery to avoid duplicates?

You can consume with no acks (at-most-once), but then a crash *loses* the message instead
of repeating it. For anything that matters, losing work is worse than repeating it. Keep
at-least-once and make the consumer idempotent.

### How likely are duplicates in practice?

Rare, but not rare enough to ignore. They cluster exactly when things go wrong - deploys,
crashes, network blips - which is precisely when you least want a double charge. Design for
them from the start.

### Where do I get a message id if the producer did not set one?

Ideally the producer sets `message_id`. If it cannot, derive a stable key from the payload
itself (for example, hash the order number and action) so the same logical event always
maps to the same id.
