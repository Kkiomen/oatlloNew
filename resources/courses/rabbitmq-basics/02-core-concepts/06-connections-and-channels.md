---
title: "Connections and channels"
slug: connections-and-channels
seo_title: "RabbitMQ Channels vs Connections: How They Work"
seo_description: "One TCP connection to RabbitMQ carries many lightweight channels multiplexed over it. Learn channels vs connections and the rule of thumb for using each."
---

## Talking to the broker

Everything in the chapters so far - producers publishing, consumers receiving - happens
over a network link to the broker. This last core concept is about that link and the
lighter-weight idea layered on top of it, which is really the whole story of RabbitMQ
channels vs connections. Two words to pin down: **connection** and **channel**.

## The connection is a TCP connection

When your application talks to RabbitMQ, it opens a **connection** to the broker. This is
a real **TCP** connection over the network - the same kind of connection your browser
opens to a website. It has to be negotiated, authenticated and kept alive, which makes it
relatively expensive to create.

Because a connection is costly, you don't want a lot of them. Opening a fresh TCP
connection for every message, or for every small piece of work, would waste time and
resources. So the guideline is simple: **open few connections, and keep them open.**

## Channels ride inside the connection

Here's the clever part. Inside a single connection, RabbitMQ lets you open many
**channels**. A channel is a lightweight, virtual connection that shares the one real TCP
connection underneath. All of your actual work - publishing, consuming, declaring queues -
happens *on a channel*, not directly on the connection.

You can picture it like this:

```text
your app  ==== one TCP connection ====  broker
                 |  |  |  |
              channel 1  (publishing orders)
              channel 2  (consuming emails)
              channel 3  (declaring queues)
```

One heavy pipe to the broker, and several cheap channels multiplexed over it. Opening and
closing a channel is fast because there's no new network handshake - it's just a bit of
bookkeeping on a link that already exists.

## Why channels exist

If a connection already reaches the broker, why add channels at all? Two reasons.

**Cost.** TCP connections are expensive to open and each one uses resources on both your
app and the broker. Channels let you get the *effect* of many independent conversations
while paying for the connection only once.

**Concurrency.** Real applications do several things at the same time - one part publishes,
another consumes, a third sets up queues. If they all shared a single stream they would
step on each other. Giving each task its own channel keeps those conversations separate and
tidy over the same connection.

There is a second payoff to that separation, and it is easy to miss. When something goes
wrong on a channel - you publish with a bad routing setup, or try to use a queue that isn't
there - RabbitMQ closes *that channel*, not the whole connection. Your other channels carry
on. Cram every task onto one shared channel and a single mistake takes them all down
together, which is the quiet reason the "one channel per task" rule below is worth
following even before you hit any performance limit.

## A practical rule of thumb

You'll see the details in code later, but the shape to remember is:

- **One connection per application** (or a small pool of them), opened once and reused.
- **One channel per task or per thread** - cheap to open, not shared between threads doing
  work at the same time.

Most client libraries make this easy, and the [Laravel driver in chapter 6](/course/rabbitmq-basics/rabbitmq-and-laravel/how-laravel-queues-work) handles much of
it for you. You mainly need to recognise the two ideas when you see them.

## Common mistake

A frequent beginner mistake is opening a **new connection for every message**. It works in
a quick test, then falls over under load: the broker runs out of connection resources and
your app spends all its time on TCP handshakes instead of real work. The fix is the rule
above - reuse one connection and open channels on top of it. If you ever see connection
counts climbing into the hundreds for a small app, this is almost always why.

## FAQ

### What's the difference between a connection and a channel?

A connection is a real, relatively expensive TCP link to the broker. A channel is a
lightweight virtual connection that runs *inside* that TCP link. You open one connection
and many channels over it.

### How many channels can I open on one connection?

Many - client libraries and the broker allow a large number. In practice you open as many
as your tasks need and no more. The point of channels is that they're cheap enough not to
worry about the way you worry about connections.

### Should I share one channel across everything?

No. Use a separate channel for separate concurrent tasks, especially across threads. A
single shared channel used by multiple things at once leads to confusing errors. Separate
channels keep each conversation clean while still sharing the one connection.
