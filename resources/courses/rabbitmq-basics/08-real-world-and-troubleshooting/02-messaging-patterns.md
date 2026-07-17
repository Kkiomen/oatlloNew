---
title: "Messaging patterns"
slug: messaging-patterns
seo_title: "RabbitMQ Messaging Patterns Explained (with Map)"
seo_description: "A map of the core RabbitMQ messaging patterns - work queue, pub/sub, routing, RPC and delayed messages - and which course chapter taught each one."
---

Every building block is now in your hands. This lesson steps back and names the **patterns**
those blocks form. A pattern is just a well-known arrangement of exchanges, queues and
bindings that solves a recurring problem. Put a name to each one and you start recognising
which a task needs on sight. Everything here was already taught, so treat this as a map back
into the course.

## Work queue (competing consumers)

**Problem:** one stream of tasks, and you want them spread across several workers so they
finish faster.

**Shape:** producers publish to one queue. Several consumers subscribe to that same queue.
RabbitMQ hands each message to exactly one consumer, round-robin. Add a worker and you add
capacity; each message is still processed once.

This is the pattern the whole Laravel stack uses - many `queue:work` processes draining one
queue. Combine it with a prefetch of 1 so a slow worker isn't handed a pile of messages
while others sit idle.

Taught in: [work queues](/course/rabbitmq-basics/first-producer-and-consumer/work-queues)
and [fair dispatch / prefetch](/course/rabbitmq-basics/first-producer-and-consumer/fair-dispatch-prefetch).

## Publish/subscribe (fanout)

**Problem:** [one event, many independent reactions](/course/software-architecture/event-driven-architecture/what-is-event-driven-architecture). An order is placed and you want to
email the customer, update analytics and warm a cache - all from the same event.

**Shape:** the producer publishes to a **fanout** exchange, which copies the message to
every bound queue. Each consumer has its own queue, so each gets its own copy and works at
its own pace. The producer doesn't know or care how many subscribers there are.

The key difference from a work queue: in pub/sub **every** consumer gets the message; in a
work queue only **one** does. That difference is entirely in the exchange type.

Worth remembering when you build one: a fanout exchange ignores the routing key completely.
Setting a key on a fanout publish looks like it should filter, but it does nothing - the
message still lands in every bound queue. If you want the key to actually decide who gets
the copy, that is routing, and it needs a direct or topic exchange.

Taught in: [fanout exchange](/course/rabbitmq-basics/exchanges-and-routing/fanout-exchange).

## Routing (direct and topic)

**Problem:** subscribers only want *some* of the events, not all of them. A logging system
where one consumer wants only errors and another wants everything.

**Shape:** the producer tags each message with a **routing key**. A **direct** exchange
delivers to queues whose binding key matches exactly (`error` goes only to the error
queue). A **topic** exchange matches routing keys against patterns with wildcards
(`order.*.eu` or `#.error`), so a queue can subscribe to a whole family of events.

Routing is pub/sub with a filter: the exchange decides which queues get a copy based on the
key and the bindings, instead of blindly copying to all of them.

Taught in: [direct exchange](/course/rabbitmq-basics/exchanges-and-routing/direct-exchange)
and [topic exchange](/course/rabbitmq-basics/exchanges-and-routing/topic-exchange). The
mechanics of keys and bindings come from
[bindings and routing keys](/course/rabbitmq-basics/core-concepts/bindings-and-routing-keys).

## RPC (request/reply)

**Problem:** you want a reply, not just fire-and-forget. The producer sends a request and
needs the answer back.

**Shape:** the client publishes a request and sets two message properties: `reply_to`, the
name of a queue it is listening on for the answer, and `correlation_id`, a unique token.
The server processes the request and publishes its result to that `reply_to` queue, copying
the same `correlation_id` back. The client matches the reply to the original request by
that id - essential when it has several requests in flight at once.

RPC turns the one-way messaging you've used all course into a round trip, using nothing but
standard message properties. Reach for it sparingly: if you're waiting for a reply, you're
back to coupling the two sides in time, which is the very thing queues usually free you
from.

Uses: message properties from
[messages and acknowledgements](/course/rabbitmq-basics/core-concepts/messages-and-acknowledgements),
and the default exchange to publish straight to a named
reply queue ([the default exchange](/course/rabbitmq-basics/exchanges-and-routing/the-default-exchange)).

## Delayed messages

**Problem:** don't deliver this yet. Retry a failed payment in five minutes, or send a
reminder an hour after signup.

**Shape:** the message waits before a consumer can see it. The portable way to build this
is a **dead-letter** setup: publish to a queue with a message TTL and no consumer, so when
the TTL expires the message is dead-lettered onto the real work queue. The TTL becomes the
delay.

This pattern reuses two reliability features directly: TTL and dead-lettering. It's also
how Laravel's `delay()` behaves on a broker that supports it.

Taught in: [delayed messages](/course/rabbitmq-basics/reliability-and-delivery/delayed-messages),
built on [message and queue TTL](/course/rabbitmq-basics/reliability-and-delivery/message-and-queue-ttl)
and [retries and dead-letter queues](/course/rabbitmq-basics/reliability-and-delivery/retries-and-dead-letter-queues).

## Picking a pattern

Ask one question: **who should get this message?**

- Exactly one of several equal workers: **work queue**.
- Everyone who is interested: **pub/sub** (fanout).
- Only the subscribers whose filter matches: **routing** (direct/topic).
- The sender, as a reply: **RPC**.
- The consumer, but later: **delayed messages**.

Every pattern is the same three parts - exchange, queue, binding - arranged differently.
Once you see them as arrangements rather than separate features, new requirements map onto a
known pattern almost every time.

## FAQ

### What's the real difference between a work queue and pub/sub?

Delivery count. A work queue delivers each message to **one** consumer so work is shared;
pub/sub (fanout) delivers a **copy to every** consumer so everyone reacts. You choose
between them by choosing the exchange, not by changing the consumers.

### Is routing just pub/sub with rules?

Yes, that's a fair way to see it. A fanout exchange ignores routing keys and copies to all
bound queues; direct and topic exchanges use the routing key and bindings to decide which
queues get a copy. Routing is fanout with a filter.

### Should I use RabbitMQ for RPC?

Only when you truly need a reply and want it to flow over the same broker as your other
messaging. For most request/response needs a normal HTTP call is simpler. RPC over RabbitMQ
shines when the responder is a pool of workers you're already running for other jobs.
