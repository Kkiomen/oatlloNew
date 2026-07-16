---
title: "Publish/subscribe"
slug: pub-sub
seo_title: "Redis Pub/Sub: PUBLISH and SUBSCRIBE for Live Messaging"
seo_description: "Learn Redis pub/sub with SUBSCRIBE and PUBLISH. Send fire-and-forget live messages to connected clients, and see how it differs from a persistent queue."
---

Redis pub/sub broadcasts a message to whoever is listening right now: a live notification, a
chat message, a "new order" ping to a dashboard. The name is short for publish and subscribe,
and it is built into Redis with no extra setup.

## How Redis pub/sub channels work

A channel is just a name, like `orders` or `chat:room:5`. Some clients subscribe to a
channel to listen. Other clients publish messages to it. Every subscriber gets every
message sent to that channel while they are connected. The sender does not know or care who
is listening.

## Try Redis pub/sub in redis-cli

Open two [redis-cli](/course/redis-basics/managing-redis-from-the-console/the-redis-cli-console)
windows. In the first one, subscribe to a channel:

```bash
SUBSCRIBE news
```

```text
Reading messages... (press Ctrl-C to quit)
```

That window now waits and prints anything sent to `news`. In the second window, publish a
message:

```bash
PUBLISH news "Redis 7 is out"
```

```text
(integer) 1
```

The `1` means one subscriber received it. Flip back to the first window and you will see
the message appear instantly. Open a third window, subscribe too, and both listeners get
every future message.

Notice the first window will not let you run a normal `GET` or `SET` now. Once a connection
subscribes it enters subscriber mode and only accepts more subscribe and unsubscribe
commands (plus `PING`). That is why publisher and subscriber are always separate
connections, and why an app never subscribes on the same connection it uses for regular
commands.

## Fire and forget

Pub/sub is fire and forget. When you `PUBLISH`, Redis pushes the message to whoever is
connected at that exact moment and then forgets it. There is no history, no receipt, no
retry. It is perfect for live updates where a slightly missed message does not matter,
because a fresh one is coming soon anyway.

## How it differs from a queue

This is the key point, so it is worth being clear. A
[queue](/course/redis-basics/beyond-cache-and-production/queues-and-background-jobs) stores
work until someone processes it. Pub/sub stores nothing.

```text
Queue:   message waits in Redis -> one worker eventually handles it
Pub/Sub: message is sent -> current subscribers get it -> it is gone
```

Two differences drive everything:

- No persistence. A queued job survives until a worker runs it. A published message is
  never saved. If nobody is subscribed, it vanishes with no error.
- Only connected subscribers receive it. A subscriber that connects one second later gets
  nothing that came before. A queue does not care when the worker shows up; the job is
  still there.

So use a queue when the work must happen. Use pub/sub when the message only matters live.

## Common mistake

Reaching for pub/sub to run background jobs. Because messages are not saved, a subscriber
that is down during a restart or a deploy misses everything sent in that gap, with no way
to recover it. For work that must not be lost, use a
[queue](/course/redis-basics/beyond-cache-and-production/queues-and-background-jobs)
instead, which keeps the job until it is handled.

## FAQ

### Can more than one client subscribe to the same channel?

Yes. Every subscriber gets its own copy of every message. That is the difference from a
queue, where one job goes to exactly one worker.

### Does Laravel use Redis pub/sub?

Yes. Laravel's broadcasting can use Redis pub/sub behind the scenes to push real-time
events out to WebSocket servers, which then reach the browser.

### What if I publish and no one is listening?

The message is simply dropped and `PUBLISH` returns `0`, the number of subscribers that got
it. No error, nothing stored. This is normal pub/sub behaviour.
