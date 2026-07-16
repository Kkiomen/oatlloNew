---
title: "The management UI tour"
slug: the-management-ui-tour
seo_title: "RabbitMQ Management UI Tour for Beginners"
seo_description: "A beginner tour of the RabbitMQ management UI - the Overview, Queues, Exchanges, Connections and Channels tabs, and what the message-rate graphs mean."
---

The **RabbitMQ management UI** is the web dashboard you opened at
`http://localhost:15672` in the last lesson. It's where you'll watch messages flow and
debug problems for the rest of the course, so a quick tour of the main tabs now will
pay off every time something misbehaves later.

Some tabs mention concepts we haven't covered yet (exchanges, channels). Don't worry -
you'll learn each one properly in the next chapter. For now, just note where things
live.

## Overview

The **Overview** tab is the landing page. At the top it shows the health of the broker:
how many messages are ready, how many are in flight, and totals for connections,
channels, queues and exchanges.

The most useful part is the **message rates** graph - live lines showing messages
coming in (publish) and going out (deliver) per second. When you run the examples later
in the course, you'll see these lines jump. Flat lines at zero mean nothing is flowing;
that's a quick way to check whether your producer is actually publishing.

At the bottom you'll also find the RabbitMQ and Erlang version numbers - handy when
looking things up or reporting an issue.

## Queues

The **Queues** tab lists every queue in the broker. For each queue you can see:

- Its **name**.
- **Ready** - messages waiting to be delivered.
- **Unacked** - messages delivered to a consumer but not yet confirmed as done.
- Incoming and outgoing message rates.

This is the tab you'll stare at most. If messages are piling up under **Ready**, no
consumer is reading them. If they're stuck under **Unacked**, a consumer took them but
never confirmed. Clicking a queue opens a detail page where you can even publish a test
message or peek at what's inside.

Watch these two numbers together and they tell a story. Kill a consumer while it's
holding unconfirmed messages and you'll see **Unacked** drop while **Ready** jumps by
the same amount - RabbitMQ didn't lose those messages, it put them back in line for the
next consumer. Seeing that swap happen live is the clearest way to understand what
"unacknowledged" really protects you from.

## Exchanges

The **Exchanges** tab lists **exchanges** - the part of RabbitMQ that decides which
queue a message goes to. You'll notice several already exist with names like
`amq.direct` and `amq.fanout`; these are built in.

We haven't covered exchanges yet - that's a whole chapter of its own. For now, just
know this tab exists and that exchanges are about **routing** messages to queues.

## Connections and Channels

These two tabs show who is currently talking to RabbitMQ.

- **Connections** lists each open network connection from an application to the broker.
  When your PHP app connects, it appears here. If it's empty, nothing is connected.
- **Channels** lists the lightweight conversations that run *inside* those connections.
  One connection can carry many channels. This is a detail you'll understand fully in
  the next chapter; for now, connections are the pipes and channels are the
  conversations inside them.

These tabs are great for a quick sanity check: if your consumer "isn't working", look
here first. No connection means your app never reached RabbitMQ at all.

## How you'll use this

Throughout the course, keep this UI open in a browser tab. When you publish a message,
watch the Overview rates move and the Queues counts change. Seeing the numbers react is
the fastest way to confirm your code did what you expected - and to spot when it
didn't.

## Common mistake: refreshing too fast and panicking

The graphs and counts update every few seconds, not instantly. When you send one test
message, don't expect the rate line to spike dramatically - a single message barely
registers. Look at the **Ready** count on the Queues tab instead; it will tick up by
one. The rate graphs are for streams of messages, not one-offs.

## FAQ

### Do I need the management UI to use RabbitMQ?

No. Your app talks to RabbitMQ over port 5672 without it. The UI is a convenience for
humans to watch and manage the broker, which is why it's on a separate port.

### Why do exchanges and queues already exist before I create any?

RabbitMQ ships with a few default exchanges so basic messaging works out of the box.
You'll learn what they do in the exchanges chapter.

### Can I do everything from the UI instead of code?

You can create queues, publish test messages and inspect state from the UI, which is
great for debugging. But real applications create and use them from code, which is what
you'll do starting in the next chapters.
