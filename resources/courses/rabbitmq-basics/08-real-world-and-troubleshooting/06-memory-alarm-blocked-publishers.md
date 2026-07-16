---
title: "RabbitMQ memory alarm and blocked publishers"
slug: memory-alarm-blocked-publishers
seo_title: "RabbitMQ Blocked Publishers - Memory Alarm Fix"
seo_description: "Fix RabbitMQ publishers hanging with connection.blocked: a memory or disk alarm triggered flow control. Free resources or raise the watermark to unblock."
---

A RabbitMQ memory alarm shows up as a publisher that stops dead: no error, no crash - the
`basic_publish` call just hangs, or you see a `connection.blocked` notification and
everything freezes. Consumers still drain the queue, but nothing new can go in. This isn't a
bug in your code. RabbitMQ has hit a **resource alarm** and deliberately paused your
publishers to protect itself. Here's what happened and how to clear it.

## What flow control is doing

RabbitMQ watches two resources: **memory** and **free disk space**. Each has a threshold
called a **watermark**. When usage crosses it, the broker raises an alarm and applies **flow
control**: it stops reading from any connection that is **publishing**, so no new messages
can arrive. This is the broker's survival instinct - if it kept accepting messages while
running out of memory or disk, it would crash and you'd lose everything unwritten.

The blast radius surprises people: one alarm blocks *every* publishing connection on the
node, not just the one feeding the queue that filled up. A backlog in your image-processing
queue can freeze publishes to a completely unrelated queue, because both live on the same
broker and the alarm is set node-wide. That is often the first real clue - an unrelated
feature stops sending at the same moment.

The key detail: **consuming still works**. Only publishers are blocked. So the escape hatch
is built in - let consumers drain the backlog and usage falls back below the watermark, at
which point the alarm clears and publishers resume automatically. A blocked publisher is a
symptom that messages are going in faster than they come out, and the broker is out of room
to buffer the difference. This is the mechanism from
[memory and disk alarms](/course/rabbitmq-basics/operating-in-production/memory-and-disk-alarms).

## Confirm it's an alarm

Don't guess - ask the broker:

```bash
docker exec rabbitmq rabbitmq-diagnostics alarms
```

If an alarm is active you'll see something like `memory` or `disk` listed. You can also see
it in the management UI: the node turns red on the **Overview** page with a message about a
memory or disk resource alarm. And in your client, a well-behaved AMQP library will have
received a `connection.blocked` frame - many log it.

```text
memory resource limit alarm set on node rabbit@...
```

## Fix a memory alarm

By default the memory watermark is **40% of the machine's RAM**. Crossing it usually means
messages are piling up in queues faster than consumers remove them. Two ways out:

**1. Drain the backlog (the real fix).** Get consumers working faster so messages leave. In
the Docker stack, scale up workers:

```bash
docker compose up -d --scale worker=4
```

As the queues empty, memory falls back under the watermark and the alarm clears on its own -
no restart needed. If a queue is enormous because messages are unroutable or nobody consumes
it, delete or fix that queue.

**2. Raise the watermark (buys time, not a cure).** If the box genuinely has RAM to spare,
you can lift the limit:

```bash
docker exec rabbitmq rabbitmqctl set_vm_memory_high_watermark 0.6
```

That moves the threshold to 60% of RAM. Use it as breathing room while you fix the real
imbalance, not as a permanent answer - eventually a bigger buffer just delays the same wall.

## Fix a disk alarm

The disk alarm fires when **free** space drops below the limit (default 50 MB, which is
low - real deployments set it higher). RabbitMQ needs disk headroom to page messages out of
memory and to store durable messages safely. Free up space:

```bash
# See what's using the disk
docker exec rabbitmq df -h

# The usual culprit is a huge backlog of persistent messages -
# draining consumers frees it as messages are acked and removed.
```

If the broker's data volume shares a disk with something noisy (logs, other containers),
clearing that space clears the alarm. As with memory, once free space climbs back above the
threshold the alarm lifts and publishers unblock automatically.

## Why not just retry the publish?

A blocked publish isn't a failure to retry - it's a deliberate pause. Retrying in a tight
loop does nothing but burn CPU, because the broker won't read the connection until the alarm
clears. The correct response is to **let the alarm clear**: your publisher will unblock by
itself the moment usage drops. If you must react in code, listen for the `connection.blocked`
and `connection.unblocked` events your client exposes and stop publishing until unblocked,
rather than hammering.

## Preventing it

Alarms are the broker telling you the system is unbalanced. To keep them from firing:

- Keep consumers ahead of producers - scale workers or speed up job handling.
- Use TTLs and dead-letter queues so stuck messages don't accumulate forever
  ([message and queue TTL](/course/rabbitmq-basics/reliability-and-delivery/message-and-queue-ttl)).
- Give the broker enough RAM and disk for your peak backlog, and monitor usage in the UI
  before it hits the watermark
  ([monitoring with the UI](/course/rabbitmq-basics/operating-in-production/monitoring-with-the-ui)).

## FAQ

### My publisher is frozen but there's no error. Is it broken?

Probably not. If a memory or disk alarm is active, RabbitMQ has blocked publishers on
purpose and your `basic_publish` will hang until the alarm clears. Run
`rabbitmq-diagnostics alarms` to confirm, then free resources - don't restart the app.

### Will raising the memory watermark fix it for good?

No. It gives you more buffer, but if messages keep arriving faster than they're consumed
you'll hit the higher limit too. Raising the watermark is a stopgap; the durable fix is
draining faster or slowing production.

### Do consumers get blocked by an alarm as well?

No - only publishers. That's intentional: the broker keeps letting consumers pull messages
out so the backlog shrinks and the alarm can clear. If your consumers are up and working,
the block usually resolves itself.
