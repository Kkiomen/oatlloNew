---
name: "The Circuit Breaker Pattern for Resilient Services"
slug: circuit-breaker-pattern
short_description: "How the circuit breaker pattern stops a failing dependency from taking down your app: states, thresholds, and a minimal PHP implementation."
language: en
published_at: 2027-01-13 09:00:00
is_published: true
tags: [php, resilience, architecture, http]
---

A slow dependency is more dangerous than a dead one. When a service returns errors instantly, you notice and move on. When it hangs for 30 seconds on every call, your workers pile up waiting, your connection pool drains, and a problem in one downstream API quietly becomes an outage in *your* app. The **circuit breaker pattern** exists to catch exactly this: it watches a dependency, notices when it has gone bad, and stops your code from throwing good requests after bad ones.

I first wired one in after a payment gateway degraded during a Friday sale. Every checkout thread sat blocked on a call that was never going to succeed, and within a few minutes we couldn't serve the pages that had nothing to do with payments. A breaker would have failed those checkout calls in a millisecond and left the rest of the site standing. This post walks through how the pattern works, how to size its thresholds, and how to build a small but honest version in PHP.

## The problem: cascading failure

Picture a request path with three hops. Your app calls an internal orders service, which calls an external shipping API. The shipping API starts timing out at 10 seconds per request.

Without any protection, here's the chain reaction:

- Each orders request holds a worker for the full 10-second timeout.
- Requests arrive faster than they drain, so the worker pool fills up.
- Now requests that don't even touch shipping are stuck waiting for a free worker.
- The orders service looks down to everyone upstream, and the failure spreads.

This is a **cascading failure**, and the root cause is resource exhaustion: threads, connections, and memory all held hostage by calls to a service that can't answer. Retrying makes it strictly worse. You're now sending *more* load to something that's already struggling, which is the opposite of what a hurting service needs.

A circuit breaker breaks that chain. Once it decides the shipping API is unhealthy, it stops calling it and returns an error (or a fallback) immediately. Your workers stay free, the healthy parts of the app keep serving, and the struggling dependency gets a chance to recover instead of being hammered flat.

## How the pattern works: three states

The name comes from an electrical breaker, and the analogy holds up well. A breaker sits in front of an outbound call and tracks its health through three states.

### Closed

This is normal operation. Calls pass straight through to the dependency, and the breaker counts failures as they happen. If failures stay below a threshold, nothing changes and the breaker stays out of the way.

Once the failure count crosses the **failure threshold** (say, 5 failures, or 50% of calls in a window), the breaker trips and moves to open.

### Open

The circuit is tripped. Calls **fail fast**: the breaker rejects them immediately without touching the dependency at all. No network round trip, no timeout, no held worker. Just an instant exception or fallback value.

The breaker stays open for a **cooldown timeout** (also called the reset or sleep window) — maybe 30 seconds. The point of the cooldown is to give the downstream service uninterrupted time to recover instead of a fresh wave of traffic the instant it wobbles.

### Half-open

When the cooldown expires, the breaker doesn't just fling the gates back open. It moves to half-open and allows a single **trial call** through.

- If that trial call succeeds, the dependency looks healthy again. The breaker resets to closed.
- If it fails, the dependency is still sick. The breaker snaps back to open and starts another cooldown.

This trial step is what stops the breaker from flapping. You confirm recovery with one cheap probe rather than dumping full production traffic onto a service that came back up two seconds ago.

Here is the whole cycle in one line: **closed** counts failures, **open** fails fast for a while, **half-open** sends one probe to decide whether to close or re-open.

## Breaker vs. retry: they solve different problems

People often reach for retries and think they're covered. Retries and breakers are not competitors, though — they operate at different layers.

- A **retry** handles a *transient* blip: one dropped packet, one node that just restarted. It assumes the next attempt has a decent chance of working.
- A **circuit breaker** handles a *sustained* outage. It assumes the next attempt will fail too, so it stops asking.

The failure mode of naive retrying is that it keeps hammering a service that's already down. The breaker is the governor that says "we've established this thing is broken, stop trying for now." In practice you want both, plus a timeout, working together:

- **Timeout** bounds how long any single call can hang.
- **Retry with backoff** absorbs the occasional transient failure without stampeding.
- **Circuit breaker** cuts off retries entirely once failures become the norm.

If you want the retry half of that story in detail, I wrote it up in [exponential backoff retry](/blog/exponential-backoff-retry). The short version: retries should back off and jitter, and the breaker should sit above them so that once it's open, you're not retrying at all.

## A minimal PHP implementation

The pattern is simpler than the diagrams make it look. A breaker needs to remember three things between calls: the current state, how many failures it has seen, and when it last tripped. Here's a compact version built on PSR-16 cache so the state survives across requests and processes.

```php
<?php

use Psr\SimpleCache\CacheInterface;

class CircuitOpenException extends \RuntimeException {}

class CircuitBreaker
{
    public function __construct(
        private CacheInterface $cache,
        private string $service,
        private int $failureThreshold = 5,
        private int $cooldownSeconds = 30,
    ) {}

    /**
     * @throws CircuitOpenException when the breaker is open
     */
    public function call(callable $operation): mixed
    {
        if ($this->isOpen()) {
            throw new CircuitOpenException("Circuit for {$this->service} is open");
        }

        try {
            $result = $operation();
            $this->recordSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    private function isOpen(): bool
    {
        $openedAt = $this->cache->get($this->key('opened_at'));

        if ($openedAt === null) {
            return false; // closed
        }

        // Cooldown elapsed? Allow one trial call (half-open).
        if (time() - $openedAt >= $this->cooldownSeconds) {
            return false;
        }

        return true; // still open
    }

    private function recordSuccess(): void
    {
        // A success in half-open (or closed) clears the slate.
        $this->cache->delete($this->key('failures'));
        $this->cache->delete($this->key('opened_at'));
    }

    private function recordFailure(): void
    {
        $failures = (int) $this->cache->get($this->key('failures'), 0) + 1;
        $this->cache->set($this->key('failures'), $failures, $this->cooldownSeconds * 2);

        if ($failures >= $this->failureThreshold) {
            $this->cache->set($this->key('opened_at'), time(), $this->cooldownSeconds * 2);
        }
    }

    private function key(string $suffix): string
    {
        return "cb:{$this->service}:{$suffix}";
    }
}
```

Using it looks like this. The breaker wraps the *outbound* call — that's the only thing it should ever guard:

```php
$breaker = new CircuitBreaker($cache, service: 'shipping-api');

try {
    $rates = $breaker->call(fn () => $shippingClient->getRates($order));
} catch (CircuitOpenException) {
    $rates = $this->cachedFallbackRates($order); // degrade gracefully
} catch (\Throwable) {
    $rates = $this->cachedFallbackRates($order);
}
```

A few things worth calling out about this code:

- **State lives in the cache, not in a property.** PHP is share-nothing: each request (and each queue worker) starts with a fresh object graph, so an in-memory counter would reset constantly and never trip. Putting `failures` and `opened_at` in **Redis** or Memcached lets every process see the same breaker. This is the single most common thing people get wrong when they port a breaker example from a long-running runtime like Java or Go.
- **Half-open is implicit here.** When the cooldown elapses, `isOpen()` returns `false`, so exactly the next call slips through as the trial. If it succeeds, `recordSuccess()` wipes the state and we're closed again; if it fails, `recordFailure()` re-stamps `opened_at` and we're open for another window.
- **The breaker only catches the exceptions you let it.** In real code you usually don't want a `404` or a validation error tripping the circuit; those aren't the dependency being *down*. Catch narrowly (connection and timeout exceptions), and let business errors pass through uncounted.

This version is deliberately simple. Because `recordSuccess()` clears the counter, it trips on *consecutive* failures rather than a failure *rate* over a window. One success in the middle resets you to zero. The half-open state also allows its single probe with no locking, so under heavy concurrency a couple of trial calls can slip through together. Both are fine trade-offs for most apps, but know they're there.

## Pitfalls I've hit

- **In-memory state in PHP.** Worth repeating: a breaker whose counters live in a class property does nothing across requests. Use a shared store.
- **Tripping on the wrong errors.** If a `422` from bad input counts as a failure, one buggy client can open your circuit for everyone. Only count failures that mean *the dependency is unhealthy*.
- **A cooldown that's too short.** Set it to two seconds and the breaker flaps open and closed while the service is still recovering, sending it repeated probe traffic. Give downstream real breathing room.
- **A threshold that's too tight.** Tripping after a single failure turns every random blip into a mini outage on your side. Require a run of failures, or a percentage over a window.
- **No fallback.** An open breaker throws fast, but if the caller has no plan for that exception, you've just swapped a slow failure for a fast one. Decide what a degraded response looks like: cached data, a queued job, a sensible default.
- **One global breaker for everything.** Each dependency deserves its own breaker keyed by service. A sick shipping API shouldn't open the circuit to your payment provider.

## Reach for a library in production

Rolling your own is great for understanding the pattern, and fine for a single well-understood call. For anything broader, use something battle-tested. In the PHP world, **Ganesha** is the mature, widely used circuit breaker. It supports rate- and count-based strategies and pluggable storage (Redis, Memcached, APCu) out of the box. **Symfony** users can lean on the HttpClient's retry and failure handling, and general resilience libraries wrap breakers together with retries and timeouts.

Whichever you pick, the breaker is one piece of a resilience toolkit. It pairs naturally with backoff, timeouts, and the kind of loose coupling you get from an [event-driven architecture](/blog/event-driven-architecture-practical-introduction), where a failing consumer doesn't block the producer in the first place.

## FAQ

### Where should the circuit breaker live, client or server?

On the caller's side, wrapping the outbound call. The whole value is protecting *you* from a slow or failing dependency, so the breaker has to sit between your code and that dependency. A server can't fail its own calls fast on your behalf.

### Should I open the circuit on timeouts or only on errors?

Timeouts especially. A timeout is the exact signal that a dependency is slow enough to exhaust your resources, which is the failure the pattern was built to stop. Count timeouts and connection errors; ignore application-level responses like `404` or `422`.

### Can I use a circuit breaker with a message queue or background jobs?

Yes, and it's a great fit. A worker calling a flaky API can check the breaker and, if it's open, release the job back to the queue with a delay instead of burning a retry. That keeps failed work from spinning uselessly while the dependency is down.

### How do I pick the threshold and cooldown values?

Start from the dependency's normal behavior. Set the threshold above its typical background error rate so blips don't trip it, and set the cooldown to at least how long that service usually takes to recover from a restart, often 30 to 60 seconds. Then watch how often the breaker opens in production and adjust.

## Wrapping up

The circuit breaker pattern earns its keep in one specific moment: when a dependency goes bad and your instinct (or your retry loop) is to keep calling it. By failing fast during the open state and probing carefully in half-open, the breaker keeps a downstream outage from draining your workers and cascading into your own.

If you're adding resilience to a service today, wire the three together: a **timeout** on every outbound call, **backoff** on retries for transient blips, and a **breaker** that cuts retries off once failures become the norm. Store the breaker state in Redis so it works across your PHP processes, give each dependency its own breaker, and always have a fallback ready for when it trips. Do that and a two-second hiccup in a third-party API stays a two-second hiccup — not a Friday-night incident.