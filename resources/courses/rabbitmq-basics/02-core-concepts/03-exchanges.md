---
title: "Exchanges"
slug: exchanges
seo_title: "RabbitMQ Exchanges: Producers Don't Publish to Queues"
seo_description: "In RabbitMQ, a producer publishes to an exchange, never straight to a queue. The exchange decides which queues receive the message. Meet the four exchange types."
---

## The idea that surprises everyone

Here is the single most important sentence in this whole chapter:

> Producers do **not** publish to a queue. They publish to an **exchange**.

If you came in thinking a producer drops a message straight into a queue, this is the
moment to update that picture. In RabbitMQ there is always a middle step. The producer
hands its message to an **exchange**, and the exchange decides which queue - or queues,
or none at all - should receive it.

## What an exchange is

An **exchange** is a router that lives inside the broker. It receives every message a
producer publishes and then figures out where that message should go. It never stores
messages itself; it only routes them. Storage is the queue's job. Routing is the
exchange's job.

Think of the exchange as the sorting desk at the post office. Letters arrive at the desk,
the clerk reads the address, and the letter is placed into the right mailbox. The desk
holds nothing overnight - it just decides and forwards.

Because an exchange stores nothing, there is nothing in it to lose when the broker
restarts. That is why durability - surviving a restart - is always a queue and message
concern, never an exchange one. An exchange being "durable" only means its definition
survives; no payload was ever sitting inside it.

## Why not publish straight to a queue?

Adding an exchange in the middle sounds like extra work, but it unlocks flexibility that
a direct producer-to-queue link never could:

- **One message, many destinations.** An exchange can copy a single "order placed"
  message into three different queues - one for billing, one for shipping, one for
  analytics - without the producer knowing any of them exist.
- **Change the wiring without touching code.** You can add or remove queues behind an
  exchange while the producer keeps publishing exactly as before.
- **Rich routing rules.** The exchange can route by an exact label, by pattern, or send
  to everyone, depending on its type.

The producer stays simple. It just says "here is a message" and lets the broker's
exchange handle the *where*.

## The exchange decides, using bindings

An exchange does not guess. It follows rules called **bindings** that connect it to
queues. A binding says "messages that match this criterion should go to this queue". The
message itself usually carries a small label - a **routing key** - and the exchange
compares that label against its bindings to pick the destinations.

Bindings and routing keys are important enough to get their
[own lesson next](/course/rabbitmq-basics/core-concepts/bindings-and-routing-keys). For
now, just hold the shape: **producer publishes to exchange, exchange consults its
bindings, matching queues receive the message.**

## The four types of exchange

An exchange's *type* decides how it matches messages to queues. RabbitMQ has four:

- **direct** - route by an exact routing-key match. A message labelled `pdf` goes to the
  queue bound with `pdf`.
- **fanout** - ignore the routing key entirely and send a copy to *every* bound queue.
  Great for broadcasts.
- **topic** - match the routing key against patterns with wildcards, like
  `orders.*.europe`.
- **headers** - route on message header values instead of the routing key.

We only name them here so the words are familiar. Each type gets a full lesson with
examples in **chapter 4, Exchanges and routing**. Don't try to memorise the rules yet.

## What happens if nothing matches

An exchange that finds no matching queue simply **drops** the message. There is no error
by default - the message just goes nowhere. This trips people up constantly: they publish
happily, no queue is bound the way they expect, and the messages vanish. Keep it in mind;
we'll show how to catch it later.

## FAQ

### If producers publish to exchanges, how did my "publish to a queue" example work?

Through the **default exchange**. RabbitMQ ships with a nameless direct exchange that is
pre-bound to every queue by the queue's own name. So "publishing to a queue called
`emails`" is really publishing to the default exchange with routing key `emails`. It's a
convenient shortcut, and we cover it in chapter 4.

### Does the exchange store messages?

No. An exchange only routes. If no queue is bound to receive a message, the exchange has
nowhere to put it and the message is discarded. Only queues store messages.

### How many exchanges and queues can I have?

Many. A real system typically has several exchanges, each with its own queues bound
behind it. You choose the layout that fits how your messages need to flow.
