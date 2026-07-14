---
name: "Optimizing SQL Queries with EXPLAIN: A Practical Guide"
slug: optimizing-sql-queries-with-explain
short_description: "Learn to use SQL EXPLAIN to optimize query performance, read the plan, spot full scans, and fix them with indexes or sargable rewrites."
language: en
published_at: 2026-12-18 09:00:00
is_published: true
tags: [sql, mysql, postgresql, performance, databases]
---

The first time a query took eleven seconds in production and under 40ms on my laptop, I did what most of us do: blamed the network, then the ORM, then the phase of the moon. The real answer was sitting in the query plan the whole time. If you want to use SQL EXPLAIN to optimize a query, the trick isn't memorizing every column of output — it's learning to read the two or three signals that actually predict pain.

This guide walks through how the planner thinks, how to read `EXPLAIN` on both MySQL and PostgreSQL, and how to turn a full table scan into an index lookup without guessing.

## What EXPLAIN actually tells you

`EXPLAIN` asks the database, "if I ran this query, how would you do it?" You get back the plan: which tables get touched, in what order, whether an index is used, and roughly how many rows the optimizer expects to chew through.

Two things trip people up early.

- **Estimates are not measurements.** Plain `EXPLAIN` shows the optimizer's *guesses*, based on table statistics. Those guesses can be wildly off if stats are stale.
- **The plan can change.** Same query, different data distribution or a fresh `ANALYZE`, and the planner may pick a different strategy.

So `EXPLAIN` is where you start. To see what really happened, you need the analyze variants, which I'll get to.

## Reading a MySQL EXPLAIN

Here's a query I actually had to fix — a lookup on an orders table with a few million rows.

```sql
EXPLAIN
SELECT id, total, created_at
FROM orders
WHERE customer_id = 4812
  AND status = 'shipped';
```

Traditional (non-JSON) output, trimmed to what matters:

```
+----+-------------+--------+------+---------------+------+---------+------+---------+-------------+
| id | select_type | table  | type | possible_keys | key  | key_len | ref  | rows    | Extra       |
+----+-------------+--------+------+---------------+------+---------+------+---------+-------------+
|  1 | SIMPLE      | orders | ALL  | NULL          | NULL | NULL    | NULL | 2938104 | Using where |
+----+-------------+--------+------+---------------+------+---------+------+---------+-------------+
```

Focus on four columns:

- **`type`** is the access method, and honestly the column I read first. Rough hierarchy from best to worst: `const`, `eq_ref`, `ref`, `range`, `index`, `ALL`. `ALL` means a full table scan, every row read. `index` is a full scan of the index (better than `ALL`, still not great). `ref` and `range` mean the database is using an index to narrow things down.
- **`key`** shows which index was actually chosen. `NULL` here confirms the bad news: nothing.
- **`rows`** is the estimated rows examined. Nearly 3 million to return a handful is the smell.
- **`Extra`** carries the notes. `Using where` is normal. What you're hunting for as red flags: `Using filesort` (sorting rows outside an index) and `Using temporary` (a temp table, common with `GROUP BY`/`DISTINCT`). `Using index` is the good one, meaning a covering index answered the query without touching the table.

`type = ALL` plus `key = NULL` plus a huge `rows` estimate is the classic full-scan signature. That was my eleven-second query.

## Fixing the full scan

The predicate filters on `customer_id` and `status`. There was no index covering them, so MySQL read the whole table. The fix is a composite index:

```sql
CREATE INDEX idx_orders_customer_status
ON orders (customer_id, status);
```

Column order matters here — put the most selective, equality-matched column first. Re-running `EXPLAIN`:

```
+----+-------------+--------+------+----------------------------+----------------------------+---------+-------------+------+-------------+
| id | select_type | table  | type | possible_keys              | key                        | key_len | ref         | rows | Extra       |
+----+-------------+--------+------+----------------------------+----------------------------+---------+-------------+------+-------------+
|  1 | SIMPLE      | orders | ref  | idx_orders_customer_status | idx_orders_customer_status | 8       | const,const |   37 | Using where |
+----+-------------+--------+------+----------------------------+----------------------------+---------+-------------+------+-------------+
```

`type` went from `ALL` to `ref`. `rows` dropped from ~2.9M to 37. That's the whole game. If you want the deeper theory on why composite index order matters, we covered it in [database indexing explained](/blog/database-indexing-explained).

To confirm the estimate matched reality, I used `EXPLAIN ANALYZE` (MySQL 8.0.18+), which actually runs the query and reports timing:

```sql
EXPLAIN ANALYZE
SELECT id, total, created_at
FROM orders
WHERE customer_id = 4812 AND status = 'shipped';
```

```
-> Index lookup on orders using idx_orders_customer_status
   (customer_id=4812, status='shipped')
   (cost=13.35 rows=37) (actual time=0.041..0.198 rows=41 loops=1)
```

Read that as two pairs. `rows=37` is the estimate; `actual ... rows=41` is what really came back. When those two numbers are close, you can trust the plan. When they diverge by orders of magnitude, your statistics are lying and it's time to run `ANALYZE TABLE`.

## Reading a PostgreSQL EXPLAIN

Postgres speaks a different dialect but the ideas transfer. Same logical query:

```sql
EXPLAIN
SELECT id, total, created_at
FROM orders
WHERE customer_id = 4812 AND status = 'shipped';
```

```
Seq Scan on orders  (cost=0.00..64821.30 rows=39 width=24)
  Filter: ((customer_id = 4812) AND (status = 'shipped'::text))
```

The node type is what you scan for:

- **Seq Scan** is a sequential scan, the Postgres equivalent of MySQL's `ALL`. Full table read.
- **Index Scan** walks an index, then fetches matching rows from the table (the heap).
- **Index Only Scan** means the index alone satisfies the query, with no heap fetch. Fastest when it applies.
- **Bitmap Heap Scan** (usually paired with a **Bitmap Index Scan**) is where Postgres builds a bitmap of matching pages first, then reads them in physical order. It shines when a query matches a medium-sized chunk of rows, too many for a plain index scan to beat.

The `cost=0.00..64821.30` pair is startup cost and total cost, in the planner's own arbitrary units. They're only meaningful *relative* to other plans, so don't read them as milliseconds.

Add the index, and the plan flips:

```
Index Scan using idx_orders_customer_status on orders
  (cost=0.43..8.61 rows=39 width=24)
  Index Cond: ((customer_id = 4812) AND (status = 'shipped'::text))
```

To measure instead of estimate, Postgres gives you the far more useful form:

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, total, created_at
FROM orders
WHERE customer_id = 4812 AND status = 'shipped';
```

```
Index Scan using idx_orders_customer_status on orders
  (cost=0.43..8.61 rows=39 width=24)
  (actual time=0.028..0.104 rows=41 loops=1)
  Buffers: shared hit=6
Planning Time: 0.142 ms
Execution Time: 0.151 ms
```

`BUFFERS` is the part people skip and shouldn't. `shared hit=6` means six pages came from cache; if you see `read=` numbers climbing, the query is hitting disk, and that's often the actual bottleneck rather than CPU. As with MySQL, compare `rows=39` (estimate) against `actual ... rows=41` to judge whether the planner's mental model matches your data.

## The other fix: sargable predicates

An index only helps if the query lets the database use it. A predicate is **sargable** (Search-ARGument-able) when the indexed column sits alone on one side of the comparison. Wrap it in a function and the index goes dark.

This is the trap I see most in code review:

```sql
-- NOT sargable: function on the indexed column
SELECT id FROM orders
WHERE YEAR(created_at) = 2026;
```

Even with an index on `created_at`, `YEAR(created_at)` forces a scan, because the database has to compute the function for every row before it can compare. Rewrite it as a range instead:

```sql
-- sargable: index on created_at is usable
SELECT id FROM orders
WHERE created_at >= '2026-01-01'
  AND created_at <  '2027-01-01';
```

Same result, but now `EXPLAIN` shows a `range` scan (MySQL) or `Index Scan` (Postgres). Other common offenders: leading wildcards (`LIKE '%term'`), implicit type casts (comparing a string column to a number), and `OR` across different columns that could be a `UNION`.

## A repeatable workflow

When a query is slow, I run the same loop every time.

1. **Capture the real query.** Not a simplified version, but the exact SQL with real parameter values, since the plan can depend on them. If it comes from an ORM, log the generated SQL.
2. **Run plain `EXPLAIN` first.** Cheap, no execution. Look at `type`/node type, `key`, and `rows`.
3. **Find the worst node.** In a join, the full scan on the biggest table is usually your target. Ignore the cheap nodes.
4. **Form one hypothesis.** Missing index? Non-sargable predicate? Bad join order from stale stats? Change one thing.
5. **Measure with `EXPLAIN ANALYZE`** (MySQL) or **`EXPLAIN (ANALYZE, BUFFERS)`** (Postgres). Confirm estimate and actual now agree and timing dropped.
6. **Re-check under load.** A plan that's fast on a warm cache can behave differently cold. Buffer numbers help you spot that.

One caution: `EXPLAIN ANALYZE` and Postgres's `ANALYZE` option *actually execute the query*. On a `SELECT` that's fine. On an `UPDATE` or `DELETE`, wrap it in a transaction you roll back, or you'll analyze your data right out of the table.

## FAQ

### Does EXPLAIN run my query?
Plain `EXPLAIN` does not. It only asks for the plan and returns estimates. The variants that *measure* real timing do run it: MySQL's `EXPLAIN ANALYZE` and PostgreSQL's `EXPLAIN ANALYZE`. For anything that writes data, run it inside a transaction and roll back.

### Why is my estimated row count so different from the actual?
Almost always stale statistics. The planner estimates from a sampled histogram of your data; if the table changed a lot since the last sample, the guess drifts. Run `ANALYZE TABLE orders` (MySQL) or `ANALYZE orders` (PostgreSQL) to refresh, then re-check the plan.

### The plan says it's using my index but the query is still slow. Why?
A few usual suspects: the index isn't selective enough (most rows match, so the scan isn't avoided), the query returns a huge result set where the time is in fetching and transferring rows, or the slowness is I/O, so check `BUFFERS` in Postgres for `read=` counts. It's also worth ruling out an application-side issue like an [N+1 query problem](/blog/eloquent-n1-query-problem) before blaming the plan.

### Should I just add indexes to every column?
No. Every index has to be maintained on `INSERT`, `UPDATE`, and `DELETE`, and it costs disk. Over-indexing quietly slows down writes and can even confuse the planner. Index for the queries you actually run, and for repeated read-heavy queries consider [caching them](/blog/laravel-cache-queries) instead of adding another index.

## Wrapping up

Reading a query plan stops feeling like decoding hieroglyphics once you anchor on the essentials: the access type (`ALL`/`Seq Scan` is your enemy), whether a `key` was chosen, and how far the estimated `rows` sit from reality. Fix full scans with a well-ordered composite index or by rewriting predicates to be sargable — usually both. And when a number surprises you, reach for `EXPLAIN ANALYZE` or `EXPLAIN (ANALYZE, BUFFERS)` so you're arguing with measurements instead of guesses.

Next time a query drags, don't guess at the cause. Run `EXPLAIN`, read those three signals, and you'll usually know the fix before you've finished your coffee. If the slowness turns out to be higher up the stack, that's a job for [profiling the PHP application](/blog/profile-slow-php-application) instead.