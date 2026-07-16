---
slug: database-indexing-carousel
type: carousel
language: en
title: "Database indexing explained"
topic: database
source_type: article
source: database-indexing-explained
link: https://oatllo.com/database-indexing-explained
publish_at: 2026-07-22 19:00
status: ready
formats: [post, reel]
hashtags: [database, sql, mysql, performance, backend]
caption: |
  A plan that looks fine on 100 dev rows can flip to a full scan on 100 million.

  An index is a sorted copy of your data that turns scans into lookups. You pay
  for it with slower writes and more storage. Index what you filter, join and
  sort on, then confirm it with EXPLAIN against real data volumes.

  Full write-up linked in bio.

  What is the worst full table scan you have found in production?
verified:
  verdict: approved
  at: 2026-07-16 07:02
  fingerprint: 154dbdbf6765fa5e2ccf95dd9a50450f2a9a7e4f
  checks:
    - the fine-on-100-dev-rows-full-scan-at-100-million hook is the article own line, and the B-tree sorted-copy framing matches
    - leftmost-prefix rule verified - the composite on (customer_id, status, created_at) serves customer_id and customer_id plus status, but not status alone - correct per the rule and the article
    - YEAR(created_at) defeating an index on created_at while the range rewrite keeps it is correct SQL and matches the article silent-killer section
    - EXPLAIN ANALYZE plus MySQL type ALL and Postgres Seq Scan as scan indicators are accurate and match the article
  notes: |
    Phone-book analogy compresses the article last-name-then-first-name version but keeps the logic intact. No version-pinned claims.
---

## Fine on 100 dev rows. A full scan at 100 million.

Nothing changed in the code. The data just grew up.

<!-- slide -->

## Without an index it reads every row

Finding one row in 10 million means reading 10 million. An index is a sorted
copy of the column, so the database walks a B-tree instead: a few hops, not a
scan.

<!-- slide -->

## The columns you filter on, sorted

```sql
CREATE INDEX idx_orders_lookup ON orders (
    customer_id, status, created_at
);
```

Sorted by `customer_id` first, then `status`, then `created_at`. That order is
not a detail.

<!-- slide -->

## Column order is the whole rule

The index above serves `WHERE customer_id = 42` and `customer_id = 42 AND status
= 'paid'`. It does nothing for `WHERE status = 'paid'` alone. A phone book sorted
by last name can't find every John.

<!-- slide -->

## This one silently ignores your index

```sql
-- index on created_at: IGNORED
SELECT * FROM orders
WHERE YEAR(created_at) = 2026;

SELECT * FROM orders
WHERE created_at >= '2026-01-01'
  AND created_at < '2027-01-01';
```

The function hides the raw column value. The range doesn't.

<!-- slide role="cta" -->

## Adding an index isn't using an index

```sql
EXPLAIN ANALYZE
SELECT * FROM orders WHERE customer_id = 42;
```

MySQL `type: ALL` or a Postgres `Seq Scan` means it's still scanning. Ask the
database, don't trust your gut.
