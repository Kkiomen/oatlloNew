---
title: "What is RabbitMQ?"
slug: what-is-rabbitmq
seo_title: "What Is RabbitMQ? Message Broker and AMQP Basics"
seo_description: "What is RabbitMQ? A message broker that receives, stores and routes messages between producers and consumers over the AMQP protocol. Explained simply."
---

**RabbitMQ** is a message broker. It's a separate program that sits in the middle of
your system, receives messages from one part of your application, stores them safely,
and delivers them to another part when it's ready. It's the software that makes the
message queue from the [previous lesson](/course/rabbitmq-basics/getting-started/what-is-a-message-queue)
real.

## What "message broker" means

A **broker** is a middleman. In messaging, the broker is the trusted party that both
sides talk to instead of talking to each other directly.

- The part that sends messages (the **producer**) hands them to RabbitMQ.
- RabbitMQ holds those messages in a queue.
- The part that reads messages (the **consumer**) receives them from RabbitMQ.

Neither side needs to know where the other one lives, whether it's online right now,
or how fast it is. They only need to know how to reach RabbitMQ. That's what makes the
two sides independent.

## What RabbitMQ actually does

RabbitMQ does three jobs:

1. **Receives** messages from producers.
2. **Stores** them (in memory, or on disk if you ask it to) until they're handled.
3. **Routes and delivers** them to the right consumers.

That third job - routing - is where RabbitMQ is smarter than a plain list. It can
decide which queue a message should go to based on rules you set up. We'll cover
[routing](/course/rabbitmq-basics/core-concepts/exchanges) in detail in a later chapter; for now, just know that RabbitMQ can do more
than dump every message into one line.

## AMQP: the protocol it speaks

RabbitMQ implements a protocol called **AMQP** (Advanced Message Queuing Protocol).

A protocol is just an agreed set of rules for how two programs talk. AMQP defines how
a producer publishes a message, how the broker stores it, and how a consumer receives
it. Because AMQP is an open standard, many different tools and languages can talk to
RabbitMQ the same way - your PHP app, a JavaScript service, and a Python script can
all share the same broker.

You don't need to memorize AMQP. You'll use a client library that speaks it for you.
Just remember: **AMQP is the language, RabbitMQ is the broker that speaks it.**

## Where RabbitMQ runs

RabbitMQ is its own server process. It usually runs on its own - on your machine
during development, and on a dedicated server or container in production. Your
application connects to it over the network, even if "the network" is just your own
computer.

Because it's a separate process, RabbitMQ has to be running *before* your app tries to
talk to it. This trips up almost everyone once: a [connection refused](/course/rabbitmq-basics/real-world-and-troubleshooting/connection-refused) error is rarely
a bug in your code, it usually just means the broker isn't up yet. Start the broker
first, then the app.

Next we'll look at why you'd reach for RabbitMQ, and then run it with Docker so you can
see it for yourself.

## FAQ

### Is RabbitMQ written in a specific language?

RabbitMQ itself is built on Erlang, a language designed for reliable networked
systems. You don't need to know Erlang to use it - you talk to RabbitMQ from whatever
language your app uses.

### Do I install RabbitMQ inside my app?

No. RabbitMQ is a separate server. Your app installs a small **client library** to
connect to it, but the broker runs on its own.

### Is RabbitMQ free?

Yes. RabbitMQ is open source and free to use, including in commercial projects.
