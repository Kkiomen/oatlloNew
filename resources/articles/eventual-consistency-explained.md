---
name: "Eventual Consistency Explained"
slug: eventual-consistency-explained
short_description: "What eventual consistency actually means, the read-your-writes and stale-read bugs it causes, and how to handle it in a Laravel app with read replicas and caches."
language: en
published_at: 2027-05-05 09:00:00
is_published: true
tags: [architecture, database, laravel, devops]
---

A user updated their profile name, hit save, saw the green toast, and then the page reloaded with the old name. They filed a bug. There was no bug in the write path. The write had committed to the primary database, and the reload had been served by a read replica that was about a second behind. That gap between "the write happened" and "everybody can see it" has a name, and once you learn to spot it you start seeing it everywhere in your stack.

This article is about that gap: what eventual consistency really is, the specific user-visible failures it produces, the handful of techniques that fix each one, and how to explain the trade-off to a product owner who just wants the save button to work.

## Strong vs eventual consistency, without the hand-waving

**Strong consistency** means: once a write succeeds, every subsequent read returns that write. There is a single, agreed-upon "now." A plain single-node MySQL or Postgres gives you this for free, which is why most developers never think about it.

**Eventual consistency** means: if you stop writing, all copies of the data will *converge* to the same value — eventually. In between, different readers can see different versions. Nothing is broken. The system is doing exactly what it was designed to do; it just traded "everyone sees the latest value instantly" for something else — usually availability, latency, or the ability to scale reads horizontally.

The word "eventually" does a lot of quiet work here. It is not a promise about *when*. On a healthy read replica the lag is single-digit milliseconds. Under a heavy import job or a network hiccup it can be seconds or, in a bad afternoon, minutes. Your code has to be correct across that whole range, not just the happy path where lag is basically zero.

## Where it shows up (probably in your app right now)

You don't need a globally distributed database to meet eventual consistency. It's already in a boring three-tier Laravel app:

- **Read replicas.** You send writes to the primary and spread reads across replicas. Replication is asynchronous, so a replica always trails the primary by some amount.
- **Caches.** The moment you cache a query result, the cache and the database are two copies that can disagree until the cache is invalidated or expires. A 60-second TTL is a 60-second consistency window you chose on purpose.
- **Async projections / CQRS.** When reads and writes live in separate models, a write updates the write side and a job later updates the read side. The read model is stale until that job runs.
- **Search indexes.** Elasticsearch, Meilisearch, Algolia — you write to the database and reindex asynchronously. The index lags.
- **Distributed systems.** Multi-region databases, event-driven microservices, CDNs. Here it's not an implementation detail; it's the whole design.

If your app has any of these — and most non-trivial apps have three or four — you are already running an eventually consistent system. The only question is whether you've accounted for it.

## The two anomalies that actually bite

Most of the pain reduces to two named read anomalies. Learn to recognize them by their symptom, because that's how they show up in a bug tracker.

### Read-your-own-writes

A user makes a change and then immediately reads it back, and their own change is missing. This is the profile-name story from the intro. It's the single most common and most confusing eventual-consistency bug, because from the user's seat it looks like the save silently failed.

The mechanism: the write goes to the primary; the follow-up read gets routed to a replica that hasn't caught up. The user sees a stale value that happens to be *their own* stale value, which is why it feels like a lie.

### Monotonic reads

A user sees a value, then on a later read sees an *older* value — data appears to travel backward in time. This happens when consecutive reads hit different replicas that are lagging by different amounts. Refresh the page, see the new comment. Refresh again, it's gone. Refresh a third time, it's back. Nothing was deleted. The requests just bounced between a fresh replica and a stale one.

There are more formal guarantees in the literature (consistent prefix, bounded staleness, causal consistency), but read-your-writes and monotonic reads are the two that generate support tickets.

## Fixing each anomaly

The good news: these have well-worn, cheap fixes. You don't rearchitect; you route reads more deliberately.

**Read from the primary right after a write.** For the specific request where a user just changed something and needs to see the result, bypass the replicas and read the primary. This is the direct cure for read-your-own-writes. Laravel gives you a `sticky` option on the connection that does exactly this: after any write in a request cycle, subsequent reads in that same cycle go to the primary.

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [env('DB_READ_HOST', '10.0.0.2')],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', '10.0.0.1')],
    ],
    'sticky'   => true, // reads-after-writes hit the primary for the rest of the request
    'driver'   => 'mysql',
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
],
```

`sticky` only covers a single request. If your flow is "POST to save, then redirect to a GET that renders the result," the GET is a *new* request and `sticky` won't help it — it never saw the write. That's the trap most people hit. For a redirect-after-save flow, you either force that specific read onto the write connection, or you carry the new value forward yourself:

```php
public function update(Request $request, User $user)
{
    $user->update($request->only('name'));

    // Redirect target reads from a replica that may lag. Two honest options:

    // 1) Force this particular read onto the primary.
    $fresh = User::on('mysql')->getConnection()->transaction(
        fn () => User::query()->useWritePdo()->find($user->id)
    );

    // 2) Or don't re-read at all — you already know what you just wrote.
    return redirect()->route('profile')->with('user', $fresh);
}
```

`useWritePdo()` is the surgical version of `sticky`: it pins one query to the write connection. Reach for it when you want *this* read fresh and are happy to leave everything else on the replicas.

**Pin a user to one replica.** To kill monotonic-read flip-flopping, route a given user's reads to the same replica (sticky sessions / consistent hashing on user id). They might see slightly stale data, but it never goes backward, which is far less alarming than data that time-travels.

**Version your data.** Attach a version number or an `updated_at` timestamp to records and refuse to render something older than what the client already saw. If the client holds version 7 and a replica hands back version 5, you know to retry against the primary instead of showing the stale copy.

**Make writes idempotent.** In async and distributed setups, a message can be delivered more than once. If applying it twice corrupts state, lag turns into duplicate charges. An idempotency key (a unique token per logical operation, checked before applying) means a replayed message is a no-op instead of a second order.

**Use compensating actions instead of pretending you can roll back.** Across service boundaries there's no distributed transaction to undo a half-finished flow. You apply a *reversing* operation — refund the payment, release the reserved stock — to bring the system back to a consistent state. This is the core of the [saga pattern](/saga-pattern-distributed-transactions), which is worth reading if your writes span multiple services.

## A concrete Laravel example: replica lag through a cache

Here's a bug that looks like magic until you name it. A `Product` has a stock count. You cache it, you read from replicas, and you write to the primary.

```php
public function show(int $id): View
{
    // Reads from a replica AND caches for 5 minutes.
    $product = Cache::remember("product:$id", 300, function () use ($id) {
        return Product::query()->findOrFail($id); // goes to a read replica
    });

    return view('product.show', compact('product'));
}

public function purchase(int $id): RedirectResponse
{
    DB::transaction(function () use ($id) {
        $product = Product::query()->useWritePdo()->lockForUpdate()->findOrFail($id);
        $product->decrement('stock'); // writes to primary
    });

    // Without this line, the cached copy survives for up to 5 minutes.
    Cache::forget("product:$id");

    return redirect()->route('product.show', $id);
}
```

You now have *two* consistency windows stacked on top of each other. Even after `Cache::forget`, the next `show()` request repopulates the cache from a replica — and if that replica is 800ms behind the purchase you just committed, you've cached the *pre-purchase* stock count for the next 5 minutes. The fix is to invalidate the cache and repopulate from a source you trust for that moment:

```php
public function purchase(int $id): RedirectResponse
{
    $stock = DB::transaction(function () use ($id) {
        $product = Product::query()->useWritePdo()->lockForUpdate()->findOrFail($id);
        $product->decrement('stock');
        return $product->stock;
    });

    // Prime the cache with the value we KNOW is current, not a replica re-read.
    Cache::put("product:$id", Product::query()->useWritePdo()->find($id), 300);

    return redirect()->route('product.show', $id);
}
```

The lesson isn't "caches are dangerous." It's that every copy you add — replica, cache, index — is another clock that can disagree, and you have to decide, per read, which clock you trust. When the separation between the write path and the read path gets this involved, you're most of the way to [splitting reads and writes into separate models](/cqrs-pattern-when-to-separate-reads-and-writes) on purpose.

## Why anyone chooses this on purpose

If eventual consistency causes bugs, why not always be strongly consistent? Because strong consistency has a price, and two theorems tell you what it is.

**CAP** says that when a network partition happens (nodes can't talk to each other), a distributed system must choose between staying **Consistent** (reject requests it can't confirm) and staying **Available** (answer with possibly-stale data). You can't have both *during a partition*. Pick "available" and you've picked eventual consistency for the duration of the fault.

**PACELC** is the more useful, less quoted follow-up: it points out that even when there's no partition (the "Else" case), you still trade between **Latency** and **Consistency**. Making every read confirm with the primary across regions costs you round-trips. Serving from a nearby replica is faster but staler. Most of the time your system isn't partitioned, so this everyday latency-vs-consistency trade is the one you actually live with — and it's the one CAP-only conversations miss.

So the trade is deliberate: you accept a small, bounded window of staleness to get read scaling, lower latency, and an app that stays up when a replica or a region wobbles. For a product catalog, a stock count that's 500ms stale is completely fine. For the balance shown on a bank transfer confirmation, it is not. **Match the consistency guarantee to what the specific piece of data actually needs** — this is a per-feature decision, not one global switch.

## How to explain it to a product owner

Skip CAP. Try this: "To keep the site fast and online, we keep several copies of the data. When someone saves a change, it lands on the main copy instantly, and the other copies catch up a moment later — usually well under a second. For the person who made the change, we always show the main copy, so *they* see it immediately. Another user might see the old value for a heartbeat. For a product listing that's invisible. For anything where a second of delay would confuse or cost someone, we read the main copy directly and take the small speed hit."

That framing gives them the one lever that matters: which data needs to be instant everywhere (and will be slower/costlier), and which data can lag for a second (and will be fast and resilient). That's a product call, and now they can make it.

## Common pitfalls

- **Assuming `sticky` covers the redirect.** It's per-request. A save that redirects to a fresh GET request is exactly where read-your-writes returns.
- **Forgetting the cache is a second replica.** Invalidating on write isn't enough if the repopulating read comes from a lagging replica.
- **Testing only on localhost.** One database, zero lag — the anomaly can't reproduce. It only appears under real replication, which is why it reaches production.
- **Retrying non-idempotent writes.** A timeout doesn't tell you whether the write landed. Retry a non-idempotent operation and you get duplicates.
- **Treating everything as needing strong consistency.** Then you've paid for a distributed system and thrown away the benefit that justified it.

## FAQ

**How much replication lag is normal?**
On healthy hardware with modest write volume, single-digit milliseconds. It spikes during large batch writes, schema migrations, long-running transactions on the primary, or network congestion. Monitor it (`SHOW REPLICA STATUS` on MySQL, `pg_stat_replication` on Postgres) and alert on it — don't guess.

**Is eventual consistency the same as a race condition?**
No, though they feel similar. A race condition is a bug: unsynchronized access producing an unintended result. Eventual consistency is a deliberate design property with a defined convergence guarantee. The fixes differ too — races need locking or ordering; consistency anomalies need routing and versioning.

**Do I need read replicas to hit this?**
No. A single database plus any cache already gives you two copies that can disagree. Async jobs, queues, and search indexes do the same. Replicas are just the most obvious source.

**Can I get strong consistency in a distributed database?**
Yes — systems like Google Spanner and CockroachDB offer strong consistency across nodes, paying for it in coordination latency (Spanner leans on synchronized clocks to do it). The point of PACELC stands: you're buying consistency with latency. Whether that's worth it depends on the data.

## The takeaway

Eventual consistency isn't a failure mode you patch out; it's the shape of any system with more than one copy of the data — and that's nearly all of them. The skill is deciding, per read, whether "eventually" is good enough or whether this particular value has to be exact right now. Start by auditing your app for the copies you already have — replicas, caches, indexes — and for each one, ask which reads would break if that copy were a second behind. Those are the reads to pin to the primary. Everything else can lag, and should, because that lag is what's buying you speed and uptime.
