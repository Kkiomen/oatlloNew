---
name: "The Outbox Pattern for Reliable Messaging"
slug: outbox-pattern-reliable-messaging
short_description: "Why publishing an event after a DB commit loses messages, and how a transactional outbox table plus a relay fixes it in Laravel."
language: en
published_at: 2027-03-10 09:00:00
is_published: true
tags: [architecture, laravel, database, messaging, devops]
---

The bug that finally made me care about this was quiet. An order got saved, the customer got charged, and the warehouse never heard about it. No exception, no stack trace, nothing in Sentry. The order row was right there in the database. The `OrderPlaced` event that should have gone to the message broker simply never arrived, because the process died in the half-second between the commit and the publish.

That gap is the whole problem. You have two systems - your database and your broker - and no way to write to both at once. This article is about closing that gap with a transactional outbox, wiring a relay to drain it, and the parts nobody mentions until they page you at 3am: duplicate delivery, ordering, and when to reach for Debezium instead.

## The dual-write problem

Look at code you've almost certainly written:

```php
DB::transaction(function () use ($data) {
    $order = Order::create($data);
    $order->markAsPaid();
});

// commit succeeded, now tell everyone
Broker::publish(new OrderPlaced($order->id));
```

Two writes, two systems, and they are not atomic. Four things can happen and only one is fine:

- DB commit works, publish works. Great.
- DB commit works, publish throws. The order exists; nobody downstream knows. This is my warehouse bug.
- DB commit works, then the container gets OOM-killed before `publish()` even runs. Same silent loss, no exception to catch.
- Publish works, DB commit rolls back. Now you've announced an order that doesn't exist - a phantom event.

You can't wrap a database and a Kafka/RabbitMQ/SQS call in one transaction. There's no shared commit. A distributed transaction (two-phase commit, XA) technically exists, but it's slow, brokers barely support it, and it turns a broker outage into a database outage. Nobody sane runs 2PC for this in 2027.

Moving the publish *inside* the transaction is worse, not better:

```php
DB::transaction(function () use ($data) {
    $order = Order::create($data);
    Broker::publish(new OrderPlaced($order->id)); // published, then...
    $order->markAsPaid(); // ...this throws, transaction rolls back
});
```

Now the broker holds an event for an order that got rolled back. You've traded lost messages for phantom messages. The broker call also isn't transactional, so a rollback can't un-send it.

The core insight: **there is exactly one system you can write to transactionally - your database.** So write the intent to send there too, in the same transaction as the business change. Deliver it afterwards, separately, and keep retrying until it lands.

## The transactional outbox

Add a table. The event goes into it as part of the same commit as the data that produced it.

```sql
CREATE TABLE outbox_messages (
    id CHAR(26) PRIMARY KEY,          -- ULID, sortable by creation time
    aggregate_type VARCHAR(64) NOT NULL,
    aggregate_id VARCHAR(64) NOT NULL,
    event_type VARCHAR(128) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP(6) NOT NULL,
    published_at TIMESTAMP(6) NULL,
    INDEX idx_unpublished (published_at, id)
);
```

The transaction now touches only the database, so it either fully commits or fully rolls back:

```php
use Illuminate\Support\Str;

DB::transaction(function () use ($data) {
    $order = Order::create($data);
    $order->markAsPaid();

    DB::table('outbox_messages')->insert([
        'id'             => (string) Str::ulid(),
        'aggregate_type' => 'order',
        'aggregate_id'   => (string) $order->id,
        'event_type'     => 'order.placed',
        'payload'        => json_encode([
            'order_id' => $order->id,
            'total'    => $order->total,
        ]),
        'created_at'     => now(),
        'published_at'   => null,
    ]);
});
```

If any line throws, the whole thing rolls back - order *and* outbox row together. If it commits, the event is durably recorded. It hasn't been published yet, but it can't be lost, and that's the entire point. The broker is now downstream of a guaranteed record instead of racing it.

One design note that saves pain later: store a fully-formed payload, not just an ID. A relay reading `{"order_id": 42}` and calling back into your models re-reads the *current* state, which may have changed since the event happened. Snapshot what the event means at the moment it's created. The outbox row is a fact about the past; keep it that way.

## The relay that actually publishes

Something has to move rows from the table to the broker. The simplest version is a poller - a loop that grabs unpublished rows, sends them, and stamps `published_at`.

```php
class OutboxRelay
{
    public function drain(int $batch = 100): void
    {
        $rows = DB::table('outbox_messages')
            ->whereNull('published_at')
            ->orderBy('id')          // ULID order == creation order
            ->limit($batch)
            ->lockForUpdate()        // stop two relays grabbing the same rows
            ->get();

        foreach ($rows as $row) {
            Broker::publish($row->event_type, $row->payload);

            DB::table('outbox_messages')
                ->where('id', $row->id)
                ->update(['published_at' => now()]);
        }
    }
}
```

Wrap the `lockForUpdate` read in a short transaction so the rows are held only while you're working them. Run it from a long-lived command:

```php
class RelayOutbox extends Command
{
    protected $signature = 'outbox:relay';

    public function handle(OutboxRelay $relay): void
    {
        while (true) {
            DB::transaction(fn () => $relay->drain());
            usleep(200_000); // 200ms between empty passes
        }
    }
}
```

Supervise it (systemd, Supervisor, a Kubernetes deployment) so it restarts on crash. Poll frequency is a latency-vs-load knob: 200ms feels instant to users and is trivial load on an indexed `WHERE published_at IS NULL`. That partial-index-style lookup is why the schema has `idx_unpublished` - without it the relay table-scans and gets slower as history piles up.

A subtlety people miss: **the read order and the publish order are the same, but the *stamp* isn't the commit.** If the process dies after `Broker::publish` but before the `update`, that row stays unpublished and gets sent again on the next pass. You cannot close that window from here. Which brings us to the part that isn't optional.

## At-least-once, so consumers must be idempotent

The outbox gives you **at-least-once delivery**, never exactly-once. Exactly-once across a network doesn't exist; anyone who promises it is hiding a dedup step somewhere. Because the relay can crash between "sent" and "marked sent," every message can arrive twice. Your consumers have to be built for that.

The fix is to make processing a message a second time a no-op. Put the message's unique id (that ULID) to work on the consumer side:

```php
public function handle(array $message): void
{
    $id = $message['id'];

    // Insert-or-ignore acts as a dedup gate.
    $fresh = DB::table('processed_messages')->insertOrIgnore([
        'id'           => $id,
        'processed_at' => now(),
    ]);

    if ($fresh === 0) {
        return; // already handled, drop it
    }

    $this->process($message);
}
```

`insertOrIgnore` leans on the primary key: the first arrival inserts, any duplicate hits the constraint and is silently skipped, and you skip the work. For handlers that only ever *set* a value - flip a status, upsert a projection - you may not even need the table; the operation is naturally idempotent because running it twice lands on the same state. Handlers that *increment* or append are the dangerous ones, and those are exactly where the dedup table earns its keep.

This is the same discipline you use to make an HTTP endpoint safe to retry with an [idempotency key](/idempotency-key-api-safe-retries) - a stable id, a check on the write side, a duplicate that changes nothing. Same shape, different transport. Once you've done it in one place, the other stops feeling exotic.

Ordering deserves a flag too. If two events for the same order can't be swapped without breaking meaning, don't fan the relay out to parallel workers blindly - partition by `aggregate_id` so a single order's events stay in one lane. Global ordering is expensive and usually unnecessary; per-aggregate ordering is cheap and usually enough.

## The CDC alternative: let the log do the polling

The poller works and I've shipped it more than once, but it has a smell: you're hammering the database asking "anything new?" thousands of times an hour, mostly to hear "no." Change Data Capture flips that around. Instead of reading the table, you read the database's own replication log - the MySQL binlog or Postgres WAL - and react to inserts as they're written.

**Debezium** is the usual tool. Point it at the binlog, and every `INSERT` into `outbox_messages` becomes a message on Kafka, with no polling loop and near-zero added latency. There's even a Debezium "Outbox Event Router" built for exactly this table shape - it reads your outbox rows and routes them to topics by `aggregate_type`.

The trade is real, so weigh it honestly:

| | Polling relay | CDC (Debezium) |
|---|---|---|
| Moving parts | A command + a supervisor | Kafka Connect + Debezium + config |
| Latency | Poll interval (~200ms) | Near-real-time |
| DB load | Constant light queries | Reads the log, no app queries |
| Ops burden | Low - it's your code | You now run Connect and understand replication slots |
| Good fit | Most Laravel apps | High throughput, already on Kafka |

My honest default: if you're a team without a Kafka platform already, write the poller. It's fifty lines you fully understand, and a 200ms interval is invisible to users. Reach for Debezium when volume makes polling wasteful or when you already run Kafka Connect and adding one more connector is free. Don't stand up a Kafka cluster *for* the outbox - that's the tail wagging the dog.

One CDC gotcha: with Debezium, the relay never marks rows published, so nothing prunes the table. Add a scheduled job that deletes outbox rows older than a few days, or configure Postgres to emit and drop them. Either way, decide who cleans up before the table hits a hundred million rows.

## Keeping the table from becoming a landfill

Whichever relay you pick, the outbox grows forever unless you prune it. Delete in batches so you don't lock the table:

```php
DB::table('outbox_messages')
    ->whereNotNull('published_at')
    ->where('published_at', '<', now()->subDays(7))
    ->limit(1000)
    ->delete();
```

Keep a week of published rows - long enough to debug "did this event actually go out?" during an incident, short enough that the table stays small. Never delete unpublished rows; those are undelivered work, and dropping one is the exact data loss the pattern exists to prevent.

## FAQ

**Does the outbox pattern guarantee exactly-once delivery?**
No. It guarantees at-least-once. The relay can crash after publishing but before marking the row sent, so any message can arrive more than once. You get effectively-once behavior by making consumers idempotent - a dedup key checked on the write side. There is no exactly-once across a network; that's a property you build at the consumer, not one the transport hands you.

**Where should the outbox table live - same database as the business data?**
Yes, and that's the whole point. The outbox row and the data that caused it must commit in the same transaction, which only works if they share a database (and, on most engines, the same connection). A separate "events database" reintroduces the dual-write problem you were trying to kill.

**Can I skip the outbox table and use Laravel's queue with `afterCommit`?**
`afterCommit` fixes the *rollback* case - it won't dispatch a job for a transaction that rolled back. It does not fix the *crash* case: if the process dies after commit but before the job is enqueued, the job is lost, because the enqueue is a second write to a second system (Redis). `afterCommit` narrows the window; the outbox closes it. For truly must-deliver events, use the table.

**How is this different from event sourcing?**
Different problem. Event sourcing makes the event log your source of truth and rebuilds state from it. The outbox keeps your normal state as truth and just reliably *notifies* other systems about changes. You can use the outbox with a plain CRUD model and no event sourcing anywhere.

## The takeaway

The dual-write problem never announces itself. It shows up as a support ticket weeks later - an order with no shipment, a payment with no receipt email - and by then the logs have rotated. The outbox pattern trades a little machinery (one table, one relay, idempotent consumers) for a guarantee: if the business change committed, the event will be delivered, even if the broker was down, even if the process died mid-flight.

Start with the polling relay. It's the version you can read top to bottom and fix when it wakes you up. Add the dedup table on your consumers before you ship, not after your first duplicate charge. Graduate to Debezium only when the numbers or your existing stack make it the obvious choice - and when you do, put a pruning job on the calendar the same day.
