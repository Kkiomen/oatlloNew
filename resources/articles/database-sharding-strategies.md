---
name: "Database Sharding Strategies"
slug: database-sharding-strategies
short_description: "When to shard, how to pick a shard key, range vs hash vs directory sharding, consistent hashing, and the cross-shard pain nobody warns you about."
language: en
published_at: 2027-03-31 09:00:00
is_published: true
tags: [database, architecture, devops, scalability]
---

The first time I split a table across servers, I did it two years too early. We had a 40 GB `orders` table, a scary Grafana dashboard, and a manager asking about "scale." Six weeks later we had eight shards, a cross-shard reporting query that took 90 seconds, and roughly the same traffic we started with. A single beefier RDS instance would have carried us for another two years.

That's the honest opening for this topic: sharding is the thing you reach for after everything cheaper has failed. This post is about how it actually works — the three ways to split data, why the shard key is the one decision you can't easily undo, how consistent hashing keeps rebalancing sane, and where cross-shard queries quietly ruin your week. But the most useful section might be the last one: the signs you shouldn't shard yet.

## Sharding vs partitioning vs replication (they solve different problems)

These three get mixed up constantly, and picking the wrong one wastes months.

**Replication** copies the *same* data to multiple machines. A primary takes writes, replicas serve reads. It buys you read throughput and failover. It does nothing for write load or for a dataset too big to fit on one disk, because every replica holds the full copy.

**Partitioning** (in the Postgres/MySQL sense) splits one table into pieces that still live inside *one* database server. `PARTITION BY RANGE (created_at)` lets the engine prune to the relevant partition and makes dropping old data an instant `DROP PARTITION` instead of a slow `DELETE`. It speeds up planning and makes data lifecycle cheap. It does not spread load across machines — the CPU and disk are still one box.

**Sharding** splits data across *separate* database servers, each holding a disjoint subset. Shard 1 has some customers, shard 2 has others. This is the only one of the three that scales writes and total dataset size horizontally. It's also, by a wide margin, the most expensive to operate.

The order you should exhaust them: replication (read replicas) and partitioning first, sharding last. If your problem is "reads are slow," you almost certainly don't need sharding.

## The shard key is the decision you live with

Everything downstream hangs off one choice: **the shard key** — the column whose value decides which shard a row lands on. Get it right and most queries hit exactly one shard. Get it wrong and every query fans out to all of them, which defeats the entire point.

A good shard key has three properties:

- **High cardinality** — enough distinct values to spread data evenly. `user_id` is great; `status` (with five values) is useless.
- **Even distribution** — no single value holding a disproportionate slice. Sharding by `country` sounds clean until 60% of your users are in one country and that shard melts.
- **It's in your hot queries** — the key should appear in the `WHERE` clause of the queries you run most. If 90% of reads filter by `tenant_id`, shard on `tenant_id` and those reads touch one shard.

Here's the trap I've watched teams fall into: they shard `orders` by `order_id` because it's the primary key and obviously unique. Distribution is perfect. But nobody looks up orders by `order_id` alone — they query "all orders for customer X." Now every customer page scatters across all shards. The right key was `customer_id`, even though it distributes slightly less evenly.

There's a real tension here. The key that gives perfect distribution is rarely the key your queries filter on. You're choosing which pain you'd rather have: a mild hot-spot, or scatter-gather on your most common query. I'll take the mild hot-spot almost every time — you can smooth that out, but you can't un-scatter a query pattern baked into a hundred endpoints.

## The three ways to map a key to a shard

Once you've picked the key, you need a function that turns a key value into a shard. Three families of them, each trading away something different.

### Range-based

Assign contiguous ranges to shards. IDs 1–1,000,000 on shard A, 1,000,001–2,000,000 on shard B, and so on.

```
shard A:  customer_id  1 .. 1,000,000
shard B:  customer_id  1,000,001 .. 2,000,000
shard C:  customer_id  2,000,001 .. 3,000,000
```

**Upside:** range queries stay local. "Customers created last month" (sequential IDs) hits one shard, and adding a new shard for the next range is trivial.

**Downside:** with auto-increment IDs, all new writes pile onto the newest shard while the old ones go cold. You've built a rolling hot-spot. Range sharding on a monotonic key gives you the worst write distribution of the three.

### Hash-based

Hash the key and take a modulo of the shard count.

```php
function shardFor(int $customerId, int $shardCount): int
{
    // crc32 is fine here; you only need even spread, not crypto strength.
    return crc32((string) $customerId) % $shardCount;
}
```

**Upside:** distribution is excellent. Sequential IDs scatter uniformly, so writes spread evenly across shards. This is the default choice for most OLTP workloads.

**Downside:** range queries are dead — consecutive IDs land on different shards, so "last month's customers" fans out to all of them. And the modulo is a landmine: change `shardCount` from 4 to 5 and `crc32(id) % N` changes for *almost every row*. You'd have to move nearly the entire dataset to rebalance. That single line is why naive hash sharding is so painful to grow — and why consistent hashing exists (next section).

### Directory-based

Keep an explicit lookup table mapping each key (or key bucket) to a shard.

```sql
CREATE TABLE shard_map (
    tenant_id   BIGINT PRIMARY KEY,
    shard_id    SMALLINT NOT NULL
);
```

To route a query, you read the map first, then hit the named shard.

**Upside:** total control. You can move one noisy tenant to its own shard by updating a single row, place big customers deliberately, and change the mapping without a formula rewrite. For multi-tenant SaaS this is often the right answer.

**Downside:** the directory is now on the critical path of every query and a single point of failure. You'll cache it hard (it changes rarely), but you have to keep that cache correct, and a stale entry routes a write to the wrong shard.

| Strategy | Distribution | Range queries | Rebalancing | Best for |
|---|---|---|---|---|
| Range | Uneven with monotonic keys | Fast (local) | Easy (add a range) | Time-series, sequential access |
| Hash | Even | Slow (scatter) | Painful with plain modulo | General OLTP by ID |
| Directory | Fully controllable | Depends | Easy (edit the map) | Multi-tenant SaaS |

## Consistent hashing: rebalancing without moving everything

The `% N` problem is worth solving properly because resharding a live system is where the real risk lives. **Consistent hashing** is the standard fix, and the idea is simpler than its reputation.

Instead of mapping keys directly to shards, map both keys *and* shards onto the same circular hash space (say 0 to 2³²). A key belongs to the first shard you meet walking clockwise from the key's position on the ring.

When you add a shard, it slots in at one point on the ring and only steals the keys between it and the previous shard — roughly `1/N` of the data moves, not all of it. Remove a shard and its keys fall to the next one clockwise. Everything else stays put.

```php
class HashRing
{
    private array $ring = []; // hash position => shard name
    private array $sortedKeys = [];

    public function __construct(array $shards, int $virtualNodes = 150)
    {
        foreach ($shards as $shard) {
            // Virtual nodes: each shard gets many points on the ring
            // so the load evens out instead of clumping.
            for ($i = 0; $i < $virtualNodes; $i++) {
                $this->ring[crc32("{$shard}#{$i}")] = $shard;
            }
        }
        $this->sortedKeys = array_keys($this->ring);
        sort($this->sortedKeys);
    }

    public function shardFor(string $key): string
    {
        $hash = crc32($key);
        foreach ($this->sortedKeys as $point) {
            if ($hash <= $point) {
                return $this->ring[$point];
            }
        }
        return $this->ring[$this->sortedKeys[0]]; // wrapped past the end
    }
}
```

The **virtual nodes** part matters. With one point per shard, three shards would carve the ring into three uneven arcs and load would be lopsided. Giving each shard ~150 points averages those arcs out, and it lets you weight a bigger box by handing it more virtual nodes. This is the same trick Cassandra, DynamoDB, and most Redis-cluster setups use under the hood.

One caveat people skip: consistent hashing tells you which keys *should* move, but you still have to physically copy them while the system serves traffic. That migration is the hard part — the ring math is the easy 20%.

## Cross-shard queries are where the joy leaks out

Here's what the tutorials undersell. Once data lives on separate servers, `JOIN` across shards is gone. The database can't join tables that live in different processes. Any query that needs data from more than one shard becomes your application's problem.

Say you want the 20 most recent orders across all customers. On one database:

```sql
SELECT * FROM orders ORDER BY created_at DESC LIMIT 20;
```

Sharded, that single statement doesn't exist. You have to ask every shard for its top 20, then merge in application code:

```php
$partials = [];
foreach ($shards as $shard) {
    $partials[] = $shard->select(
        'SELECT * FROM orders ORDER BY created_at DESC LIMIT 20'
    );
}

// Merge all partial results, re-sort, then take the global top 20.
$merged = array_merge(...$partials);
usort($merged, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);
$top20 = array_slice($merged, 0, 20);
```

Note you must fetch `LIMIT 20` from *each* shard, not `LIMIT 20 / shardCount` — the global top 20 could all live on one shard. That's called **scatter-gather**, and it gets ugly fast: `COUNT`, `SUM`, `GROUP BY`, and pagination with `OFFSET` all need the same fan-out-then-merge treatment. `OFFSET 10000 LIMIT 20` is especially brutal — you'd have to pull 10,020 rows from every shard to find the right window.

The practical answers, none free:

- **Denormalize** so the common query needs only one shard. Store a copy of what you need next to the key you shard on.
- **Keep aggregates elsewhere** — a separate analytics store (a data warehouse, or read replicas piped into one place) so reporting never fans out across your OLTP shards.
- **Design queries around the shard key** so 95% of them touch one shard, and accept that the other 5% are slow and rare.

And cross-shard transactions? A write that must atomically touch two shards needs two-phase commit or a saga, both of which are their own multi-week projects. The cleanest move is to arrange your shard key so related rows land on the *same* shard — for example, sharding both `customers` and `orders` by `customer_id` keeps a customer's orders local, and the join stays possible.

## Don't shard yet — try these first

Most teams that think they need sharding don't. Before you split anything, walk this list.

1. **Vertical scaling.** Boring, and it works. Modern managed instances go to hundreds of GB of RAM and dozens of cores. Doubling instance size is a maintenance window, not a re-architecture. Buy time with money before you spend it with engineering.
2. **Read replicas.** If reads are the bottleneck — and they usually are — point them at replicas and keep writes on the primary. This alone carries most read-heavy apps for years.
3. **Indexes and query fixes.** I've seen a "we need to shard" panic evaporate after adding one composite index. Check `EXPLAIN` before you check your architecture diagram.
4. **Caching.** A Redis layer in front of hot reads removes load the database never needed to feel.
5. **Partitioning + archiving.** If the pain is a giant table, partition it by time and archive cold rows out. Often the working set that's actually hot is small.

Sharding earns its place only when you're genuinely write-bound or the dataset won't fit on the biggest single machine you can reasonably buy — and you've confirmed the cheaper options are exhausted, not skipped. The cost isn't the setup. It's that every feature afterward is built on a database that can't do a simple join, and every engineer you hire has to learn that the hard way.

If you're not sure whether you're there yet, you're not there yet. Sharding you can't reverse over a weekend; a bigger instance you can.

## FAQ

**When should I actually shard instead of scaling up?**
When a single primary can't absorb your write throughput, or your dataset exceeds what your largest affordable instance can hold, *and* replicas, caching, indexing and partitioning haven't closed the gap. If the bottleneck is reads, it's replicas — not shards.

**Can I change the shard key later?**
Effectively no, not without rebuilding. The shard key determines where every row physically lives, so changing it means redistributing the entire dataset and rewriting the routing logic. Treat it like a schema decision you'll live with for years. Spend real time modeling your query patterns before you commit.

**How many shards should I start with?**
More logical shards than physical servers, so you can move shards between machines without re-hashing. A common pattern is 32 or 64 logical shards mapped onto a handful of servers early on — you grow by moving shards to new machines, not by changing the shard count and re-hashing everything.

**Does Postgres or MySQL shard automatically?**
Not natively in a way that hides the seams. You either shard in your application layer, or adopt a system built for it — Citus for Postgres, or Vitess for MySQL (the same tech behind YouTube and Slack). Those handle a lot of the routing and cross-shard mechanics, but the shard-key decision is still yours to make and still the one that matters most.

**What's the difference between a logical and a physical shard?**
A physical shard is a database server. A logical shard is a bucket of data that can move between servers. Keeping the logical count high and fixed while the physical count grows is the trick that makes future rebalancing bearable — you relocate buckets instead of re-hashing keys.
