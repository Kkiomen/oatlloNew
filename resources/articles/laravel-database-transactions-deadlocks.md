---
name: "Laravel Database Transactions and How to Avoid Deadlocks"
slug: laravel-database-transactions-deadlocks
short_description: "How DB::transaction works, when deadlocks happen in MySQL, and how automatic retries, lock ordering and lockForUpdate keep concurrent writes safe."
language: en
published_at: 2027-03-01 09:00:00
is_published: true
tags: [laravel, php, database, mysql]
---

The first time a deadlock took down one of my endpoints, the logs were almost useless. A single line, `SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction`, showing up maybe forty times an hour under load and nowhere else. No stack trace worth reading, no obvious bad query. The code looked fine. That's the frustrating part about deadlocks: the individual queries are usually correct, and the bug only exists when two of them run at the same time.

So this is a piece about the patterns that eventually made those 1213s go away for me: how transactions actually behave in Laravel, why InnoDB starts killing them, and the three fixes that hold up under load — automatic retries, consistent lock ordering, and pessimistic locking with `lockForUpdate`.

## What a transaction actually guarantees

A transaction wraps a group of statements so they either all commit or all roll back. Laravel gives you two ways to run one, and the difference matters more than the docs let on.

The closure form is what you want most of the time:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $order = Order::create([...]);

    $order->items()->createMany($cartItems);

    Inventory::where('product_id', $productId)
        ->decrement('stock', $quantity);
});
```

If any statement inside the closure throws, Laravel calls `rollBack()` for you and re-throws the exception. If the closure returns cleanly, it commits. You cannot forget to roll back, because there's no path where the closure exits without Laravel deciding the outcome. That single property removes an entire category of bugs.

The manual form exists for cases where control flow doesn't fit a closure:

```php
DB::beginTransaction();

try {
    $order = Order::create([...]);
    // ... more work, maybe conditional early returns
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

The rule with the manual form is simple and unforgiving: every `beginTransaction()` needs exactly one `commit()` or `rollBack()` on every code path, including exceptions. I've debugged a "database is locked, connection pool exhausted" incident that turned out to be a method that returned early on a validation failure and never rolled back. The transaction stayed open, held its locks, and the next requests piled up behind it. Prefer the closure unless you genuinely can't use it.

One thing people miss: `DB::transaction` accepts a **second argument**, the number of attempts.

```php
DB::transaction(function () {
    // ...
}, 3);
```

That number is not a general retry-on-any-error mechanism. It retries specifically on deadlocks and lock-wait timeouts. This is the single cheapest defense you have, and I'll come back to why it works after explaining what a deadlock is.

## Why deadlocks happen

A deadlock is not corruption and it's not a Laravel problem — it's the database doing exactly what it's supposed to. InnoDB detects that two transactions are each holding a lock the other one needs, and rather than letting them wait forever, it picks one, kills it, and rolls it back with error 1213. The victim's work is undone. The survivor proceeds.

The classic cause is **inconsistent lock ordering**. Imagine two requests transferring money between accounts:

```php
// Request A: transfer from account 1 to account 2
DB::transaction(function () {
    Account::where('id', 1)->lockForUpdate()->first();
    Account::where('id', 2)->lockForUpdate()->first();
    // ... move the balance
});

// Request B: transfer from account 2 to account 1
DB::transaction(function () {
    Account::where('id', 2)->lockForUpdate()->first();
    Account::where('id', 1)->lockForUpdate()->first();
    // ... move the balance
});
```

Run these simultaneously and you get a textbook deadlock. A locks row 1 and wants row 2. B locks row 2 and wants row 1. Neither can proceed. InnoDB kills one.

The fix is to always acquire locks in the same order, regardless of the business direction:

```php
DB::transaction(function () use ($fromId, $toId) {
    // Lock in a fixed order — smallest id first — no matter
    // which way the money is actually moving.
    $ids = [$fromId, $toId];
    sort($ids);

    $accounts = Account::whereIn('id', $ids)
        ->orderBy('id')
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

    $accounts[$fromId]->decrement('balance', $amount);
    $accounts[$toId]->increment('balance', $amount);
});
```

Now every transaction that touches accounts 1 and 2 locks them in the order 1 then 2. Two transfers between the same pair can no longer form a cycle. One waits for the other and then proceeds. Consistent ordering is the most reliable structural fix there is, and it costs nothing at runtime.

### Gap locks and the deadlock you didn't see coming

The subtler deadlocks come from **gap locks**, and they surprised me the first time because there was no obvious shared row. Under the default `REPEATABLE READ` isolation level, InnoDB locks not just rows you touch but the *gaps* between index values to stop phantom inserts.

A range query like `WHERE created_at > ? AND status = 'pending'` can lock a range of the index. Two transactions inserting rows that fall into overlapping gaps, or one inserting while another range-scans, can deadlock even though they never touch the same existing row. The tell-tale sign is `INSERT` statements showing up in `SHOW ENGINE INNODB STATUS` under `LATEST DETECTED DEADLOCK`. If your deadlocks involve inserts into a table with a unique index and no obvious contended row, gap locks are usually the reason.

## Retries: the pragmatic first line of defense

Even with perfect lock ordering, you won't drive deadlocks to zero on a busy system. Lock-wait timeouts, gap locks, and the occasional contended hot row will still throw the odd 1213 at you. So you design for it: make the transaction safe to run again, and let it run again.

That's what the second argument does:

```php
DB::transaction(function () use ($orderId) {
    $order = Order::lockForUpdate()->findOrFail($orderId);

    if ($order->status !== 'pending') {
        return; // already processed, nothing to do
    }

    $order->update(['status' => 'paid']);
    // ... other writes
}, 3);
```

When InnoDB rolls back this transaction as a deadlock victim, Laravel catches it, waits, and runs the whole closure again from the top — up to three times. Because a rolled-back transaction leaves the database exactly as it was before, re-running is safe *as long as your closure has no side effects outside the database*. That last part is the trap.

**Never put non-database side effects inside a retryable transaction closure.** Sending an email, dispatching a job, charging a card via an HTTP call — if the transaction retries, those run twice. Charging a customer's card twice because of a silent deadlock retry is the kind of bug that ends up in a post-mortem. Do the external work after the transaction commits:

```php
$order = DB::transaction(function () use ($orderId) {
    $order = Order::lockForUpdate()->findOrFail($orderId);
    $order->update(['status' => 'paid']);
    return $order;
}, 3);

// Runs once, only after a successful commit.
OrderPaidNotification::dispatch($order);
```

Laravel's `DB::afterCommit` and the `ShouldDispatchAfterCommit` interface on jobs and events help here too — they hold the side effect until the outermost transaction commits, which also means it won't fire on a retry that eventually fails.

## Pessimistic locking: lockForUpdate vs sharedLock

When you read a row intending to update it based on its current value — decrement stock, deduct a balance, claim a job — a plain `SELECT` isn't enough. Between your read and your write, another request can change the value, and you'll overwrite their change. This is a lost update, and it's a data-integrity bug, not a performance one.

`lockForUpdate()` takes an exclusive lock. No other transaction can read-for-update or write that row until yours commits:

```php
DB::transaction(function () use ($productId, $qty) {
    $product = Product::lockForUpdate()->findOrFail($productId);

    if ($product->stock < $qty) {
        throw new OutOfStockException();
    }

    $product->decrement('stock', $qty);
});
```

Two concurrent buyers hitting the last unit will now serialize: one gets the lock, checks stock, decrements, commits; the other waits, then reads the *new* stock value and correctly fails. Without the lock, both could read "stock: 1" and both decrement, leaving you at -1.

`sharedLock()` is the weaker cousin. It takes a shared lock — others can also read with a shared lock, but nobody can write until you're done. Use it when you need the row to stay stable during your read but you're not going to modify it yourself. In practice I reach for `lockForUpdate` far more often, because the usual reason to lock a row is that I intend to change it.

| | Plain SELECT | `sharedLock()` | `lockForUpdate()` |
|---|---|---|---|
| Others can read | yes | yes (shared) | blocked (for-update) |
| Others can write | yes | blocked | blocked |
| Use when | read-only, no contention | read must stay stable | you'll update the row |

One caution: pessimistic locks are held until the transaction commits, so keep locked transactions short. Locking a hot row and then doing slow work inside the transaction turns one slow request into a queue of blocked ones. Lock late, commit early.

## A production failure mode worth knowing

The nastiest incident I've seen wasn't a raw deadlock at all — it was a **lock-wait timeout** dressed up as one. A reporting job ran a long transaction that touched a wide range of an orders table. Nothing wrong with the job in isolation. But it held its locks for the full 40 seconds it took to run, and every checkout that tried to write an overlapping row waited on `innodb_lock_wait_timeout` (default 50 seconds) and eventually failed with `SQLSTATE[HY000]: 1205 Lock wait timeout exceeded`.

The retries made it look worse, not better. Each timed-out checkout retried, piled back onto the same waiting queue, and the whole thing snowballed. The fix had nothing to do with retry counts:

- The reporting job didn't need a transaction at all for a read-heavy scan, so we dropped it and read with `READ COMMITTED` to avoid gap locking.
- We split the long write into smaller batches so no single transaction held locks for more than a fraction of a second.

The lesson stuck: retries handle *transient* contention. They cannot fix a transaction that holds locks too long — that's a design problem, and adding attempts just amplifies the pileup. When you see 1205 (lock wait timeout) rather than 1213 (deadlock), stop tuning retries and go find the long-running transaction.

## FAQ

### What's the difference between error 1213 and 1205?

1213 is a deadlock: InnoDB found a cycle and killed your transaction immediately as the victim. 1205 is a lock-wait timeout: no cycle, but you waited longer than `innodb_lock_wait_timeout` for a lock someone else was holding. 1213 usually means inconsistent lock ordering; 1205 usually means a transaction is holding locks too long.

### Does DB::transaction retry automatically?

No. The default is a single attempt. You have to pass the number of attempts as the second argument, and it only retries on deadlocks and lock-wait timeouts — not on validation errors, constraint violations, or anything you throw yourself.

### Is it safe to nest transactions in Laravel?

Yes, Laravel supports nesting using savepoints. Inner transactions become savepoints rather than real commits, so the actual commit only happens when the outermost transaction finishes. Just be aware that a rollback in an inner "transaction" only rolls back to its savepoint, and side effects registered with `afterCommit` fire only when the outer one commits.

### Where do I inspect what caused a deadlock?

Run `SHOW ENGINE INNODB STATUS` and read the `LATEST DETECTED DEADLOCK` section. It shows both transactions, the locks each held, and which one InnoDB rolled back. That block tells you exactly which two statements collided, which is usually all you need to spot the lock-ordering problem.

## Where to start

If you're seeing deadlocks now, do the cheap thing first: wrap the affected transaction and pass `3` as the second argument, and move every email, job, and API call to *after* the commit. That alone stops most of the bleeding. Then find the real cause — pull up `SHOW ENGINE INNODB STATUS`, look at the two colliding statements, and impose a consistent lock order on whatever rows they share. Retries buy you breathing room; consistent ordering and short transactions are what actually fix it.
