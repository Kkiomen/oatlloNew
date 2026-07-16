---
name: "Scaling Reads with Database Replicas"
slug: database-read-replicas-scaling
short_description: "How read/write splitting with database replicas works, the stale-read lag trap, and how Laravel's sticky connection config saves you from it."
language: en
published_at: 2027-05-07 09:00:00
is_published: true
tags: [database, laravel, php, architecture, devops]
---

The first time replicas bit me, a user updated their profile, the page reloaded, and the old name was still there. Refresh again and it was correct. No error, no exception, nothing in the logs. The write went to the primary, the reload read from a replica that hadn't caught up yet, and for about 40 milliseconds the database lied. That gap has a name — replication lag — and it's the thing nobody warns you about when they tell you to "just add a read replica."

Here's the whole arc: how the topology fits together, the lag trap you'll hit within a week of turning replicas on, how Laravel decides which host a query lands on, when to yank a read back onto the primary, and the point where replication runs out of road and sharding takes over.

## Why split reads from writes at all

Most web apps are read-heavy. A typical CRUD product does something like 90% SELECTs and 10% writes — think product pages, dashboards, feeds. One database server handles both, and reads are what saturate it first: they're frequent, they fan out, and a slow report query can starve the connection pool that your writes also need.

The idea is simple. Keep one **primary** (sometimes called the master or leader) that accepts every write. Attach one or more **replicas** (read-only copies) that stream changes from the primary and serve SELECTs. Now your read traffic scales horizontally — add another replica, get more read throughput — while writes stay funneled through a single source of truth.

```
                 writes
     App ───────────────────────►  Primary
      │                              │
      │  reads                       │ async replication
      ├──────────────►  Replica 1 ◄──┤
      └──────────────►  Replica 2 ◄──┘
```

That single arrow from primary to replica is where all the trouble lives, so let's go there.

## Replication is asynchronous, and that changes everything

By default, MySQL and PostgreSQL replicate **asynchronously**. The primary commits your write, tells the client "done," and *then* ships the change to replicas over the network. The replica applies it whenever it gets around to it — usually milliseconds later, sometimes seconds, occasionally minutes if it's overloaded or the write was huge.

So there is a window where the primary knows about a row the replica doesn't. Read from the replica during that window and you get stale data. This isn't a bug in your database. It's the deal you signed when you chose async replication, and it's the correct default for most systems because the alternative — synchronous replication, where the primary waits for a replica to confirm before returning — puts network round-trips on your write path and tanks write latency.

The anomaly that catches everyone is **read-your-own-writes**: a user performs an action and immediately doesn't see the result of that action. Post a comment, it's not there. Change a setting, the form shows the old value. Delete an item, it reappears on redirect. Every one of those is a write to the primary followed by a read from a not-yet-caught-up replica, in the same request or the very next one.

If you want the deeper mental model of *why* distributed reads are allowed to disagree for a while, [eventual consistency explained](/eventual-consistency-explained) covers the underlying guarantee. The short version: replicas promise to converge, not to be current.

## How Laravel routes reads and writes

Laravel has this built in. In `config/database.php` you give a connection separate `read` and `write` hosts:

```php
'mysql' => [
    'driver' => 'mysql',
    'read' => [
        'host' => [
            '10.0.0.11', // replica 1
            '10.0.0.12', // replica 2
        ],
    ],
    'write' => [
        'host' => ['10.0.0.10'], // primary
    ],
    'sticky' => true,
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    // charset, collation, prefix, etc. inherited by both
],
```

When both keys are present, Laravel picks a **read** host for SELECTs and the **write** host for INSERT/UPDATE/DELETE and anything inside a transaction. If you list multiple read hosts, it picks one at random per request — a poor man's load balancer. Options like `username` and `password` outside the `read`/`write` arrays are shared; put anything host-specific inside.

Under the hood this is `Illuminate\Database\Connection` maintaining two PDO instances and choosing one based on the query type. You don't route anything manually — Eloquent and the query builder do it for you. Which is exactly why the lag trap is so easy to fall into: the routing is invisible, so the staleness is invisible until a user complains.

### The `sticky` option is the fix you'll reach for first

See `'sticky' => true` above. That one line solves the read-your-own-writes problem for the common case. When sticky is on and a write happens during the current request, Laravel **pins all subsequent reads in that same request to the write connection** (the primary). The reasoning: if you just wrote, you're the one most likely to read your own change back, and the primary is guaranteed current.

```php
// sticky = true

$user->update(['name' => 'Ada']); // goes to primary, marks connection "written"

$fresh = User::find($user->id);    // reads from PRIMARY, not a replica
// $fresh->name === 'Ada'  ✅ guaranteed
```

Without sticky, that `find()` could hit a lagging replica and return the old name. Sticky is per-request and per-connection — it doesn't leak into the *next* request, and it only kicks in after an actual write. It's cheap, it's correct for the "user edits then views" flow, and I turn it on by default. Just know its boundary: it does nothing for a *different* request (a webhook, a queued job, another user) reading data your request just wrote. Sticky protects the writer, not bystanders.

## When to force a query onto the primary

Sticky covers most cases, but sometimes you need to read fresh data *without* having written in the same request. Laravel exposes the connection directly:

```php
use Illuminate\Support\Facades\DB;

// Force this specific read to the primary
$balance = DB::connection('mysql')
    ->table('accounts')
    ->useWritePdo()          // <-- read from primary
    ->where('id', $id)
    ->value('balance');
```

For Eloquent, wrap the read in a transaction (transactions always use the write connection) or drop to `onWriteConnection()`:

```php
$order = Order::query()
    ->onWriteConnection()   // routes this query to the primary
    ->find($orderId);
```

Reach for the primary deliberately when the read *must* be current and can't be wrong: an account balance right after a debit, an inventory count before you sell the last unit, an idempotency check that decides whether to re-run a payment, a "did this job already process this record" guard. Everything else — list pages, search results, analytics, most dashboards — can tolerate a few hundred milliseconds of staleness and should stay on replicas. If you force everything to the primary, congratulations, you've built a single-database system with extra network hops.

## Replication is not sharding — they solve different problems

People conflate these constantly, so let's be blunt about it.

| | Replication (read replicas) | Sharding |
|---|---|---|
| Copies of the data | Full copy on every node | Each node holds a *slice* |
| Scales | Read throughput | Reads **and** writes + storage |
| Write path | Still one primary | Many primaries, one per shard |
| Main cost | Stale reads (lag) | Query routing, cross-shard joins, rebalancing |
| Reach for it when | Reads are the bottleneck | One machine can't hold the data or absorb the writes |

Replication gives every node the whole dataset, so it multiplies **read** capacity but does nothing for **write** capacity — every write still goes through the single primary. When your writes saturate that one primary, or the dataset no longer fits on one box, replicas won't save you. That's the wall where [database sharding strategies](/database-sharding-strategies) become the conversation: split the data itself across independent primaries.

Order matters. Add read replicas first — they're simpler, non-destructive, and buy you a long runway. Shard only when you've proven the write path or storage is the real limit, because sharding is a one-way door that complicates every query you write afterward.

## Monitoring lag before it monitors you

You cannot manage replication without watching lag, because a replica that falls far enough behind is worse than no replica — it serves confidently wrong data. Check it directly.

On MySQL:

```sql
SHOW REPLICA STATUS\G
-- look at: Seconds_Behind_Source
--          Replica_IO_Running / Replica_SQL_Running (both must be Yes)
```

On PostgreSQL, measure the byte distance between what the primary has written and what the replica has replayed:

```sql
SELECT
    client_addr,
    pg_wal_lsn_diff(sent_lsn, replay_lsn) AS bytes_behind
FROM pg_stat_replication;
```

Alert on the numbers, not on vibes. A practical baseline: warn when a replica passes ~1 second behind, page someone at ~10 seconds, and have your app *stop routing reads* to any replica beyond a threshold. Managed services help here — AWS RDS exposes `ReplicaLag` as a CloudWatch metric, so you can wire an alarm without writing the SQL yourself. The failure mode you're guarding against isn't "replica is down" (that's obvious and load balancers handle it); it's "replica is *up* but 40 seconds stale," which looks healthy and quietly poisons reads.

Common causes of a lag spike, roughly in order of how often I've seen them: a long-running transaction on the primary blocking replay, a bulk write (a big migration, a mass `UPDATE`), the replica being underpowered relative to the primary, or a single slow replica-side query holding up the serial apply thread.

## Failover, briefly

If the primary dies, someone has to promote a replica to be the new primary. Doing that safely by hand is fiddly — you don't want two nodes both thinking they're primary (split-brain), and you don't want to promote a replica that was 30 seconds behind and lose those writes. This is why people run an orchestration layer: **Orchestrator** or **MHA** for MySQL, **Patroni** for PostgreSQL, or a managed setup like RDS Multi-AZ that fails over for you and re-points a DNS endpoint.

The app-side takeaway is smaller than it sounds. Point your `write` host at a stable endpoint (a DNS name or proxy like ProxySQL/PgBouncer), not a hard-coded replica IP, so that when failover swaps the underlying box your config doesn't need a deploy. And expect a few seconds of write errors during promotion — wrap critical writes in a retry, and let non-critical ones fail loudly rather than silently.

## FAQ

**Does adding a read replica speed up writes?**
No. Every write still goes to the single primary, and replication actually adds a small amount of work on the primary to ship changes out. Replicas scale reads only. If writes are your bottleneck, you need sharding or a faster primary, not more replicas.

**Why do I see old data right after saving in Laravel?**
Your read hit a replica that hadn't received the write yet. Set `'sticky' => true` on the connection so reads after a write in the same request go to the primary. For reads in a *different* request that must be current, use `onWriteConnection()` or wrap them in a transaction.

**Is `sticky => true` enough to guarantee consistency?**
Only within a single request that performed the write. It won't help a queued job, a webhook, or another user reading data your request just wrote — those may still hit a lagging replica. For those, force the primary explicitly or accept the staleness.

**How much replication lag is normal?**
On a healthy setup, single-digit to low-double-digit milliseconds. Momentary spikes during bulk writes are normal. Sustained lag of seconds means the replica can't keep up — investigate long transactions, big writes, or an undersized replica before it starts serving badly stale reads.

## Where to draw the line

Read replicas are one of the highest-leverage moves you can make on a read-heavy app: cheap to add, non-destructive, and they scale reads out almost linearly. The tax is consistency, and it's a real tax — the moment you split reads and writes, "the database" stops being a single source of truth you can read from blindly.

So do two things before you flip it on. Turn on `sticky` so writers see their own writes. And walk through your app asking, query by query, "if this read is 500ms stale, does anything break?" The handful where the answer is yes get forced to the primary; everything else rides the replicas. Get that sorting right and lag becomes a metric you watch, not an incident you explain.
