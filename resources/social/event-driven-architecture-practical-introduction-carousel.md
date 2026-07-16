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
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 8f5df2087762d0e4581a0fedc4818e6cf3648d92
  checks:
    - dual-write framing and This one catches good engineers are the article line; the two text blocks (BAD save/publish, and save + insert into outbox / relay read-publish-mark) are near-verbatim reproductions of the article blocks
    - outbox claim the event is guaranteed to exist wherever the order does is the article sentence
    - might publish twice because the relay crashes after publishing before marking sent, and at-least-once meaning consumers had to be idempotent already - both traced to the article
    - Kafka orders within a partition, not across a topic - matches the article and matches Kafka reality; ordering guarantee is per-partition
    - CTA (dispatch a domain event, move one listener to the queue, controller returns fast, broker only when crossing a service boundary) is the article closing advice
  notes: |
    One loose bit for the human, not a blocker. Slide 2 adds No retry saves you, because the handler already returned successfully - that reason is not in the article and sits slightly crooked against the scenario the slide just drew: if the publish dies on a network blip the handler sees an exception, it has not returned successfully. The conclusion is still right (retry is not the fix for dual-write, which is why the outbox exists - the real gap is a crash BETWEEN the two writes, where nothing is left to retry), only the stated reason is compressed. Nothing here ages: no versions, no vendors, no prices.
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
boundary.
