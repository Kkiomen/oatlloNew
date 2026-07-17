---
title: "Queues"
slug: queues
seo_title: "RabbitMQ Queue Explained: FIFO Message Buffers"
seo_description: "A RabbitMQ queue is an ordered FIFO buffer that holds messages until a consumer takes them. See how ordering works and why the queue lives inside the broker."
---

## What a queue is

A **queue** is the mailbox of RabbitMQ. It is an ordered buffer that holds messages
until a consumer is ready to take them. That's the whole job: **store messages, keep
them in order, hand them out**.

A queue lives **inside the broker**. It is not part of your producer and not part of
your consumer - it sits in the middle, on the RabbitMQ server, which is why a message
survives even when the consumer is offline.

## A queue is a buffer

The word "buffer" is important. Producers and consumers rarely run at the same speed. A
producer might publish a burst of 10,000 messages in a second, while a single consumer
can only handle a hundred per second. Without a buffer, those extra messages would have
nowhere to go.

The queue absorbs that difference. Messages pile up in the queue when they arrive faster
than they can be handled, and they drain out as consumers catch up. The producer never
has to slow down to match the consumer, and the consumer never has to rush.

## First in, first out

By default a queue is **FIFO**: *first in, first out*. The first message to arrive is
the first message handed to a consumer. Messages keep the order they were published in,
like people waiting in a line at a shop.

```text
publish order:   A  ->  B  ->  C
                 |       |       |
              [ A | B | C ]   <- the queue
                 |
delivery order:  A first, then B, then C
```

FIFO is a sensible default, but it is not an ironclad promise in every situation. Once
you add several consumers, or messages get redelivered after a failure, the exact order
a consumer *sees* can shift. We'll come back to ordering when it matters, in later
chapters. For now, picture a simple, orderly line.

## What lives in a queue

A queue holds messages, and it also has a **name** so producers and consumers can refer
to it - for example `emails` or `image-processing`. Beyond the name, a queue has a few
properties that control how it behaves: whether it [survives a broker restart](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence), whether it
deletes itself when unused, and so on. We'll set those properties deliberately when we
create queues in code, so don't worry about them yet.

One thing to file away now, because it surprises people later: a queue only exists once
something declares it, and both the producer side and the consumer side usually declare
the same queue. That is not redundant. Declaring the same queue twice is harmless, and
having both sides do it means neither depends on the other starting first - whoever
connects earliest creates the queue, and the other simply finds it already there.

## A message leaves the queue when it's done

A message does not disappear the instant a consumer receives it. It leaves the queue only
once the consumer confirms it has finished - an **acknowledgement**. Until then the
broker keeps a copy, so a consumer that crashes mid-job does not silently lose the work.
We introduce acknowledgements later in
[this chapter](/course/rabbitmq-basics/core-concepts/messages-and-acknowledgements).

## Common mistake

A very common early assumption is that producers publish **straight into a queue**. In
the simplest examples it can even look that way. But that is not how RabbitMQ actually
works: producers publish to an **exchange**, and the exchange decides which queue (or
queues) the message lands in. That's the subject of the [next lesson](/course/rabbitmq-basics/core-concepts/exchanges), and it is the single
most important idea in this chapter.

## FAQ

### Where does a queue actually live?

Inside the broker - the RabbitMQ server. It is not stored in your application. That's why
a queued message waits safely even if every consumer is turned off.

### Do messages stay in order?

By default a single queue is FIFO, so a single consumer sees messages in the order they
were published. With multiple consumers or redelivered messages the observed order can
change, which we cover later. Don't rely on strict ordering until you understand those
cases.

### What happens if the queue keeps growing and nobody consumes it?

It keeps buffering until it runs into limits like memory or a configured maximum length.
A queue that only fills and never drains is a warning sign, and RabbitMQ has alarms for
it - a topic for the production chapter, not now.
