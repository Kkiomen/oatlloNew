---
name: "Database Indexing Explained for Application Developers"
slug: database-indexing-explained
short_description: "Database indexing explained for app developers: how B-tree indexes work, composite and covering indexes, the leftmost-prefix rule, and reading EXPLAIN."
language: en
published_at: 2026-08-24 09:00:00
is_published: true
tags: [database, mysql, postgresql, performance, sql]
---

Most of the slow endpoints I have ever been handed came down to the same thing: a query scanning a table it had no business scanning. So here is **database indexing explained** the way I wish someone had explained it to me early on — not as a checklist of "add an index and it goes faster," but as a set of trade-offs you can reason about. An index makes reads faster and writes a little slower, and once you understand *why*, you stop guessing and start knowing which columns to index.

This is written for people who build applications on top of a relational database (MySQL/InnoDB, PostgreSQL) rather than for DBAs. No index internals PhD required.

## What an index actually is (the B-tree)

An index is a separate data structure that stores a copy of one or more columns, kept in sorted order, with a pointer back to the full row. That is the whole idea. Instead of reading every row to find the ones you want, the database walks a sorted structure and jumps straight to them.

The structure is almost always a **B-tree** (technically a B+tree in most engines). Picture a shallow, wide tree:

- The **root** and **internal nodes** hold ranges and point downward.
- The **leaf nodes** hold the actual indexed values in sorted order.
- Finding a value means walking from root to leaf, a handful of hops even for millions of rows.

That "handful of hops" is the point. A table with 10 million rows has a B-tree only three or four levels deep. A full table scan reads 10 million rows; an index lookup reads maybe four pages. This is why an index turns an `O(n)` scan into something close to `O(log n)`.

Two things fall out of the B-tree being *sorted*:

- **Range queries are cheap.** `WHERE created_at > '2026-01-01'` finds the start point in the tree, then reads leaves sequentially.
- **`ORDER BY` can be free.** If you order by an indexed column, the data is already sorted, no separate sort step.

> A note on primary keys in InnoDB: the table itself *is* a B-tree keyed on the primary key (a "clustered index"). Secondary indexes store the primary key as their row pointer. This is why a short, monotonic primary key (like a `BIGINT` auto-increment) matters — every secondary index carries a copy of it. PostgreSQL differs here: its tables are heaps and all indexes are secondary, pointing at a physical row location.

## When to add an index

You do not index everything. You index for the queries you actually run. Concretely, look at:

- Columns in **`WHERE`** clauses that filter a lot of rows out.
- Columns used in **`JOIN`** conditions (foreign keys are a classic miss; many ORMs create the column but not the index).
- Columns in **`ORDER BY`** / **`GROUP BY`** on hot paths.
- Columns behind **`UNIQUE`** constraints (you get the index for free, and it enforces correctness).

The real signal is **cardinality** — how many distinct values a column has. High-cardinality columns (email, user_id, order UUID) are excellent index candidates because a lookup narrows down to a tiny slice. Low-cardinality columns (a `status` with three values, a boolean `is_active`) are usually poor standalone indexes: if `WHERE is_active = 1` matches 80% of the table, the planner will correctly ignore the index and scan anyway, because reading the index *plus* jumping back to the table is more expensive than just reading the table.

Do not index a table that has a few hundred rows and never grows. The scan is already fast, and the index is pure overhead.

## Composite indexes and the leftmost-prefix rule

A **composite index** covers several columns in a defined order, and the order is everything. This is the rule people get wrong most often, so it is worth being precise.

```sql
-- MySQL / PostgreSQL
CREATE INDEX idx_orders_customer_status_created
    ON orders (customer_id, status, created_at);
```

Because the index is sorted by `customer_id` first, then `status`, then `created_at`, it can only be used efficiently for query patterns that start from the **left**. This is the **leftmost-prefix rule**. The index above can serve:

- `WHERE customer_id = 42`
- `WHERE customer_id = 42 AND status = 'paid'`
- `WHERE customer_id = 42 AND status = 'paid' AND created_at > '2026-01-01'`

But it **cannot** efficiently serve:

- `WHERE status = 'paid'`: skips the leading `customer_id`, so the sort order is useless.
- `WHERE customer_id = 42 AND created_at > '2026-01-01'`: this uses only the `customer_id` part; the gap at `status` stops the index from applying the `created_at` range.

Think of it like a phone book sorted by last name, then first name. You can find "everyone named Smith" and "Smith, John" instantly. You cannot find "everyone named John" without reading the whole book.

A practical consequence: **one well-ordered composite index often beats three single-column indexes.** Put equality-filter columns first, range and sort columns last. Lead with the range instead (`created_at` ahead of `customer_id`) and the range kills your ability to use anything after it. That ordering mistake is the one I catch most often in code review.

## Covering indexes

A **covering index** contains every column a query needs, so the database answers entirely from the index and never touches the table. In `EXPLAIN` output you will see `Using index` (MySQL) or an `Index Only Scan` (PostgreSQL).

Say this query runs constantly:

```sql
SELECT status, total
FROM orders
WHERE customer_id = 42;
```

An index on `customer_id` alone finds the rows, then does a second hop per row back to the table to fetch `status` and `total`. Widen the index to cover them:

```sql
-- MySQL: include the extra columns in the key
CREATE INDEX idx_orders_customer_covering
    ON orders (customer_id, status, total);

-- PostgreSQL: keep the key lean, carry payload columns in INCLUDE
CREATE INDEX idx_orders_customer_covering
    ON orders (customer_id) INCLUDE (status, total);
```

Now the query reads only the index. On a hot read path this can be a several-times speedup because you eliminate the row lookups. PostgreSQL's `INCLUDE` is nicer here: the extra columns ride along in the leaf pages without becoming part of the sort key, keeping the tree smaller.

The catch: covering indexes are wider, so they cost more storage and slow writes more. Use them where a specific query is both frequent and hot, not everywhere.

## Why indexes slow down writes

This is the trade-off you are actually making, and it is the part that gets skipped. Every index is a copy of data that has to stay consistent with the table.

- **INSERT**: the row is added, and *every* index on the table must insert the new value into the right sorted position.
- **UPDATE**: if you change an indexed column, the old index entry is removed and a new one inserted. Change a non-indexed column and the indexes are untouched.
- **DELETE**: the row and all its index entries go away.

So a table with eight indexes does roughly eight times the index maintenance on every insert. On a write-heavy table (event logs, queue tables, high-throughput ingestion) this is real and measurable. Indexes also consume disk and RAM, and RAM matters more than disk, because an index only helps when its hot pages fit in the buffer pool / shared buffers.

The mental model: **indexes trade write throughput and storage for read speed.** That is a great deal for read-heavy workloads (most web apps) and a bad one for tables you mostly write and rarely query.

## Reading EXPLAIN

Stop guessing whether an index is used. Ask the database. `EXPLAIN` shows the planned strategy; `EXPLAIN ANALYZE` actually runs it and reports real timings and row counts.

```sql
-- Both engines: show the plan
EXPLAIN SELECT * FROM orders WHERE customer_id = 42;

-- PostgreSQL: run it and show real timing + estimated vs actual rows
EXPLAIN ANALYZE SELECT * FROM orders WHERE customer_id = 42;

-- MySQL 8+: same idea
EXPLAIN ANALYZE SELECT * FROM orders WHERE customer_id = 42;
```

What I look at first:

- **MySQL `type` column.** `ALL` means full table scan (bad on a big table). `ref`, `range`, `eq_ref`, `const` mean an index is doing its job. Also check `key` (which index was picked) and `rows` (how many the planner expects to examine).
- **PostgreSQL node type.** `Seq Scan` is a full scan; `Index Scan`, `Index Only Scan`, and `Bitmap Index Scan` mean the index is used.
- **Estimated vs actual rows** (with `ANALYZE`). A wild mismatch usually means stale statistics: run `ANALYZE` (Postgres) or `ANALYZE TABLE` (MySQL) so the planner has fresh numbers.

A `Seq Scan` is not automatically wrong. On a small table, or when a query returns most of the rows, scanning is genuinely faster than an index. The planner knows this. Trust it, but verify with real data volumes. A plan that looks fine on 100 dev rows can flip to a scan on 100 million.

## Common pitfalls

The failures I see over and over:

- **Over-indexing.** Adding an index for every column "just in case." Each one taxes writes and eats memory, and the planner can only use so many. Index for queries you run, then stop.
- **Indexing low-cardinality columns alone.** A standalone index on a boolean or a 3-value `status` rarely helps. Fold it into a composite index behind a high-cardinality column instead.
- **Wrapping the indexed column in a function.** This is the silent killer:

```sql
-- Index on created_at is IGNORED — the function hides the raw column value
SELECT * FROM orders WHERE YEAR(created_at) = 2026;

-- Rewrite as a range so the index applies
SELECT * FROM orders
WHERE created_at >= '2026-01-01' AND created_at < '2027-01-01';
```

  The same trap hits `WHERE LOWER(email) = ...`, `WHERE CAST(id AS text) = ...`, and leading-wildcard `LIKE '%term'`. If you genuinely need the transformation, both engines support an **expression index** (e.g. `CREATE INDEX ON orders (LOWER(email))` in PostgreSQL, or a functional index in MySQL 8), but rewriting the query is usually simpler.

- **Implicit type mismatches.** Comparing an indexed `VARCHAR` column to a number (`WHERE phone = 12345`) forces a cast and drops the index. Match the types.
- **Redundant indexes.** An index on `(a, b)` already covers queries on `a` alone (leftmost prefix), so a separate index on `(a)` is dead weight.

## FAQ

**Does adding an index ever make a query slower?**
The query itself, rarely: the planner won't use an index that hurts. But the index slows every write to that table and consumes memory, so a needless index makes your *overall* system slower. That is the real cost.

**How many indexes is too many on one table?**
There is no hard number, but if a write-heavy table has more than five or six indexes, review them. Look for redundant ones (covered by a composite's leftmost prefix) and unused ones; PostgreSQL exposes `pg_stat_user_indexes`, and MySQL's `sys.schema_unused_indexes` view flags indexes nothing has touched.

**Should I index every foreign key?**
Almost always yes. You join on them constantly, and in MySQL an `InnoDB` foreign key requires an index anyway. Unindexed foreign keys also make parent-row deletes scan the child table. Postgres does *not* auto-create the index for a foreign key. That one is on you.

**What is the difference between EXPLAIN and EXPLAIN ANALYZE?**
`EXPLAIN` shows the plan the optimizer intends to use, using estimates, without running the query. `EXPLAIN ANALYZE` executes it and reports actual timings and row counts, which lets you catch bad estimates. Careful: `EXPLAIN ANALYZE` really runs the statement, so don't point it at an `UPDATE` or `DELETE` on production without wrapping it in a transaction you roll back.

## Conclusion

Indexing is not magic and it is not free. An index is a sorted B-tree copy of your data that turns scans into lookups — you pay for that with slower writes and more storage. Index the columns you filter, join, and sort on; order composite indexes so equality columns come first and the leftmost-prefix rule works for you; reach for covering indexes on hot read paths; and never wrap an indexed column in a function you could rewrite as a range. Then confirm every assumption with `EXPLAIN ANALYZE` against realistic data volumes rather than trusting your gut.

Once your indexes are pulling their weight, the next wins are usually at the application layer: killing the [Eloquent N+1 query problem](/blog/eloquent-n1-query-problem) so you issue fewer queries in the first place, and caching the expensive ones that remain (see our guide on caching expensive queries in Laravel). Fast queries and fewer of them — that is the whole game.