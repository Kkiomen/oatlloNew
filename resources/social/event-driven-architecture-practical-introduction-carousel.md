---
slug: event-driven-architecture-practical-introduction-carousel
type: carousel
language: en
title: "The dual write problem"
topic: architecture
source_type: article
source: event-driven-architecture-practical-introduction
link: https://oatllo.com/event-driven-architecture-practical-introduction
publish_at: 2026-10-23 19:00
status: ready
formats: [post]
hashtags: [architecture, events, laravel, messaging, backend]
caption: |
  The order exists in your database and nobody was ever told it was placed.

  Two writes, one transaction each. The DB commit lands, the publish hits a
  network blip, and your state disagrees with your event stream forever.
  The outbox pattern is the standard fix.

  Full intro linked in bio.

  Have you shipped an outbox, or dodged it so far?
---

## Your DB commit can succeed while the event announcing it vanishes.

Your handler does two things and only one of them is inside a transaction.
This one catches good engineers.

<!-- slide -->

## Two writes. One can fail alone.

```text
save order to DB    -> success
publish OrderPlaced -> network blip
result: order exists, nobody was told
```

Your state and your event stream now disagree, permanently. No retry saves
you, because the handler already returned successfully.

<!-- slide -->

## Put the event in the same transaction

```text
save order + insert into outbox
  -> ONE transaction, atomic
[relay] read outbox -> publish -> mark
```

A separate relay reads the outbox and publishes. The event is now
guaranteed to exist wherever the order does.

<!-- slide -->

## It might publish twice. That is fine.

The relay can crash after publishing and before marking the row sent.
Almost all messaging is at-least-once anyway, so your consumers had to be
idempotent already.

<!-- slide -->

## You may get OrderShipped before OrderPaid

Networks reorder. Consumers restart. Kafka orders within a partition, not
across a topic. Design so out-of-order delivery survives, not so it is
impossible.

<!-- slide role="cta" -->

## The cheapest experiment costs nothing new

Dispatch a domain event, move one listener onto the queue, watch the
controller return fast. Reach for a broker when you cross a service
boundary. Full intro linked in bio.
