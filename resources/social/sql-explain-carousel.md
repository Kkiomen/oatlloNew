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
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 70f71cf7567f02b84e8b8f50cad254f1a11d81cd
  checks:
    - eleven seconds in prod vs under 40ms locally matches the article opening
    - type ALL plus key NULL is the real MySQL full-scan signature; ref and rows 37 after the composite index match the article output exactly
    - EXPLAIN ANALYZE printing estimate next to actual, and ANALYZE TABLE as the fix for diverging stats - both correct for MySQL and stated in the article
  notes: |
    rows: 2900000 in the plan block is the articles 2938104 rounded to the 2.9M the article itself quotes. Reads as illustrative output rather than a verbatim paste.
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
