---
title: "Clustering and high availability"
slug: clustering-and-ha
seo_title: "RabbitMQ Clustering and High Availability Guide"
seo_description: "How RabbitMQ clustering delivers high availability: node availability, quorum queues for replication, and why a cluster alone is not a throughput button."
---

## What a cluster is

RabbitMQ clustering joins several broker nodes together so they act as one logical broker. Clients
can connect to any node, and the cluster shares its metadata - the definitions of users, vhosts,
exchanges and queues - across all of them. The reason to run one is **availability**: if a node
goes down, the others keep serving.

A single node is fine for learning and small workloads. A cluster is what you reach for when "the
broker being unavailable" is not an acceptable outcome.

Two things have to line up before nodes will even join. They must run compatible RabbitMQ and
Erlang versions, and they must share the same Erlang cookie - the same secret that `rabbitmqctl`
needs to talk to a node in the [earlier lesson](/course/rabbitmq-basics/operating-in-production/rabbitmqctl-basics).
A mismatched cookie is the most common reason a fresh node silently refuses to cluster.

## Availability comes from replication

Here's the part people miss: joining nodes into a cluster does **not** automatically make your
queues survive a node failure. By default a classic queue lives on exactly one node - the node
where it was declared. If that node dies, that queue is unavailable until it comes back, even
though the rest of the cluster is up.

To actually survive a lost node, the queue's data has to exist on more than one node. That's what
**quorum queues** from the
[previous lesson](/course/rabbitmq-basics/operating-in-production/quorum-queues) are for: they
replicate the queue across nodes and keep working as long as a majority survives. So the real
high-availability recipe is "a cluster **plus** quorum queues", not a cluster alone. This is also
why quorum queues want at least three nodes - a majority of three still exists after one fails.

## Not a magic scale button

It's tempting to think more nodes means more throughput. That's not what a cluster is for. Each
queue (classic or quorum) still has a home and does its work on specific nodes, so adding nodes
doesn't make one busy queue faster. A cluster buys you:

- **Availability** - survive losing a node.
- **More connections and channels** - spread many clients across nodes.
- **More total queues** - room for many queues across the cluster.

What it does not buy you is a single queue magically going faster because you added machines. If
one queue is your bottleneck, you split the work across more queues or more consumers (fair
dispatch and prefetch from
[chapter 3](/course/rabbitmq-basics/first-producer-and-consumer/fair-dispatch-prefetch)), not by
adding nodes.

## Disk vs RAM nodes

You may see mention of **disk nodes** and **RAM nodes**. A disk node stores the cluster's
metadata (definitions) on disk; a RAM node keeps that metadata only in memory for slightly faster
metadata changes. This only ever refers to **metadata**, not your messages - persistent messages
are always written to disk regardless. The practical guidance is simple: use disk nodes. Keep at
least a majority of your nodes as disk nodes so the cluster can recover its definitions after a
full restart. RAM nodes are a niche optimization you rarely need.

## Common mistake

Building a cluster and assuming it's now "highly available", while all the important queues are
still classic single-node queues. When a node dies, those queues vanish with it and the app breaks,
cluster or not. Availability of the broker is not the same as availability of your queues - the
queues need quorum replication too.

## FAQ

### How many nodes should a cluster have?

An odd number, commonly three. Odd numbers avoid split-vote situations, and three lets a quorum
queue keep a majority after losing one node.

### Does a cluster make RabbitMQ faster?

Not for a single queue. It adds capacity for more connections and more queues, and it adds
availability. Throughput of one queue is scaled with more consumers and by splitting the work,
not by adding nodes.

### Do clients need to know every node?

They should be able to reach more than one, usually through a load balancer or by listing several
hosts, so that if the node they're on goes down they can reconnect to another.
