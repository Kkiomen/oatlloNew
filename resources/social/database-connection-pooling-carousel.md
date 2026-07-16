---
slug: database-connection-pooling-carousel
type: carousel
language: en
title: "Connection pooling in PHP"
topic: database
source_type: article
source: database-connection-pooling
link: https://oatllo.com/database-connection-pooling
publish_at: 2026-10-28 19:00
status: ready
formats: [post, reel]
hashtags: [php, database, postgres, performance, backend]
caption: |
  Too many connections is a counting problem, not a database bug. Do the math: workers per server x number of servers.

  PHP under FPM is share-nothing, so there is nowhere for a warm pool to live
  inside the process. The pool has to go outside it.

  Full write-up in bio.

  What was your first move when that alert fired?
verified:
  verdict: approved
  at: 2026-07-16 07:08
  fingerprint: c68e63b8358afe7f828f485fc01e199cf3c84db8
  checks:
    - matematyka 50 workerow x 6 serwerow = 300 vs limit 200 - zgodna z artykulem
    - PgBouncer transaction mode i emulated prepares - zgodne ze zrodlem
    - "PHP share-nothing: brak wlasnego poola - potwierdzone, nie tylko zrodlowe"
  notes: |
    POPRAWIONE PO WERYFIKACJI: slajd CTA urywal sie w pol zdania na 'Full write-up' - to byl MOJ blad z commita f36077f (usuwanie formulki 'link in bio' zatrzymywalo sie na lamaniu linii i zostawialo wstep). Fragment renderowal sie doslownie na ostatnim slajdzie. Usuniety, slajd konczy sie teraz na swojej puencie. Fakty byly OK od poczatku. Wymaga Twojej ostatecznej akceptacji.
---

## 50 workers x 6 servers = 300 connections. The DB allows 200.

Under load, 100 requests get an ugly connection error, and you find out about it
from customers.

<!-- slide -->

## PHP has no in-process pool. By design.

Request arrives, code opens a connection, script tears down, connection closes.
Share-nothing is why a fatal error can't poison the next request, and why a
pool has nowhere to sit.

<!-- slide -->

## PDO persistent is per worker, not a pool

```php
new PDO($dsn, $user, $secret, [
    PDO::ATTR_PERSISTENT => true,
]);
```

Pool size equals your worker count. Not something you tune. And session state
travels: a `SET`, a changed timezone, a temp table all reach the next request.

<!-- slide -->

## Raising max_connections buys the next outage

On Postgres every connection is a backend process reserving memory. Push the
number high enough and you trade a connection error for an out-of-memory crash.

<!-- slide -->

## Put the pool outside the process

```ini
[pgbouncer]
pool_mode = transaction
default_pool_size = 20
max_client_conn = 2000
```

2000 workers across the fleet can connect. Postgres only ever sees 20 busy
connections.

<!-- slide role="cta" -->

## Transaction mode breaks session state

Anything relying on state across transactions misbehaves, server-side prepared
statements included, so PDO users switch to emulated prepares.

