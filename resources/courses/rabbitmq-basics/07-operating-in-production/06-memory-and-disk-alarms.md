---
title: "Memory and disk alarms"
slug: memory-and-disk-alarms
seo_title: "RabbitMQ Memory Alarm and Disk Alarm Explained"
seo_description: "How the RabbitMQ memory alarm and free-disk alarm work: the high-watermark thresholds, why the broker blocks publishers with flow control, and how to clear it."
---

## RabbitMQ protects itself by blocking you

The RabbitMQ memory alarm exists to stop the broker from killing itself. RabbitMQ keeps a lot in
memory and needs free disk to keep writing messages. Let a queue grow without limit and it would
eventually exhaust memory or disk and crash, losing everything. So it watches two thresholds and
raises an **alarm** when either is crossed. While an alarm is active, RabbitMQ **stops accepting new
messages from publishers** - it applies flow control and blocks them - until the pressure eases.
Consumers keep running, so the queue can drain.

This is deliberate. A blocked publisher is annoying, but it's far better than a dead broker.

## The two alarms

**Memory high-watermark.** RabbitMQ sets a memory limit, by default a fraction of the machine's
RAM (0.4, i.e. 40%). When the node's memory use crosses that watermark, the memory alarm trips and
publishers are blocked.

**Free-disk alarm.** RabbitMQ also watches free disk space against a configured limit (default
around 50 MB free). If free space drops below it, the disk alarm trips and, again, publishers are
blocked - because without disk it can't safely persist messages.

You can see an active alarm on the management UI **Overview** and **Nodes** pages, and in
`rabbitmqctl status` from
[earlier in this chapter](/course/rabbitmq-basics/operating-in-production/rabbitmqctl-basics).

Worth knowing if you run a [cluster](/course/rabbitmq-basics/operating-in-production/clustering-and-ha):
an alarm is not a single-node affair. When one node trips its memory or disk threshold, publishers
are blocked across the **whole** cluster, not just on that node. It only takes one hot node to stall
everyone's publishing, which is why you watch every node's memory, not just the busiest one.

## What "blocked" looks like

To your producer, a blocked broker looks like publishing that suddenly hangs. The connection isn't
dropped - RabbitMQ just stops reading from it, so `basic_publish` stalls. If you're using publisher
confirms from
[chapter 5](/course/rabbitmq-basics/reliability-and-delivery/publisher-confirms), the confirms stop
arriving. Consumers, meanwhile, keep receiving and acking normally. That combination - producers
stuck, consumers fine - is the tell-tale sign of an alarm.

## How to clear it

An alarm clears **on its own** the moment the underlying pressure drops back below the threshold.
The real fix is to remove the cause, not to raise the limit:

- **Let consumers catch up.** The most common trigger is a backlog of Ready messages eating memory
  because consumers stopped or fell behind. Get consumers running again and the queue drains, memory
  falls, and the alarm lifts.
- **Free disk space.** For a disk alarm, delete logs or old data, or grow the volume, until free
  space is back above the limit.
- **Adjust the threshold only if it's genuinely wrong.** You can raise the memory watermark or lower
  the disk limit in the broker config, but do that only when the default doesn't fit your machine -
  not as a way to silence a real backlog.

## Common mistake

Ignoring the alarm and assuming publishing "randomly broke". The block is the system working as
designed - it's telling you a queue is growing faster than it drains, or the disk is filling. The
answer is to look at Ready and Unacked counts (from the
[monitoring lesson](/course/rabbitmq-basics/operating-in-production/monitoring-with-the-ui)) and
fix the consumer or the disk, not to restart the broker and hope.

## FAQ

### Are messages lost when an alarm trips?

No. Nothing already in a queue is dropped. RabbitMQ simply stops accepting new publishes until the
alarm clears, so producers wait rather than the broker crashing.

### Why are my consumers fine while producers are stuck?

Flow control blocks publishers on purpose so the queue can drain. Consumers are the way out of the
alarm, so they keep working.

### Should I just raise the memory watermark?

Only if the default truly doesn't suit your hardware. Most of the time a tripped alarm means a real
backlog or a filling disk - raising the limit just delays the same crash.
