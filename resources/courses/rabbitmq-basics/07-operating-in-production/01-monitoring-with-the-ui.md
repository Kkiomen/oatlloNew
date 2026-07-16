---
title: "Monitoring with the management UI"
slug: monitoring-with-the-ui
seo_title: "Monitor RabbitMQ Queues in the Management UI"
seo_description: "Monitor RabbitMQ queues in the management UI: Ready vs Unacked, publish and deliver rates, consumer count and memory, and what a rising backlog means."
---

## Why monitor RabbitMQ from the UI

Most message-system failures don't announce themselves with an error on screen. A queue just
grows. Producers keep publishing, consumers fall behind, and the backlog piles up until memory
runs out. Monitoring RabbitMQ queues from the management UI is the fastest way to catch that
before it turns into an outage. You met the UI briefly in
[chapter 1](/course/rabbitmq-basics/getting-started/the-management-ui-tour); now you'll read
it like an operator.

Open `http://localhost:15672` and go to the **Queues** tab.

## Ready vs Unacked

Every queue shows two counts that matter more than any other:

- **Ready** - messages sitting in the queue, waiting to be delivered to a consumer.
- **Unacked** (unacknowledged) - messages already delivered to a consumer that have not been
  acknowledged yet. You learned about acks in
  [chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/message-acknowledgements).

The plain **Total** (or `messages`) column is just these two added together, so a big total with
Unacked near zero is a very different situation from a big total that's mostly Unacked. Always
split it.

In a healthy system both numbers stay low and bounce around near zero, because consumers keep
up with producers. The shape of the problem tells you where it is:

- **Ready keeps growing** - messages arrive faster than consumers process them. Either you
  don't have enough consumers, or they are too slow, or they crashed and nobody is reading the
  queue at all.
- **Unacked keeps growing** - consumers took messages but never acked them. Usually a consumer
  is stuck (a slow database call, an infinite loop) or your code forgot to acknowledge. Those
  messages are locked to that consumer and will only requeue if the connection drops.

## Rates and consumers

On the **Overview** tab and each queue page you'll see message rates as small graphs:

- **Publish rate** - messages coming in per second.
- **Deliver / ack rate** - messages going out to consumers and being acknowledged.

When publish rate sits above the ack rate for a while, the backlog is growing - that's the same
story the Ready count tells, just as a trend. The **Consumers** column shows how many consumers
are attached to a queue. If it reads `0` on a queue that should be worked, no one is listening -
that alone explains a climbing Ready count.

One thing worth knowing about those graphs: the management plugin samples stats on an interval,
it does not stream every event. A burst that spikes and drains between two refreshes can be
invisible on the graph even though it happened. Trust the trend across a minute, not a single
reading.

## Memory

The **Overview** tab and the **Nodes** section show the node's memory use. RabbitMQ holds a lot
in RAM, and a large backlog of Ready messages is one of the main things that fills it. Watching
memory climb alongside a growing queue is the early warning that an alarm is coming - alarms are
covered later in
[this chapter](/course/rabbitmq-basics/operating-in-production/memory-and-disk-alarms).

## Common mistake

Treating a non-zero Unacked count as a bug by itself. With prefetch (see
[chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/fair-dispatch-prefetch)) a busy
consumer will always hold a few unacked messages while it works - that is normal. The problem is
Unacked that keeps **rising and never drains**, which means work started but never finished.

## FAQ

### What counts as a "healthy" Ready number?

There is no fixed value - it depends on your throughput. What matters is the trend: a Ready count
that keeps climbing over minutes is unhealthy even if the absolute number is small. One that
spikes and drains back down is fine.

### Why is my Unacked count stuck and not going down?

A consumer is holding those messages without acking. It is either still processing (slow work),
frozen, or it never calls acknowledge in the code. If the consumer disconnects, RabbitMQ requeues
them and they move back to Ready.

### Does the management UI cost performance?

The plugin collects stats periodically, which adds a little overhead - fine for normal use. On a
very busy broker you can lengthen the collection interval, but for learning and most production
loads the default is perfectly acceptable.
