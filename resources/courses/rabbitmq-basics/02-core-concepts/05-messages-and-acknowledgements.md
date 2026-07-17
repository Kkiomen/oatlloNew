---
title: "Messages and acknowledgements"
slug: messages-and-acknowledgements
seo_title: "RabbitMQ Messages and Acknowledgements (Ack) Explained"
seo_description: "What a RabbitMQ message is - body plus properties - and how an acknowledgement lets a consumer confirm the work so the broker can safely drop the message."
---

## What a message actually is

We've talked a lot about "messages" moving through the system. Let's look at what one
actually contains. A RabbitMQ message has two parts: a **body** and its **properties**.

The **body** is the payload - the raw content you want to deliver. To RabbitMQ it is just
bytes. It does not care whether those bytes are JSON, plain text, or anything else; it
carries them without looking inside. In practice most applications put a small JSON string
in the body, like `{"user_id": 42, "action": "welcome_email"}`.

The **properties** are metadata that travel alongside the body. They describe the message
without being part of its content. Common ones include:

- **content type** - a hint about the body's format, such as `application/json`.
- **delivery mode** - whether the message should be written to disk so it survives a
  broker restart.
- **headers** - your own key/value pairs, useful for routing and for passing extra
  context.
- **timestamp**, **message id**, and a few others.

You set the ones you need and ignore the rest. Most of the time the body plus a content
type is enough.

## Body versus routing key

Don't confuse the body with the **routing key** from the
[previous lesson](/course/rabbitmq-basics/core-concepts/bindings-and-routing-keys). The
routing key is an address the exchange reads to decide *where* the message goes. The body
is the actual content the consumer processes once it arrives. The exchange never looks at
the body; the consumer usually never routes on the routing key. Two different jobs.

## Acknowledgements: confirming the work is done

Now the second big idea of this lesson. When a consumer receives a message, the broker
does not immediately forget it. It waits for the consumer to send an **acknowledgement**
- often shortened to **ack** - a small signal that means "I've finished processing this,
you can let it go".

Here's the sequence:

```text
1. broker delivers a message to the consumer
2. consumer does its work (send email, resize image, ...)
3. consumer sends an ack
4. broker removes the message from the queue
```

The message is only truly gone after step 4. Until the ack arrives, the broker keeps
holding it.

## Why acknowledgements matter

Acknowledgements are what make delivery reliable. Imagine a consumer takes a message and
then crashes halfway through - a bug, a lost database connection, a server reboot. Because
it never sent an ack, the broker still has the message. It notices the consumer is gone
and **redelivers** the message to another consumer. Nothing is lost just because one
worker failed.

Without acknowledgements, the broker would delete a message the moment it was handed out,
and a crash would silently destroy the work. With them, a message survives failures until
someone genuinely finishes it.

There is a catch worth understanding now, before you write any consumer. Redelivery is
blind to how far the work got. If a consumer finishes the job - sends the email - and then
crashes in the split second before its ack goes out, the broker never heard the ack and
hands the same message to someone else. The email goes out twice. This is why RabbitMQ
gives you ["at least once" delivery](/course/rabbitmq-basics/reliability-and-delivery/delivery-guarantees), not "exactly once", and why consumers that do
non-repeatable work should be written to tolerate seeing the same message again.

## A note on the trade-off

Acknowledgements are not free of nuance. If a consumer never acks - because it hangs, or a
bug forgets to send the signal - the broker keeps the message as "in progress" and it
never completes. A pile of unacknowledged messages is a classic RabbitMQ symptom, and
there's a [whole troubleshooting lesson about it later](/course/rabbitmq-basics/real-world-and-troubleshooting/unacked-messages-piling-up) in the course. For now, just know
the signal exists and why.

## This is only the overview

We've kept this deliberately simple: a message is a body plus properties, and an ack tells
the broker a message was handled. There is much more to it - manual versus automatic acks,
negative acknowledgements, how many messages a consumer holds at once - but that belongs
with real code. You'll wire up acknowledgements yourself in **chapter 3**, and go deep on
[reliable delivery](/course/rabbitmq-basics/reliability-and-delivery/acknowledgements-deep-dive) in **chapter 5**.

## FAQ

### What's the difference between the message body and its properties?

The body is the content you want delivered - to RabbitMQ, just bytes. The properties are
metadata about the message, like its content type or whether it should survive a restart.
The body is *what* you send; the properties describe *how* to handle it.

### What happens if a consumer never sends an acknowledgement?

The broker keeps the message as unacknowledged. If the consumer disconnects, the message
is redelivered to another consumer. If the consumer stays connected but simply never acks,
the message sits in limbo - a common bug we troubleshoot later in the course.

### Can a consumer reject a message instead of acking it?

Yes. A consumer can send a negative acknowledgement to say "I couldn't handle this",
optionally asking the broker to requeue it or route it elsewhere. That's part of the
reliability toolkit we explore in chapter 5.
