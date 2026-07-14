---
name: "Database Isolation Levels Explained: Transactions Without the Guesswork"
slug: database-isolation-levels
short_description: "A practical guide to database isolation levels, the anomalies they prevent, engine defaults in MySQL and PostgreSQL, and how to set them in Laravel."
language: en
published_at: 2026-10-28 09:00:00
is_published: true
tags: [databases, transactions, sql, laravel]
---

I once spent the better part of a Friday chasing a bug where a nightly report showed a total that didn't match the sum of its own line items. The query was fine. The data was fine. The problem was that the report read some rows before a big batch job committed and some rows after. Nobody had thought about **database isolation levels**, so the database happily gave us a snapshot that never actually existed as a consistent state. That afternoon is why I take this topic seriously, and why I'd rather you learn it from an article than from a corrupted month-end report.

Isolation is the "I" in ACID, and it's the one people understand the least. This guide walks through what a transaction actually guarantees, the four standard isolation levels, the specific read anomalies each one lets through, and how the defaults differ between MySQL and PostgreSQL. There's working SQL and a bit of Laravel at the end, because knowing the theory doesn't help if you can't wire it up.

## A quick ACID recap, with the focus where it belongs

ACID is four promises a transaction makes:

- **Atomicity:** all the statements in a transaction succeed together or none of them do. Halfway is not a state the database will leave you in. If the third of five `UPDATE`s throws, the first two roll back.
- **Consistency:** the transaction moves the database from one valid state to another, respecting constraints, foreign keys, and triggers.
- **Isolation:** concurrent transactions don't step on each other in ways that produce nonsense. How strictly this holds is exactly what isolation levels control.
- **Durability:** once you get a commit acknowledgement, the data survives a crash.

Most tutorials spend all their energy on atomicity because it's intuitive: wrap the money transfer in `BEGIN` and `COMMIT` so you never debit one account without crediting the other. Fair enough. But atomicity only describes what happens inside one transaction. The moment two transactions run at the same time, which on any real system is always, isolation is what decides whether they see a coherent picture of the world. That's where the money bugs actually hide.

Atomicity and isolation work as a pair. Atomicity keeps a single unit of work all-or-nothing; isolation keeps concurrent units from contaminating each other's reads. You need both, and the second one has a dial.

## The three read anomalies

Before the levels make sense, you need the vocabulary for what can go wrong. The SQL standard names three phenomena, each strictly worse than the last.

**Dirty read.** Transaction A reads a row that transaction B has modified but not yet committed. If B rolls back, A acted on data that never officially existed. Imagine reading an account balance mid-transfer, before the other leg landed.

**Non-repeatable read.** Transaction A reads a row, transaction B updates that same row and commits, and when A reads it again it gets a different value. The same `SELECT` inside one transaction returns two different answers. That's what bit my report job: it read a row, something committed underneath it, and a later read disagreed.

**Phantom read.** Transaction A runs a query with a `WHERE` clause (say, "all orders over $1000") and gets ten rows. Transaction B inserts an eleventh matching row and commits. A re-runs the exact same query and now sees eleven. No existing row changed; a new one appeared. The set of matching rows shifted under A's feet.

The difference between non-repeatable and phantom reads trips people up. A non-repeatable read is about a specific row's value changing. A phantom read is about the membership of a result set changing because rows were inserted or deleted. Same idea, different granularity.

## The four isolation levels

The SQL standard defines four levels. Each one prevents more anomalies than the one below it, and each one costs you more in concurrency to do so. Here's the canonical mapping.

| Isolation level | Dirty read | Non-repeatable read | Phantom read |
| --- | --- | --- | --- |
| READ UNCOMMITTED | Possible | Possible | Possible |
| READ COMMITTED | Prevented | Possible | Possible |
| REPEATABLE READ | Prevented | Prevented | Possible |
| SERIALIZABLE | Prevented | Prevented | Prevented |

Read that table as a ladder. Each rung up locks the door on one more anomaly.

**READ UNCOMMITTED** is the loosest. It permits all three anomalies, including dirty reads. You almost never want this. The one honest use case is a rough approximate count over a huge table where you genuinely don't care about precision and want to avoid taking any locks. In practice I've never shipped it deliberately.

**READ COMMITTED** guarantees you only ever see committed data, killing dirty reads. But each statement in your transaction sees a fresh snapshot, so a value can change between two reads in the same transaction. This is the pragmatic default for a lot of systems, and it's PostgreSQL's default.

**REPEATABLE READ** freezes the rows you've read: read a row once and it keeps that value for the rest of the transaction, even if someone else commits a change. It prevents dirty and non-repeatable reads. By the letter of the standard it still allows phantoms, though real engines vary on that point (more below).

**SERIALIZABLE** is the strictest. It behaves as if transactions ran one after another with no overlap at all, preventing every anomaly including phantoms. You pay for it: more locking or more serialization failures that you have to catch and retry.

## What your engine actually does by default

Here's where the standard and reality drift, and where a lot of confidently wrong Stack Overflow answers come from. The isolation level you get depends on the engine, and the two most common ones disagree.

**MySQL with InnoDB defaults to REPEATABLE READ.** More interesting, InnoDB's implementation goes further than the standard requires. Through a mechanism called next-key locking (a combination of row locks and gap locks), REPEATABLE READ in InnoDB largely prevents phantom reads too, at least for locking reads. So MySQL's default is quite strong out of the box, which surprises people coming from Postgres.

**PostgreSQL defaults to READ COMMITTED.** Postgres does not implement READ UNCOMMITTED at all; if you ask for it, you get READ COMMITTED. Its REPEATABLE READ is built on true snapshot isolation, which also prevents phantoms in practice. And its SERIALIZABLE uses Serializable Snapshot Isolation (SSI), detecting conflicts and aborting one of the transactions rather than blocking with heavy locks.

So the same `SELECT`-then-`UPDATE` code can behave differently on MySQL and PostgreSQL, purely because their defaults sit at different rungs of the ladder. Develop on one, deploy on the other, and it bites. I once burned an hour on a "works on my machine" concurrency bug that turned out to be nothing but a default-isolation mismatch between a Postgres laptop and a MySQL production box.

A higher isolation level does not make your queries faster or your indexes matter less. Concurrency control and access-path efficiency are separate concerns — if reads are slow, that's a job for [database indexing](/blog/database-indexing-explained), not for turning the isolation dial.

## Setting isolation levels in SQL

You can set the level for the next transaction, or change the session default. Syntax is close to identical across engines.

```sql
-- Set the isolation level for the transaction you are about to start.
-- This must come before the transaction begins.
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;

START TRANSACTION;

SELECT id, seats_left
FROM events
WHERE id = 42;

UPDATE events
SET seats_left = seats_left - 1
WHERE id = 42;

COMMIT;
```

The `SET TRANSACTION ISOLATION LEVEL ...` line applies only to the transaction that follows it. If you want every transaction on the connection to use a level, set it at the session scope instead:

```sql
-- Applies to all subsequent transactions on this session.
SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
```

A word of caution about SERIALIZABLE: it doesn't magically prevent bad outcomes, it converts them into errors you must handle. On Postgres a conflicting transaction fails with a serialization error (SQLSTATE `40001`) and you're expected to retry the whole thing. If you set SERIALIZABLE and don't wrap it in retry logic, you've just traded silent corruption for loud, intermittent failures. That can be a good trade, but only if you actually catch them.

## Isolation levels in Laravel

Laravel doesn't expose a fluent `->isolationLevel()` helper, so you set it with a raw statement on the connection right before opening the transaction. Because the setting applies to the next transaction, order matters.

```php
use Illuminate\Support\Facades\DB;

DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

DB::transaction(function () {
    $event = DB::table('events')->where('id', 42)->first();

    if ($event->seats_left < 1) {
        throw new RuntimeException('Sold out');
    }

    DB::table('events')
        ->where('id', 42)
        ->decrement('seats_left');
});
```

The genuinely useful piece here is the second argument to `DB::transaction`. Its signature is `DB::transaction(Closure $callback, int $attempts = 1)`. When you pass an `$attempts` value greater than one, Laravel reruns the closure if the transaction fails with a deadlock, up to that many times:

```php
// Retry the whole closure up to 3 times if a deadlock is detected.
DB::transaction(function () {
    // ... work that might deadlock under contention
}, 3);
```

This matters a lot at higher isolation levels, where the database is more willing to abort a transaction to preserve correctness. Pairing a strict isolation level with a retry count is the standard recipe: let the database refuse unsafe interleavings, and let your app quietly try again.

Two caveats I've learned to respect. First, the retried closure must be idempotent: if it sends an email or charges a card outside the DB, a retry does it twice. Keep side effects out of the transaction body. Second, `decrement` above is an atomic update, so it sidesteps the read-then-write gap entirely; that's often a cleaner fix than cranking isolation up.

For the broader question of when to reach for isolation versus explicit row locks, [optimistic vs pessimistic locking](/blog/optimistic-vs-pessimistic-locking) covers the trade-offs, and [preventing race conditions in a web app](/blog/preventing-race-conditions-web-app) collects the practical patterns.

## How to choose

My rough heuristic, after years of getting it wrong in both directions:

- Start with your engine's default. It's the default because it's a sane balance for most workloads.
- Reach for **REPEATABLE READ** when a single transaction reads the same data more than once and needs a stable view — reports, exports, multi-step calculations.
- Reach for **SERIALIZABLE** only for the small number of operations where a phantom would genuinely corrupt a business invariant, like enforcing "no more than N bookings for this slot." And always pair it with retries.
- Don't raise the global level to fix one query. Scope it to the transaction that needs it.

## FAQ

**Is a higher isolation level always safer?**
Safer against anomalies, yes, but not free. Higher levels mean more locking or more aborted transactions, which reduces throughput and can introduce deadlocks. The safest correct choice is the lowest level that prevents the specific anomaly your operation can't tolerate.

**Why does the same code behave differently on MySQL and PostgreSQL?**
Because their defaults differ: InnoDB defaults to REPEATABLE READ while PostgreSQL defaults to READ COMMITTED. A read-then-write sequence sees a stable snapshot under one and a moving one under the other. Test on the engine you deploy to, not just the one on your laptop.

**Does REPEATABLE READ prevent phantom reads?**
By the strict SQL standard, no. But both major engines go beyond the standard here: InnoDB uses next-key locking to block phantoms on locking reads, and PostgreSQL's snapshot-based REPEATABLE READ prevents them too. So in practice, on MySQL and Postgres, you usually get phantom protection at this level.

**What happens if a SERIALIZABLE transaction fails?**
The database aborts one of the conflicting transactions with a serialization error rather than committing a bad result. Your application is expected to catch that error and retry the transaction. In Laravel, the `$attempts` argument to `DB::transaction` gives you deadlock retries for free; serialization failures you typically handle with your own retry loop.

## Conclusion

Isolation levels are a dial, not a switch. READ UNCOMMITTED lets everything through, SERIALIZABLE blocks everything, and the two levels in the middle, READ COMMITTED and REPEATABLE READ, are where nearly all real applications live. Learn the three anomalies, memorize the table above, and check what your specific engine actually does rather than trusting the standard.

Concretely: find out your database's default, keep it for the common case, and raise the level only on the specific transactions where a dirty, non-repeatable, or phantom read would break a real business rule. Pair anything strict with retry logic. Do that, and you won't spend a Friday afternoon explaining to your boss why the month-end report added up to a number that never existed.