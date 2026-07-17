---
title: "Bindings and routing keys"
slug: bindings-and-routing-keys
seo_title: "RabbitMQ Bindings and Routing Keys: How Routing Works"
seo_description: "A binding links an exchange to a queue; the routing key plus that binding decide delivery. See how RabbitMQ routes a message, with a direct-exchange example."
---

## The two halves of routing

In the [previous lesson](/course/rabbitmq-basics/core-concepts/exchanges) we said the
exchange decides where a message goes, but it does not decide alone. Routing is a
match between two things:

- The **binding** - a link you create from an exchange to a queue, with a rule attached.
- The **routing key** - a short label the producer stamps on each message.

The exchange compares the message's routing key against its bindings. Where they match,
the message is delivered. That's the entire mechanism.

## What a binding is

A **binding** is a connection between an exchange and a queue. You create it once, when
you set up your topology, and it stays in place. It tells the exchange: "if a message
matches this rule, put it in this queue".

A queue with no binding is unreachable through that exchange - the exchange has no reason
to send it anything. So bindings are what actually wire your system together.

A detail that saves confusion later: creating the exact same binding twice does nothing
the second time. A binding is identified by its exchange, queue and rule, so re-declaring
one is a no-op, not a duplicate that delivers two copies. That is why setup code which runs
on every app boot can safely re-create its bindings without piling them up.

Draw the picture in your head like this:

```text
producer  ->  exchange  ->  (binding)  ->  queue  ->  consumer
```

The producer and consumer sit at the ends. The binding is the piece in the middle that
connects the exchange to a specific queue.

## What a routing key is

A **routing key** is a small string a producer attaches to a message when it publishes.
It is not the message body - it is more like the address on an envelope. Something like
`invoice.pdf` or `orders.new`. The producer chooses it; the exchange reads it.

The routing key on its own does nothing. It only has meaning when an exchange compares it
against the routing rules in its bindings.

## A concrete direct example

Let's route with a **direct** exchange, where a binding matches a routing key exactly.

Imagine an exchange called `documents` and two queues:

- `pdf-queue`, bound to `documents` with the rule `pdf`.
- `image-queue`, bound to `documents` with the rule `image`.

Now watch what happens as messages are published:

```text
publish "report.pdf"   with routing key "pdf"    -> pdf-queue
publish "photo data"   with routing key "image"  -> image-queue
publish "report.pdf"   with routing key "pdf"    -> pdf-queue
publish "notes"        with routing key "text"   -> nowhere (dropped)
```

The exchange looks at each message's routing key, finds the binding whose rule matches
exactly, and drops the message into that queue. The last message uses `text`, which no
binding matches, so it goes nowhere. This is the "silent drop" we warned about earlier -
the message is simply discarded.

## One key can reach many queues

A routing key does not have to map to a single queue. If **two** queues are both bound
to the `documents` exchange with the rule `pdf`, then a message with routing key `pdf`
goes to **both**. Each queue gets its own copy. This is how the same event can feed
several independent consumers.

The reverse is also true: one queue can have several bindings, so it can collect messages
from more than one routing key.

## This was just the direct style

The example above used exact matching because it is the easiest to picture. Other
exchange types treat the routing key differently - a **[fanout](/course/rabbitmq-basics/exchanges-and-routing/fanout-exchange)** exchange ignores it
completely and delivers to every bound queue, while a **[topic](/course/rabbitmq-basics/exchanges-and-routing/topic-exchange)** exchange matches it
against patterns with wildcards. Those live in **chapter 4**. The relationship you should
remember is the one that never changes: **routing key plus binding decides delivery.**

## FAQ

### What's the difference between a binding and a routing key?

A binding is the fixed link between an exchange and a queue, created when you set up your
topology. A routing key is a per-message label the producer sets each time it publishes.
The exchange matches the second against the first to decide delivery.

### Does every message need a routing key?

You always set one, even if it's an empty string. Some exchange types (like fanout)
ignore it, but the field is always there. For a direct exchange the routing key is
essential, because matching is the whole point.

### What happens to a message that matches no binding?

The exchange drops it, with no error by default. If you publish and messages seem to
vanish, an unmatched or missing binding is the first thing to check.
