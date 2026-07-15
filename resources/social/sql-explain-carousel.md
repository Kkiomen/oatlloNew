---
slug: sql-explain-carousel
type: carousel
language: en
title: "Optimizing SQL queries with EXPLAIN"
topic: database
source_type: article
source: optimizing-sql-queries-with-explain
link: https://oatllo.com/optimizing-sql-queries-with-explain
publish_at: 2026-07-31 19:00
status: ready
formats: [post, reel]
hashtags: [sql, mysql, database, performance, backend]
caption: |
  Eleven seconds in production. Under 40ms on my laptop. The plan knew why.

  You do not need every column of EXPLAIN output. Three of them predict the
  pain: how it reads the table, which index it actually picked, and how many
  rows it expects to chew through.

  Full write-up linked in bio.

  What was hiding in the plan the last time you finally ran EXPLAIN?
---

## Eleven seconds in prod. 40ms on your laptop.

Same query. Same code. The plan was different the whole time.

<!-- slide -->

## EXPLAIN is asking instead of guessing

"If I ran this query, how would you do it?" The database answers with the
plan: what it touches, in what order, and roughly how many rows.

<!-- slide -->

## Three columns predict the pain

`type` is how it reads the table. `key` is the index it actually chose.
`rows` is how many it expects to examine. Read those three first.

<!-- slide -->

## The full scan signature

```
type: ALL
key:  NULL
rows: 2900000
```

`ALL` reads every row. `NULL` means no index was used at all.

<!-- slide -->

## The same query, once indexed

```
type: ref
rows: 37
```

`ALL` became `ref`. 2.9 million became 37. That is the whole game.

<!-- slide role="cta" -->

## When the plan itself is lying

`EXPLAIN ANALYZE` prints the estimate next to what actually happened. Close
numbers mean you can trust it. Orders apart means run `ANALYZE TABLE`.
