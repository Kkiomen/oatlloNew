---
slug: circuit-breaker-pattern-carousel
type: carousel
language: en
title: "Circuit breaker"
topic: architecture
source_type: article
source: circuit-breaker-pattern
link: https://oatllo.com/circuit-breaker-pattern
publish_at: 2026-09-25 19:00
status: ready
formats: [post]
hashtags: [architecture, php, resilience, backend, redis]
caption: |
  A payment gateway degraded during a Friday sale and within minutes we could not serve pages unrelated to payments.

  Every checkout thread sat blocked on a call that was never going to succeed.
  A breaker would have failed them in a millisecond.

  Full walkthrough linked in bio.

  Which dependency would take you down today?
---

## A slow API is more dangerous than a dead one. It drains your workers.

An error comes back instantly and you move on. A 10-second hang holds a worker
for 10 seconds. Requests arrive faster than they drain, and the pool fills.

<!-- slide -->

## Three states, one job

```text
closed     count failures, calls pass through
open       fail fast, no network call at all
half-open  one probe: close or re-open
```

The probe is what stops the flapping. You confirm recovery with one cheap call,
not by dumping production traffic on a service that came back two seconds ago.

<!-- slide -->

## The mistake ported from Java and Go

```php
// PHP is share-nothing. A property counter
// resets every request and never trips.
$failures = (int) $cache->get($k, 0) + 1;
$cache->set($k, $failures, $cooldown * 2);
```

Each request starts with a fresh object graph. State lives in Redis or the
breaker does nothing at all.

<!-- slide -->

## Do not let a 422 open your circuit

```php
try {
    $rates = $breaker->call(fn () =>
        $client->getRates($order));
} catch (CircuitOpenException) {
    $rates = $this->cachedFallback($order);
}
```

Count timeouts and connection errors. A `404` is not the dependency being down
- it is one buggy client opening your circuit for everyone.

<!-- slide role="cta" -->

## An open breaker still needs a plan

No fallback means you swapped a slow failure for a fast one. Cached data, a
queued job, a sensible default. Full walkthrough linked in bio.
