---
title: "Quorum queues"
slug: quorum-queues
seo_title: "RabbitMQ Quorum Queues: Declare and Use Them"
seo_description: "RabbitMQ quorum queues are the modern replicated, durable queue type. Declare one with x-queue-type, see why they replaced mirrored queues, and weigh the trade-offs."
---

## The durable queue for production

A queue that lives on a single node has a single point of failure: if that node dies, the queue and
its messages go with it. For anything important you want the queue **replicated** across several
nodes so it survives losing one. RabbitMQ quorum queues are the modern way to do that, and on a
current broker they're the only built-in way left.

Quorum queues are RabbitMQ's recommended replicated, durable queue type. They keep copies of the
queue on multiple nodes and use a consensus algorithm (Raft) so a majority of nodes must agree
before a message is considered safely stored. Lose one node and the queue keeps working from the
survivors.

## Declaring one

A quorum queue is just a normal queue with one extra declaration argument: `x-queue-type` set to
`quorum`. In php-amqplib you pass it as a queue argument (an `AMQPTable`):

```php
use PhpAmqpLib\Wire\AMQPTable;

$args = new AMQPTable(['x-queue-type' => 'quorum']);

// name, passive, durable, exclusive, auto_delete, nowait, arguments
$channel->queue_declare('orders', false, true, false, false, false, $args);
```

Two rules that fall out of what quorum queues are:

- They are **always durable** - you declare them with the durable flag set to `true` (you met
  durability in
  [chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/durability-and-persistence)).
  A non-durable quorum queue makes no sense and isn't allowed.
- The `x-queue-type` argument is fixed at declaration time. You cannot convert an existing classic
  queue into a quorum queue - you declare a new one and move traffic over.

## Why they replaced mirrored queues

Older RabbitMQ replicated queues with a feature called **classic mirrored queues** (a policy that
copied a classic queue to other nodes). They were hard to operate and could lose or duplicate
messages during network trouble. Quorum queues were built to fix exactly that, with a proven
consensus algorithm underneath.

This matters today because **classic mirrored queues were removed in RabbitMQ 4.0**. On a current
broker, quorum queues are the way to get a replicated queue - there is no mirroring policy to fall
back to.

## Trade-offs

Quorum queues are not free:

- They need a **cluster** with enough nodes to form a majority - three nodes is the usual minimum
  (clustering is the [next lesson](/course/rabbitmq-basics/operating-in-production/clustering-and-ha)).
  On a single node you can declare one, but you get no real replication.
- They use **more resources** than a classic queue, because every message is written to several
  nodes and to disk.
- They're designed for **durable, longer-lived queues**, not for huge numbers of short-lived or
  temporary queues.

There's a subtlety behind that resource cost. A classic queue can hold a message in memory and
only touch disk if you asked for persistence; a quorum queue has no such mode. Because durability
is baked in, every message is written to disk on a majority of replicas before it counts as safe.
That's the whole point, but it's also why a quorum queue is the wrong tool for a firehose of
throwaway messages: you'd be paying replicated disk writes for data you never intended to keep.

For the queues that carry work you can't afford to lose - orders, payments, jobs - that cost is
exactly what you want to pay.

## Common mistake

Expecting replication from a quorum queue on a single-node broker. The queue type is set, but with
one node there's nothing to replicate to, so it behaves like a durable classic queue and gives you
no high availability. Replication only kicks in once the queue has multiple nodes to live on.

## FAQ

### Should every queue be a quorum queue?

No. Use them for durable queues whose messages matter. For temporary, exclusive or very
high-churn queues, a classic queue is lighter and more appropriate.

### Do I change my producer or consumer code?

Almost none. Publishing, consuming and acknowledging work the same way. The only change is the
`x-queue-type` argument when the queue is declared.

### What happened to mirrored queues?

They were deprecated and then removed in RabbitMQ 4.0. If you're moving off an old broker, replace
mirrored classic queues with quorum queues.
