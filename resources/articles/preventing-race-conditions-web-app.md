---
name: "Preventing Race Conditions in a Web App: Locks, Atomics, and Idempotency"
slug: preventing-race-conditions-web-app
short_description: "A practical guide to fixing the race condition web app bug: unique constraints, atomic updates, row locks, cache locks, and idempotency keys."
language: en
published_at: 2026-09-23 09:00:00
is_published: true
tags: [concurrency, laravel, php, backend]
---

The first time a race condition in a web app cost me real money, it was a coupon. A launch discount, limited to 500 redemptions, went out to about 40,000 people. We handed out roughly 900. The code looked fine in review. It read the redemption count, checked it against the limit, and then wrote a new row. On a quiet Tuesday afternoon that logic is correct. Under a thundering herd of shoppers hitting "apply" in the same second, it was a slot machine.

That gap between "looks correct" and "is correct under load" is where race conditions live. This article walks through what they actually are, the three or four shapes they take in typical web code, and the concrete tools that fix them for good. Examples are in PHP and Laravel because that's what I reach for, but the ideas port anywhere.

## What a race condition actually is

A race condition is a **check-then-act sequence on shared state that runs under concurrency**. That's the whole definition, and every word earns its place.

- **Shared state**: a database row, a Redis counter, a file, a cache entry. Something two requests can both touch.
- **Check-then-act**: you read a value, make a decision based on it, then write. The read and the write are separate steps.
- **Concurrency**: two or more requests interleave, so request B reads before request A has written.

The bug is that you assumed those steps were one indivisible action. They aren't. Between your check and your act, another request slips in and changes the world out from under you. Your decision was based on a snapshot that is already stale by the time you act on it.

Here is the trap in its purest form. This looks like reasonable code and I have shipped a version of it more than once:

```php
// The classic race condition: SELECT, then if, then INSERT
$existing = User::where('email', $email)->first();

if ($existing === null) {
    // Two requests can BOTH pass this check before either inserts.
    User::create(['email' => $email, 'name' => $name]);
}
```

Two signup requests for the same email arrive together. Both run the `SELECT`, both find nothing, both pass the `if`, and both `INSERT`. You now have two accounts for one email. The `SELECT`-then-`if`-then-`INSERT` pattern is the single most common way this bug reaches production, and no amount of staring at it in a code review will reveal the flaw, because the flaw is in the timing, not the syntax.

## The shapes it takes in real web apps

Before the fixes, it helps to recognize the family. These are the ones I run into most.

**Double-submit duplicates.** A user double-clicks "Pay" or a mobile client retries a request that actually succeeded. You get two orders, two comments, two signups. This is the coupon story and the duplicate-account story rolled together.

**Lost updates on a balance.** Two requests both read a wallet balance of 100, both subtract 30, both write back 70. One of the two 30-unit deductions vanished into thin air. The classic double-spend.

**Over-redemption of a limited resource.** Coupons, tickets, inventory, rate limits. Everyone reads "5 left," everyone decrements, and you sell 12 tickets for 5 seats.

All three are the same underlying bug wearing different costumes. And all three have well-understood fixes.

## Fix 1: Let the database enforce uniqueness

If the rule is "one account per email," the cleanest place to enforce it is the database, not your PHP. A **unique constraint** makes the "does this already exist" question and the write into a single atomic operation that the database guarantees.

```sql
ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email);
```

Now the race disappears. Both requests can still try to insert, but the database will accept exactly one and reject the other with a duplicate-key error. Your job shifts from "prevent the duplicate" to "handle the rejection gracefully":

```php
try {
    User::create(['email' => $email, 'name' => $name]);
} catch (\Illuminate\Database\QueryException $e) {
    // 23000 is the SQLSTATE for an integrity constraint violation.
    if ($e->getCode() === '23000') {
        return $this->existingUserResponse($email);
    }
    throw $e;
}
```

This is my default for anything with a natural key. The constraint is a hard guarantee that survives buggy application code, a second app server, a background job, and the migration script somebody runs by hand at 2 a.m. Unique constraints are backed by an index, so it's worth understanding how those behave; I dug into that in [database indexing explained](/blog/database-indexing-explained).

## Fix 2: Make the write itself atomic

For counters and balances, don't read-then-write. Push the decision into a single `UPDATE` and let the database evaluate the condition while it holds the row.

```sql
-- Decrement only if there is stock left. One statement, no gap.
UPDATE coupons
SET remaining = remaining - 1
WHERE id = 42 AND remaining > 0;
```

The `remaining > 0` guard lives inside the same statement that does the decrement, so there is no window between the check and the act. Then you inspect how many rows were actually affected:

```php
$claimed = DB::update(
    'UPDATE coupons SET remaining = remaining - 1 WHERE id = ? AND remaining > 0',
    [$couponId]
);

if ($claimed === 0) {
    return response()->json(['error' => 'Coupon sold out'], 409);
}
```

If `$claimed` is 1, this request won the decrement. If it's 0, the coupon was gone. No overselling, no matter how many requests pile up at once. This is the fix I wish I'd known about during the coupon incident; it would have been a two-line change.

## Fix 3: Transactions with row locks

Sometimes the logic is too involved for one clever `UPDATE`. You need to read a row, run some business rules, maybe touch a couple of related tables, then write. For that, wrap the work in a transaction and take a **pessimistic lock** on the row so nobody else can read it until you're done.

```php
DB::transaction(function () use ($accountId, $amount) {
    // lockForUpdate() issues SELECT ... FOR UPDATE.
    // Other transactions block here until this one commits.
    $account = Account::where('id', $accountId)
        ->lockForUpdate()
        ->first();

    if ($account->balance < $amount) {
        throw new InsufficientFundsException();
    }

    $account->balance -= $amount;
    $account->save();
});
```

`lockForUpdate()` emits a `SELECT ... FOR UPDATE`. The first transaction to reach the row holds it, and any competing transaction waits at that line until the first commits. The lost-update problem is gone because the second reader never sees the stale 100; it waits and reads 70.

The tradeoff is throughput. Locked rows serialize access, so a hot row becomes a queue. Keep transactions short, lock as late as you can, and never do slow work like an HTTP call while holding a lock.

This is the pessimistic side of a broader tradeoff. The optimistic approach, where you don't lock but instead check a version column on write, suits low-contention paths better. I compared the two in [optimistic vs pessimistic locking](/blog/optimistic-vs-pessimistic-locking) if you're deciding between them.

## Fix 4: Atomic cache locks for cross-process critical sections

Row locks are perfect when the shared state is a database row. But sometimes the thing you're protecting isn't in the database yet, or you want to stop expensive work from running twice across your whole fleet. Say a webhook fires three times and each invocation would kick off the same report generation. An **atomic cache lock** gives you a named mutex backed by Redis:

```php
$lock = Cache::lock('generate-report:' . $reportId, 120);

if ($lock->get()) {
    try {
        $this->generateExpensiveReport($reportId);
    } finally {
        $lock->release();
    }
} else {
    // Someone else holds the lock; skip or requeue.
    Log::info('Report already generating', ['id' => $reportId]);
}
```

`Cache::lock()->get()` returns `true` only for the caller that acquired the lock, and it does so atomically, so two processes can't both think they won. If you'd rather wait than skip, use `->block(5)` to poll for up to five seconds before giving up. Always release in a `finally`, and always set a sensible timeout so a crashed worker doesn't hold the lock forever.

## Fix 5: Idempotency keys for the retry problem

Locks and constraints stop concurrent duplicates. But there's a subtler case: a client sends a request, the network hiccups before the response arrives, and the client retries. The first request may have already succeeded. This isn't strictly concurrency, yet it produces the same double-charge symptom.

The fix is an **idempotency key**. The client generates a unique token per logical operation and sends it as a header. The server records the token the first time it processes the request and returns the stored result for any repeat.

```php
$key = $request->header('Idempotency-Key');

// The unique index on idempotency_key turns a duplicate into a no-op.
$record = IdempotencyRecord::firstOrCreate(
    ['key' => $key],
    ['status' => 'processing']
);

if (! $record->wasRecentlyCreated) {
    return response()->json($record->response, $record->status_code);
}

// ... do the work once, then persist the response on $record.
```

Notice the unique constraint doing the heavy lifting again underneath. Idempotency keys are the standard way payment APIs like Stripe let you retry safely, and they pair naturally with everything above. I wrote a deeper walkthrough in [idempotency keys for safe API retries](/blog/idempotency-key-api-safe-retries).

## FAQ

### Isn't a race condition just a database problem?

No. The database is the most common place shared state lives, but the same bug appears with cache entries, files, in-memory counters, and external API calls. Anywhere two requests can observe and modify the same thing, you can race. The database just happens to also offer the strongest tools to fix it.

### Do I still need locks if I use database transactions?

Often, yes. A transaction gives you atomicity and rollback, but on the default isolation level it does not stop two transactions from reading the same row and then both writing. `lockForUpdate()` is what forces them to take turns. Transactions and row locks solve related but distinct parts of the problem.

### What's the difference between optimistic and pessimistic locking?

Pessimistic locking assumes conflict is likely and blocks other readers up front with something like `SELECT ... FOR UPDATE`. Optimistic locking assumes conflict is rare, lets everyone read freely, and checks a version number at write time, retrying if it changed. Pessimistic is simpler under high contention; optimistic scales better when collisions are uncommon.

### How do I even reproduce a race condition to test it?

Fire concurrent requests on purpose. A quick `ab -n 50 -c 50` against the endpoint, or a small script that opens many parallel connections, will usually surface a duplicate or a lost update within a few runs. If you can't reproduce it, add artificial latency between your check and your act in a test build; that widens the window and makes the bug reliable.

## Wrapping up

Race conditions feel mysterious because they hide from code review and pass every single-user test you throw at them. But they follow one rule: a check and an act that should have been one step got split into two, and concurrency drove a truck through the gap.

The fixes map cleanly onto the situation:

- **Natural uniqueness** (one account per email, one vote per user): a database unique constraint.
- **Counters and balances**: a single atomic `UPDATE ... WHERE` guard.
- **Multi-step business logic on a row**: a transaction with `lockForUpdate()`.
- **Expensive work across processes**: an atomic `Cache::lock()`.
- **Client retries**: idempotency keys.

Pick the lightest tool that closes the gap. Reach for a full pessimistic lock only when the cheaper atomic write can't express your rule. Had I known that during the coupon launch, the fix would have been one line instead of a very awkward finance reconciliation. Design for the concurrent case from the start, and the quiet-Tuesday case takes care of itself.