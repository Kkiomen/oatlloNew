---
name: "Optimistic vs Pessimistic Locking in Databases: A Practical Guide"
slug: optimistic-vs-pessimistic-locking
short_description: "Optimistic vs pessimistic locking explained with real SQL and Laravel code, trade-offs, and when to pick each for concurrent writes."
language: en
published_at: 2026-09-14 09:00:00
is_published: true
tags: [databases, concurrency, laravel, sql]
---

Two users open the same order in your admin panel. One changes the shipping address, the other changes the total. Both hit Save within the same second. One of those writes is about to quietly overwrite the other, and nobody will notice until a customer complains. Choosing correctly between **optimistic vs pessimistic locking** is how you stop that lost update from ever happening.

This is a comparison guide, so I'll keep it grounded: what each strategy actually does at the database level, working SQL and Laravel code for both, and the trade-offs I've hit in production. No hand-waving about "it depends" without telling you what it depends on.

## The problem both strategies solve

Concurrency bugs of this kind have a name: the lost update. Two transactions read the same row, both compute a new value from what they read, and the second write clobbers the first. Neither transaction did anything wrong on its own. The damage comes from them overlapping.

You can't fix this by being careful in application code. `SELECT` then `UPDATE` in two separate round-trips leaves a gap, and under real traffic something will slip into that gap. Locking strategies close it. There are two philosophies for doing so, and they sit at opposite ends of a spectrum.

- **Optimistic locking** assumes conflicts are rare. It lets everyone read freely and only checks for a collision at write time.
- **Pessimistic locking** assumes conflicts are likely. It grabs a lock up front so nobody else can touch the row until you're done.

The word "locking" in "optimistic locking" is a bit of a misnomer, which trips people up constantly. Let's clear that up first.

## What is optimistic locking

Optimistic locking does **not** acquire a database lock. Nothing is held. Instead you add a `version` column (an integer, or you reuse `updated_at`), read it along with the row, and then make your update conditional on that version still being what you saw.

The trick lives entirely in the `WHERE` clause:

```sql
-- You read the row earlier and saw version = 7
UPDATE products
SET stock = stock - 1,
    version = version + 1
WHERE id = 42
  AND version = 7;
```

If another transaction already bumped the row to version 8, your `WHERE` matches zero rows. The database reports **0 rows affected**, and that is your conflict signal. No exception, no deadlock, just a count you have to check. Ignore that count and the whole mechanism is worthless, which is the single most common mistake I see.

When you detect the conflict, you reload the fresh row and either retry the operation or bubble a "someone else changed this, please review" message up to the user. For automated retries, pair this with an [exponential backoff retry](/blog/exponential-backoff-retry) so a burst of colliding writes doesn't hammer the database in lockstep.

### Optimistic locking in Laravel

Eloquent doesn't ship optimistic locking out of the box, but it's a few lines. The important part is checking the affected-row count that `update()` returns:

```php
public function decrementStock(int $productId): void
{
    $attempts = 0;

    do {
        $product = Product::findOrFail($productId);

        if ($product->stock < 1) {
            throw new OutOfStockException($productId);
        }

        // Conditional update: only succeeds if version is unchanged.
        $affected = Product::where('id', $product->id)
            ->where('version', $product->version)
            ->update([
                'stock'   => $product->stock - 1,
                'version' => $product->version + 1,
            ]);

        // 1 = we won the race, 0 = someone updated first, retry.
    } while ($affected === 0 && ++$attempts < 3);

    if ($attempts >= 3) {
        throw new ConcurrencyException('Could not update stock after retries.');
    }
}
```

Two things worth calling out. The `update()` call bypasses model events and mutators because it's a query-builder update, not a `$model->save()`; if you rely on Eloquent events, you'll need a different shape. And the retry cap matters. Without it, a hot row under heavy contention can spin far longer than you'd like.

## What is pessimistic locking

Pessimistic locking takes the opposite bet. Before you read the row, you acquire a lock on it, and the database blocks anyone else who tries to lock the same row until your transaction commits or rolls back. There's no version column and no "0 rows affected" dance. If you got past the `SELECT`, you own the row.

This only works inside a transaction. The lock is released when the transaction ends, so an autocommitted single statement gives you nothing useful here.

```sql
BEGIN;

-- Blocks other writers until this transaction commits/rolls back.
SELECT stock
FROM products
WHERE id = 42
FOR UPDATE;

-- We now hold the row exclusively; safe to read-modify-write.
UPDATE products
SET stock = stock - 1
WHERE id = 42;

COMMIT;
```

`FOR UPDATE` takes an exclusive lock: other transactions can neither update the row nor take their own `FOR UPDATE` on it. If you only need to prevent writes while you read consistently but are fine with other readers, `FOR SHARE` (a shared lock) is the lighter option.

### Pessimistic locking in Laravel

Laravel exposes both lock modes cleanly, and both must live inside a transaction:

```php
use Illuminate\Support\Facades\DB;

public function decrementStock(int $productId): void
{
    DB::transaction(function () use ($productId) {
        // Exclusive row lock held until the transaction closes.
        $product = Product::where('id', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($product->stock < 1) {
            throw new OutOfStockException($productId);
        }

        $product->stock -= 1;
        $product->save();
    });
}
```

`->lockForUpdate()` maps to `SELECT ... FOR UPDATE`; `->sharedLock()` maps to `SELECT ... FOR SHARE`. The `DB::transaction()` closure commits automatically on success and rolls back on any thrown exception, which also releases the lock. Keep the body of that closure short. Every millisecond you spend inside it is a millisecond other requests wait behind the lock.

## Optimistic vs pessimistic locking: the trade-offs

Here's the honest comparison. Neither wins outright; they win in different conditions.

| Aspect | Optimistic locking | Pessimistic locking |
|---|---|---|
| Holds a DB lock? | No | Yes (`FOR UPDATE` / `FOR SHARE`) |
| Conflict detected | At write time (0 rows affected) | Prevented up front by blocking |
| Best when | Contention is low, reads dominate | Contention is high, writes collide often |
| Failure mode | Wasted retries under heavy contention | Blocking, lock waits, deadlocks |
| Scales with | High read concurrency | Short critical sections |
| Extra schema | Needs a `version` / `updated_at` column | None required |
| Cross-request safe | Yes (great for long user think-time) | No (don't hold a lock across HTTP calls) |

The read-versus-write balance is the deciding factor most of the time. If ninety-nine out of a hundred requests just read and the odd write rarely overlaps another, optimistic locking is nearly free. You pay nothing on the happy path and only occasionally retry.

Flip the ratio. If dozens of transactions all fight over the same inventory row every second, optimistic locking degrades into a retry storm: everyone reads version 7, one wins, the rest retry, read version 8, one wins, and so on. That churn burns CPU and database round-trips. Pessimistic locking serializes them cleanly instead, one at a time, no wasted work.

There's a second dimension people forget: **how long you hold the resource**. Optimistic locking is the only sane choice when a human is in the loop. Picture an editor opening a document, going to lunch, and saving an hour later. You absolutely cannot hold a `FOR UPDATE` lock across that gap; you'd block the table for an hour. The version check handles it gracefully instead.

### The deadlock tax on pessimistic locking

Pessimistic locking buys safety with a real risk: deadlocks. Two transactions each lock a row the other needs, and the database has to kill one of them to break the cycle. The classic cause is inconsistent lock ordering.

```php
// Transaction A locks account 1 then 2.
// Transaction B locks account 2 then 1.
// They meet in the middle and deadlock.
```

The standard defense is to always acquire locks in a consistent order, for example by sorting IDs ascending before locking. Even then, your code has to expect a deadlock exception (SQLSTATE `40001` / `40P01`) and retry the whole transaction. So "pessimistic locking never needs retries" is a myth. It needs fewer, and for a different reason.

## A quick word on transaction isolation

Both strategies interact with your isolation level, and it's easy to assume the database is doing more than it is. Under MySQL's default `REPEATABLE READ` or PostgreSQL's default `READ COMMITTED`, a plain `SELECT` gives you a consistent snapshot but does **not** stop another transaction from updating that row underneath you. That snapshot is exactly why the lost update slips through and why you need one of these two strategies on top.

If you want the row-level guarantees, you have to ask for them explicitly with the version check or the `FOR UPDATE`. The default isolation won't hand them to you for free.

## FAQ

### Does optimistic locking actually lock anything in the database?

No. That's the confusing part of the name. Optimistic locking holds no database lock at all. It relies on a `version` column (or `updated_at`) and a conditional `UPDATE ... WHERE version = ?`. When zero rows are affected, you know someone else wrote first and you retry. The only real "lock" is the brief row lock the `UPDATE` itself takes to write, same as any update.

### When should I use pessimistic locking instead of optimistic?

Reach for pessimistic locking when the same rows are contended heavily and retries would pile up, when the critical section is short and fully inside one transaction, or when a conflict is expensive to recover from and you'd rather serialize than gamble. Inventory decrements on a flash-sale product and balance transfers between two accounts are textbook cases.

### How do I implement optimistic locking in Laravel?

Add a `version` integer column, read it with the model, then run a query-builder update scoped to both the id and the original version, incrementing the version in the same statement. Check the affected-row count: `1` means you won, `0` means a conflict occurred and you should reload and retry within a capped loop. Eloquent has no built-in flag for this, so you wire the version check yourself.

### Which one performs better under high concurrency?

It depends on the read/write mix on the contended rows. For read-heavy workloads with rare colliding writes, optimistic locking performs better because it avoids blocking entirely. For write-heavy contention on the same rows, pessimistic locking usually wins because it avoids the retry churn that optimistic locking suffers when many writers collide.

## Conclusion

Pick based on how often your writes actually collide, not on which sounds safer. Default to optimistic locking: add a `version` column, make updates conditional, check the affected-row count, and retry on zero. It costs nothing on the happy path and it's the only workable option when a human's think-time sits between read and write.

Switch to pessimistic locking, `lockForUpdate()` inside a short transaction, when a few hot rows take a beating and retries would otherwise spiral. Just budget for consistent lock ordering and a deadlock-retry path, because that's the price of holding the lock. If your operation is a retryable side effect like charging a card, layer an [idempotency key for safe retries](/blog/idempotency-key-api-safe-retries) on top so a retried transaction can't double-charge. Get the strategy right and that two-users-one-order race stops being a bug report and becomes a non-event.