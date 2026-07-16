---
slug: database-isolation-levels-carousel
type: carousel
language: en
title: "Isolation levels"
topic: database
source_type: article
source: database-isolation-levels
link: https://oatllo.com/database-isolation-levels
publish_at: 2026-11-20 19:00
status: ready
formats: [post]
hashtags: [database, sql, mysql, postgres, laravel]
caption: |
  The report read some rows before a batch job committed and some rows after. The query was fine. The data was fine.

  Atomicity is the part everyone learns. Isolation is the dial that decides
  whether two concurrent transactions see a coherent world at all.

  Full guide linked in bio.

  Do you know your production engine's default level?
verified:
  verdict: approved
  at: 2026-07-16 06:59
  fingerprint: e443dace488631fd1364ce3eb95e4e9e893d07e9
  checks:
    - "engine defaults verified against reality, not just the article: MySQL InnoDB really does default to REPEATABLE READ and PostgreSQL to READ COMMITTED"
    - non-repeatable vs phantom distinction (one row's value vs a result set's membership) is correct and matches the article
    - SQLSTATE 40001 is the real Postgres serialization_failure code; 'SERIALIZABLE converts bad outcomes into errors, not prevention' is accurate and the article's framing
    - DB::transaction(closure, 3) second arg is genuinely $attempts and reruns on deadlock; the keep-side-effects-out and atomic-decrement caveats are the article's own
  notes: |
    Nightly-report opener traces verbatim to the article. Nothing here ages.
---

## A nightly report summed to a number that never actually existed

The total did not match the sum of its own line items. Nobody had thought about
isolation, so the database handed us a snapshot that was never a real state.

<!-- slide -->

## The same SELECT, two answers, one transaction

A non-repeatable read: you read a row, someone commits a change underneath you,
your second read disagrees. READ COMMITTED allows it - every statement gets a
fresh snapshot.

<!-- slide -->

## A phantom is not a non-repeatable read

Non-repeatable is one row's value changing. Phantom is a result set's
membership changing: "orders over $1000" gives ten rows, someone inserts a
match, you re-run and get eleven.

<!-- slide -->

## Same code, two engines, two behaviours

```sql
-- MySQL InnoDB:  REPEATABLE READ
-- PostgreSQL:    READ COMMITTED
```

Develop on a Postgres laptop, deploy to MySQL, and a read-then-write sequence
sits on different rungs of the ladder. It looks like "works on my machine".

<!-- slide -->

## SERIALIZABLE does not prevent bad outcomes

```sql
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
```

It converts them into errors. Postgres aborts the loser with SQLSTATE `40001`
and expects a retry. Set it without retry logic and you trade silent corruption
for loud failures.

<!-- slide role="cta" -->

## Pair anything strict with retries

```php
DB::transaction(function () {
    // work that might deadlock
}, 3);
```

The second argument reruns the closure on a deadlock, so keep side effects out
of the body. And an atomic `decrement()` often beats turning the dial at all.

