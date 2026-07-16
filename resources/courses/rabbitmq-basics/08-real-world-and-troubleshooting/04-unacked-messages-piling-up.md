---
title: "RabbitMQ unacked messages piling up"
slug: unacked-messages-piling-up
seo_title: "RabbitMQ Unacked Messages Keep Growing - How to Fix"
seo_description: "Fix RabbitMQ unacked messages piling up: a consumer took messages but never acknowledged them. Spot the cause in the UI and fix forgotten acks or a bad prefetch."
---

RabbitMQ unacked messages piling up look like this: you open the management UI, watch a
queue, and the **Unacked** column keeps climbing while **Ready** drains. Messages are being
delivered but never confirmed. Left alone it reads like a stuck queue, and eventually the
consumer stops receiving anything at all. Here is what "unacked" means, why it grows, and how
to fix each cause.

## What "unacked" actually means

RabbitMQ shows three numbers per queue:

- **Ready** - messages waiting, not yet sent to any consumer.
- **Unacked** - messages **delivered** to a consumer but **not yet acknowledged**.
- **Total** - the two added together.

An unacked message is in limbo. The broker has handed it out and is holding it in memory,
waiting for the consumer to say "done" with a `basic_ack`. Until that ack (or the consumer
disconnects), the broker will not delete it and will not give it to anyone else. A few
unacked messages is normal - that's just work in progress. **Unacked that only ever grows**
is the bug.

This is also why a broken consumer looks *dead* rather than *slow*. Unacked messages count
against the consumer's prefetch limit, so a consumer that stops acking fills its prefetch
window and then RabbitMQ sends it nothing more. With a prefetch of 10 it goes silent after
exactly 10 deliveries. It has not disconnected; it is simply full of messages it never
finished. This builds directly on
[message acknowledgements](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements)
and the [acknowledgements deep dive](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive).

## Cause 1: the consumer forgot to ack

The most common cause. You're consuming in manual-ack mode but your callback never calls
`basic_ack`. Every delivered message stays unacked forever.

```php
// BROKEN: no ack, so every message sticks in "unacked".
$channel->basic_consume($queue, '', false, false, false, false, function ($msg) {
    handle($msg->body);
    // ...and then nothing. The broker is still waiting.
});
```

The fix is to acknowledge after the work succeeds:

```php
$channel->basic_consume($queue, '', false, false, false, false, function ($msg) {
    handle($msg->body);
    $msg->ack(); // tell the broker this one is done
});
```

If you're on Laravel, the driver acks for you when the Job's `handle()` returns without
throwing - so this manual mistake is a hand-rolled-consumer problem, not a Laravel one.

## Cause 2: an exception is thrown before the ack

Your ack is there, but the code above it throws, so the ack line never runs. The message
stays unacked while the consumer moves on to the next delivery - or crashes.

```php
function ($msg) {
    handle($msg->body); // throws on a bad message
    $msg->ack();        // never reached
}
```

Wrap the work so you make a deliberate decision on failure - ack, or reject/nack so the
message is redelivered or dead-lettered rather than silently stuck:

```php
function ($msg) {
    try {
        handle($msg->body);
        $msg->ack();
    } catch (\Throwable $e) {
        // don't requeue a poison message forever - send it for dead-lettering
        $msg->reject(false);
    }
}
```

Blindly requeueing a message that always throws creates a **poison message** loop - it's
redelivered, throws, redelivered, forever. Route persistent failures to a dead-letter queue
instead, as in
[retries and dead-letter queues](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues).

## Cause 3: prefetch too high with slow work

Even with correct acks, a high (or unlimited) prefetch lets one consumer grab a large batch
of messages up front. They all show as unacked until that consumer works through them one by
one. If each takes a while, the Unacked number stays large the whole time - and worse, other
idle consumers get nothing because the messages are already reserved.

Set a prefetch that matches how much a consumer can genuinely process at once. For slow jobs,
one at a time:

```php
$channel->basic_qos(null, 1, null); // deliver 1 unacked message at a time
```

This is [fair dispatch / prefetch](/course/rabbitmq-basics/first-producer-and-consumer/fair-dispatch-prefetch)
doing exactly its job: with prefetch 1, a consumer only ever holds one unacked message, so
the number reflects real in-flight work, not a hoarded backlog.

## Cause 4: a slow or hung consumer

Sometimes the consumer isn't broken, just stuck - blocked on a slow database call, an
external API with no timeout, or an infinite loop. It's holding unacked messages it hasn't
finished. In the UI the queue's **Unacked** sits high and flat while **Ready** doesn't move.

Check whether the consumer is alive and doing anything:

```bash
docker compose logs -f worker
```

If it's frozen, the fix is in the consumer: add timeouts to external calls, and let a
long-running worker be restartable. When the consumer disconnects, the broker returns its
unacked messages to Ready and hands them to another consumer - which is the whole safety
value of manual acks.

## How to spot which cause it is

The UI tells you a lot at a glance:

- **Unacked grows, Ready shrinks, worker logs are silent** - forgotten ack or an exception
  before the ack (causes 1 and 2).
- **Unacked is large and flat, one consumer, others idle** - prefetch too high (cause 3).
- **Unacked is stuck at a fixed number, worker is up but doing nothing** - hung consumer
  (cause 4).

Then click the queue and look at **Consumers**: if a consumer holds many unacked messages
and its ack rate is zero, you've found it.

## FAQ

### Will unacked messages ever get lost?

No. That's the point of manual acks. An unacked message is safe - if the consumer
disconnects or crashes without acking, the broker requeues it and redelivers it. The risk
is duplication (the work might run twice), not loss.

### Why do unacked messages block my other consumers?

Because they're already reserved for the consumer that holds them. The broker won't give a
delivered-but-unacked message to anyone else until it's acked or the holder disconnects. A
high prefetch on one slow consumer can starve the others - lower the prefetch.

### I'm using Laravel and see unacked piling up. What's wrong?

Usually a job that hangs or runs very long, so the worker holds it unacked the whole time.
Give jobs a `--timeout`, keep them short, and make sure the worker isn't blocked on an
external call without a timeout of its own.
