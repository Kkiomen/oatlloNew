---
title: "Events vs commands"
slug: events-vs-commands
seo_title: "Events vs Commands: Facts vs Intents Explained"
seo_description: "Events vs commands: a command is an intent one handler can reject; an event is a past-tense fact many can react to and cannot refuse. Learn the difference."
---

Both a command and an event are just messages - small objects you pass around. But they
mean opposite things, and mixing them up leads to confused designs. Chapter 6 already split
[commands and queries](/course/software-architecture/application-layer-and-use-cases/commands-and-queries-cqrs).
Here we contrast a **command** with an **event**, because in an event-driven system you
send both and they play very different roles.

## A command is an intent

A **command** is a request to change something: "please do this". It is written in the
**imperative** - `PlaceOrder`, `CancelSubscription`, `ShipParcel`. A command:

- Has **exactly one handler**. Somebody is responsible for carrying it out.
- **Can be rejected.** The handler may validate it and refuse - out of stock, not
  authorized, invalid data. The sender is asking permission.
- Is directed **at** a specific receiver. The sender expects it to be executed (or refused).

```php
// A command: an intent, imperative, one handler, may be refused.
final class PlaceOrder
{
    public function __construct(
        public string $customerId,
        public array $items,
    ) {}
}
```

## An event is a fact

An **event** is a record that something already happened: "this occurred". It is written in
the **past tense** - `OrderPlaced`, `SubscriptionCancelled`, `ParcelShipped`. An event:

- Can have **many subscribers**, or none. The publisher does not care.
- **Cannot be rejected.** It is history. You cannot refuse a fact; you can only react to it.
- Is directed **at no one in particular.** It is a broadcast of truth about the past.

```php
// An event: a fact, past tense, many subscribers, cannot be refused.
final class OrderPlaced
{
    public function __construct(
        public string $orderId,
        public int $totalCents,
    ) {}
}
```

## Direction and flow

The two fit together in a clean loop. A command comes **in** and asks for a change; if the
handler accepts it, an event goes **out** to announce what changed.

```text
  intent (may be refused)          fact (cannot be refused)
        |                                    |
  [ PlaceOrder ] --> [ handler decides ] --> [ OrderPlaced ] --> subscribers react
    one handler          accept/reject          many listeners
```

Notice the asymmetry. Before the handler runs, the outcome is open - the command might be
turned down. After it runs, the event is settled - everyone downstream simply accepts it and
does their part (send email, create invoice, update analytics).

A practical detail that decides a lot: how much data the event carries. Put enough on
`OrderPlaced` that a subscriber can do its job without calling back to the order service for
details. A thin event that only says `orderId` forces every listener to fetch the rest,
which quietly rebuilds the tight coupling you were trying to escape.

## Naming is the tell

Because they mean different things, they read differently:

- **Commands** use the **imperative**: a verb telling the system what to do -
  `RegisterUser`, `RefundPayment`.
- **Events** use the **past tense**: a verb saying what already happened -
  `UserRegistered`, `PaymentRefunded`.

If you find yourself wanting to "reject an event" or "send a command to nobody in
particular", you have probably mislabeled the message. Fix the name and the design usually
straightens out with it.

## Common mistake

Publishing a past-tense event but secretly expecting one specific subscriber to act on it,
so the rest of the system breaks if that subscriber is missing. That is a command wearing an
event's costume. If exactly one component must handle a message and may refuse it, send a
**command** to it directly. Reserve events for facts that others may react to freely - and
that stay true whether zero or ten listeners exist.

## FAQ

### What is the difference between a command and an event?

A command is an intent to change something ("PlaceOrder"): one handler, and it can be
rejected. An event is a fact that already happened ("OrderPlaced"): many subscribers, and it
cannot be rejected. Commands look forward and ask; events look back and inform.

### Why are events named in the past tense?

Because they describe something that has already occurred and is now unchangeable. Past
tense (`OrderPlaced`) signals "this is history, react to it", while an imperative name
(`PlaceOrder`) signals "please make this happen".

### Can an event be rejected like a command?

No. An event is a statement of fact about the past, so there is nothing to refuse. If a
message needs to be validated and possibly turned down, it is a command, not an event.
