---
title: "Event sourcing"
slug: event-sourcing
seo_title: "Event Sourcing Explained: Events as Source of Truth"
seo_description: "Event sourcing stores the sequence of events as the source of truth instead of current state, and rebuilds state by replaying them. Benefits, costs, PHP example."
---

Most applications store the **current state**: a `balance` column holds `120`, and when
money moves you overwrite it with the new number. The old value is gone. **Event sourcing**
flips this around. Instead of storing the current state, you store the full **sequence of
events** that led to it - and the current state becomes something you compute from that
history whenever you need it.

## Store what happened, not where you ended up

Think of a bank account. A state-based system keeps one number and edits it. An
event-sourced system keeps the list of things that happened and never edits the past.

```text
State-based (overwrite):
   balance = 120        <- the 40 and 100 that came before are lost

Event-sourced (append-only log):
   1. MoneyDeposited  +100
   2. MoneyWithdrawn   -30
   3. MoneyDeposited   +50
   current balance = replay(1,2,3) = 120
```

The events are the **source of truth**. They are stored **append-only**: you add new events
but never change or delete old ones, because they are facts about the past (exactly the
[events](/course/software-architecture/event-driven-architecture/events-vs-commands) from
earlier in this chapter).

This has a consequence people trip over: you never fix bad data with an `UPDATE`. If a
deposit was recorded wrong, you don't edit the old event - you append a correcting one (a
reversal, then the right value). The log is a ledger, and accountants don't erase ink either.
It feels clumsy until the first time an auditor asks what changed and when.

## Rebuild state by replaying events

To get the current state, you start from empty and apply each event in order. This is called
**replaying** or building a **[projection](/course/software-architecture/event-driven-architecture/read-models-and-cqrs)** of the events.

```php
final class Account
{
    private int $balanceCents = 0;

    /** @param object[] $events  the stored history, in order */
    public static function fromHistory(array $events): self
    {
        $account = new self();
        foreach ($events as $event) {
            $account->apply($event);
        }
        return $account;
    }

    private function apply(object $event): void
    {
        match (true) {
            $event instanceof MoneyDeposited => $this->balanceCents += $event->amountCents,
            $event instanceof MoneyWithdrawn => $this->balanceCents -= $event->amountCents,
            default => null,
        };
    }

    public function balanceCents(): int
    {
        return $this->balanceCents;
    }
}
```

`fromHistory` never reads a `balance` column - it derives the balance from the events every
time. When something new happens, you append a new event and, next time, the replay simply
includes it. (In practice you cache the rebuilt state with **snapshots** so you don't replay
thousands of events on every read, but the log remains the truth.)

## Benefits

- **A complete audit trail, for free.** You don't just know the balance is 120 - you know
  every deposit and withdrawal that produced it, with no separate history table to maintain.
- **Time travel.** Because you can replay up to any point, you can reconstruct exactly what
  the state was last Tuesday, or debug by replaying events into a fixed version of the code.
- **New questions from old facts.** If you later want a report the original design never
  imagined, you can build a fresh projection from the existing events. The data is already
  there.

## Costs

Event sourcing is powerful, and it is not free:

- **More complexity.** There is no simple "just read the row". Every read needs a rebuild
  (hence snapshots), and you now think in terms of events plus projections.
- **Versioning.** Events live forever, so an old `MoneyDeposited` from two years ago must
  still be replayable after you change the event's shape. You end up versioning event
  schemas and writing upcasters, which is real, ongoing work.
- **Eventual consistency and tooling.** Query-friendly views are derived, not the source, so
  they can lag; and ordinary database tools no longer show you "the answer" directly.

## Common mistake

**Event sourcing the whole application** because it sounds elegant. Most parts of a system -
a settings page, a product catalog - gain nothing from a full event log and suffer all the
complexity and versioning cost. Event sourcing pays off where **history is itself valuable**:
money, orders, anything audited or heavily regulated. Apply it to those specific aggregates
and keep the rest state-based. It is a targeted tool, not a default.

## FAQ

### What is event sourcing?

A pattern where you store the sequence of events that happened as the source of truth,
instead of storing the current state. The current state is computed by replaying those
events from the beginning.

### How do you get the current state in event sourcing?

You replay the events in order, applying each one to a starting state, to rebuild the
current value. To avoid replaying long histories every time, systems periodically save
snapshots and replay only the events after the latest snapshot.

### What are the main downsides of event sourcing?

Added complexity (no simple row read), event schema **versioning** because old events must
stay replayable forever, and eventual consistency in the derived views. It is worth the cost
mainly where the full history has real value, such as financial or audited data.
