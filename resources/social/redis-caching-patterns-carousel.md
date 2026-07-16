---
slug: redis-caching-patterns-carousel
type: carousel
language: en
title: "Redis caching patterns"
topic: redis
source_type: article
source: redis-caching-patterns
link: https://oatllo.com/redis-caching-patterns
publish_at: 2026-11-13 19:00
status: ready
formats: [post]
hashtags: [redis, caching, laravel, performance, backend]
caption: |
  `SETNX` then `EXPIRE` is two commands. If the process dies between them, the lock has no TTL and blocks everyone forever.

  Redis runs commands one at a time, so `SET key val EX 10 NX` does the whole
  thing atomically. One command, no window, no 3am page.

  Full guide linked in bio.

  Which cache bug cost you a weekend?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: f90b492e346a856f65cea4a0b32a795c1ae4e265
  checks:
    - SETNX-then-EXPIRE race and the atomic SET key val EX 10 NX fix match the article and real Redis semantics
    - Redis is single-threaded for command execution - stated in the article and true
    - five hundred requests stampede, the 3600 + rand(0, 300) jitter and the maxmemory 512mb / allkeys-lru CTA all trace to the article
    - Cache::lock -> get / release in a finally is valid Laravel
---

## SETNX then EXPIRE has a race that can lock everyone out forever

```bash
# Crash between these two = lock forever
redis-cli SETNX lock:user:42 1
redis-cli EXPIRE lock:user:42 10
```

The key is set. The expiry never lands. Nothing releases it.

<!-- slide -->

## One command. No window.

```bash
redis-cli SET lock:user:42 1 EX 10 NX
```

Redis is single-threaded for command execution, so this is atomic for free.
`NX` sets only if absent, `EX` attaches the TTL in the same breath.

<!-- slide -->

## A popular key expiring is the trigger, not the fix

A key expires. Five hundred requests miss in the same instant, all fire the
identical expensive query, and the database falls over. The cache was supposed
to protect it.

<!-- slide -->

## One worker rebuilds, everyone else serves stale

```php
$lock = Cache::lock("rebuild:user:{$id}", 10);

if ($lock->get()) {
    try { /* rebuild + Cache::put */ }
    finally { $lock->release(); }
}
```

The winner recomputes. Slightly stale beats a database dogpile.

<!-- slide -->

## Ten thousand keys, one deploy, one expiry second

```php
Cache::put($key, $value, 3600 + rand(0, 300));
```

Write them all with a flat `EX 3600` and they all expire together an hour
later. Jitter fans the expirations out instead of rebuilding the stampede.

<!-- slide role="cta" -->

## A key with no TTL lives until something evicts it

Multiply that by a busy app and you meet `maxmemory` sooner than you would
like. Set `maxmemory 512mb` and `allkeys-lru` today, not at 3am.

