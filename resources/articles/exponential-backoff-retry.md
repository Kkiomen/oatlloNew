---
name: "Building a Robust Retry Strategy with Exponential Backoff Retry"
slug: exponential-backoff-retry
short_description: "How to build an exponential backoff retry with jitter that survives real outages: which errors to retry, capping delays, and Retry-After."
language: en
published_at: 2026-09-02 09:00:00
is_published: true
tags: [php, laravel, resilience, http]
---

The first time a retry loop took down one of our services, it wasn't the third-party API that failed. It was us. A payment provider hiccuped for maybe two seconds, every worker retried immediately, and the flood of requests we sent back kept the provider on its knees long after it would have recovered on its own. That incident is why I now reach for an **exponential backoff retry** before I write any `while` loop around a flaky network call.

The goal is a retry that helps a struggling dependency recover instead of amplifying the outage. That comes down to four things: the delay formula, jitter, knowing which errors deserve a retry, and leaning on the tools Laravel already ships.

## What exponential backoff actually means

A naive retry waits a fixed amount between attempts: try, wait 1s, try, wait 1s, and so on. That's fine when a single client hits a single hiccup. It falls apart when hundreds of clients hit the same hiccup at the same moment, because they all retry in lockstep.

Exponential backoff spreads those attempts out over time. The wait grows with each failure:

```
delay = base * 2 ^ attempt
```

With a `base` of 100ms you get 100ms, 200ms, 400ms, 800ms, and so on. Each retry gives the downstream system more room to breathe than the last. Two things keep this sane in production:

- **A cap.** `2 ^ attempt` grows fast. Without an upper bound you'll eventually be sleeping for minutes. Clamp it: `min(cap, base * 2 ^ attempt)`.
- **A total attempt limit.** Backoff decides *how long* to wait; it does not decide *when to give up*. Always bound the number of tries so a permanently dead dependency doesn't hold a request open forever.

## Why jitter is the part people skip

Here's the trap. Pure exponential backoff still synchronizes clients. If a hundred workers all failed at `T=0`, they'll all wait 100ms, then all retry at `T=100ms`, then all wait 200ms, and retry together again at `T=300ms`. You've slowed the stampede down but you haven't broken it up. This is the classic thundering-herd, or retry storm, and it's exactly what bit us.

Jitter fixes it by adding randomness so the herd smears across the time window instead of arriving in a spike. There are two common flavors, both from AWS's well-known write-up on the topic:

**Full jitter** picks a random delay anywhere between zero and the current ceiling:

```
delay = random(0, min(cap, base * 2 ^ attempt))
```

**Equal jitter** keeps half the delay fixed and randomizes the other half, so you never retry *too* eagerly:

```
temp  = min(cap, base * 2 ^ attempt)
delay = temp / 2 + random(0, temp / 2)
```

I default to full jitter. It gives the widest spread and the lowest chance of clients colliding again. Equal jitter is a reasonable choice when you want a guaranteed minimum wait between attempts, for instance to avoid hammering a rate limiter that measures request spacing.

## Retry the right errors, not everything

Retrying blindly is how you turn a client bug into a billing incident. The rule of thumb: retry only *transient* failures, the ones that might succeed if you simply try again in a moment.

Worth retrying:

- Connection timeouts and read timeouts
- Connection resets and DNS blips
- HTTP **429 Too Many Requests**
- HTTP **5xx** (500, 502, 503, 504), where the server is struggling rather than refusing

Not worth retrying:

- **400 Bad Request** and **422 Unprocessable Entity**, because your payload is wrong and it'll be wrong on attempt five too
- **401 / 403**, since an auth problem won't fix itself by repeating
- **404 Not Found**, when the resource simply isn't there

There's one more thing to check before retrying anything: is the operation safe to repeat? A `GET` is naturally safe. A `POST` that charges a card is not, unless the server dedupes it. If you're retrying writes, pair this strategy with an [idempotency key](/blog/idempotency-key-api-safe-retries) so a duplicated request can't double-charge or double-create.

## Respect Retry-After on 429

When a server returns 429 (and sometimes 503), it often tells you exactly how long to wait via the `Retry-After` header. That value can be seconds (`Retry-After: 30`) or an HTTP date. When it's present, it wins. The server knows more about its own recovery window than your formula does. Use the header value as your delay for that attempt and fall back to backoff-with-jitter only when it's absent.

If you want the fuller picture of *why* an API throttles you in the first place, the mechanics are worth understanding; see [token bucket vs fixed window rate limiting](/blog/api-rate-limiting-token-bucket-vs-fixed-window).

## A clean PHP implementation

Here's a self-contained version that pulls the ideas together: capped exponential backoff, full jitter, a retryable-error check, and `Retry-After` support. It's framework-agnostic and expects a callable that either returns a result or throws.

```php
<?php

class RetryableException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }
}

function withBackoff(
    callable $operation,
    int $maxAttempts = 5,
    int $baseMs = 100,
    int $capMs = 10_000,
): mixed {
    $attempt = 0;

    while (true) {
        try {
            return $operation();
        } catch (RetryableException $e) {
            $attempt++;

            if ($attempt >= $maxAttempts) {
                throw $e; // give up, bubble the failure
            }

            $delayMs = $e->retryAfterSeconds !== null
                ? $e->retryAfterSeconds * 1000              // server told us when
                : fullJitterDelay($attempt, $baseMs, $capMs); // otherwise back off

            usleep($delayMs * 1000);
        }
    }
}

function fullJitterDelay(int $attempt, int $baseMs, int $capMs): int
{
    $ceiling = min($capMs, $baseMs * (2 ** $attempt));

    return random_int(0, $ceiling);
}
```

A few decisions baked in here that I'd defend:

- **Only `RetryableException` triggers a retry.** Everything else propagates immediately. The decision of *what is transient* lives at the boundary where you make the HTTP call and inspect the status code, not in this loop.
- **`Retry-After` short-circuits the formula.** No point rolling dice when the server handed you the answer.
- **The loop throws on exhaustion** rather than returning `null`, so a caller can't silently treat a total failure as an empty result.

To use it, translate transport-level failures into that exception at the call site:

```php
$response = withBackoff(function () use ($client, $url) {
    $res = $client->get($url);

    if ($res->getStatusCode() === 429) {
        $after = $res->getHeaderLine('Retry-After');
        throw new RetryableException('rate limited', is_numeric($after) ? (int) $after : null);
    }

    if ($res->getStatusCode() >= 500) {
        throw new RetryableException('server error');
    }

    return $res;
});
```

## Let Laravel do the boring part

If you're on Laravel, you rarely need to hand-roll the loop. The framework ships two tools that cover most cases.

The `retry()` helper wraps any callable. Its third argument can be a closure that returns the sleep in milliseconds for a given attempt, which is exactly where your backoff-plus-jitter formula goes:

```php
use Illuminate\Support\Facades\Http;

$result = retry(
    times: 5,
    callback: fn () => Http::get('https://api.example.com/orders')->throw(),
    sleepMilliseconds: fn (int $attempt) => min(10_000, 100 * (2 ** $attempt)) + random_int(0, 100),
    when: fn (\Throwable $e) => $e instanceof \Illuminate\Http\Client\RequestException
        && in_array($e->response->status(), [429, 500, 502, 503, 504], true),
);
```

The `when` closure is doing the heavy lifting: it retries throttling and server errors but lets a 422 fail fast.

For HTTP specifically, the client has a `retry()` method built in. You can pass a closure for the sleep to add jitter, and another to decide whether a given failure is retryable:

```php
$response = Http::retry(
    times: 4,
    sleepMilliseconds: fn (int $attempt) => (100 * (2 ** $attempt)) + random_int(0, 100),
    when: fn (\Throwable $e, $request) => $e instanceof \Illuminate\Http\Client\ConnectionException
        || ($e instanceof \Illuminate\Http\Client\RequestException && $e->response->status() >= 500),
)->get('https://api.example.com/orders');
```

For work that can afford to wait minutes rather than milliseconds (webhooks, syncs, outbound notifications), push it onto a queue instead and let the worker back off between attempts. That's a different tool for a different timescale; [retrying failed jobs in Laravel](/blog/laravel-retry-failed-jobs) covers the queue side.

## The same idea in JavaScript

Node code hits the same wall against `fetch`, so here's full jitter in JS:

```js
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function withBackoff(fn, { maxAttempts = 5, baseMs = 100, capMs = 10_000 } = {}) {
  for (let attempt = 1; ; attempt++) {
    try {
      return await fn();
    } catch (err) {
      const retryable = err.status === 429 || (err.status >= 500 && err.status <= 599);
      if (!retryable || attempt >= maxAttempts) throw err;

      const retryAfter = Number(err.retryAfter);
      const ceiling = Math.min(capMs, baseMs * 2 ** attempt);
      const delay = Number.isFinite(retryAfter) ? retryAfter * 1000 : Math.random() * ceiling;

      await sleep(delay);
    }
  }
}
```

Same shape, same guarantees: transient-only, capped, jittered, `Retry-After` aware.

## Pitfalls I've actually hit

- **Retrying non-idempotent writes without protection.** We once double-created refunds because a timeout fired *after* the server had already processed the request. The response was lost; the side effect wasn't.
- **No cap on the delay.** A misconfigured `capMs` meant a stuck job slept for eleven minutes before its next try. Nobody noticed until the queue backed up.
- **No jitter.** Covered above, but it bears repeating: backoff without jitter just moves the stampede, it doesn't disperse it.
- **Retrying 4xx.** Burning five attempts on a 401 wastes time and can trip abuse detection on the far end.
- **Ignoring the total time budget.** Five attempts with generous backoff can easily exceed a request's own timeout. If the user is waiting on the response, your retries need to fit inside that window.
- **Logging nothing.** If you don't log the attempt number and the delay, you're blind when a dependency degrades slowly rather than failing outright.

## FAQ

### How many retries should I allow?

For a synchronous, user-facing request, three to five is plenty — beyond that you're just making the user wait longer for the same bad news. Background jobs can afford more because nobody's staring at a spinner. The real constraint is your total time budget, not a magic number.

### Full jitter or equal jitter?

Full jitter for most cases. It spreads clients out the most and, in AWS's own testing, reduced competing requests without meaningfully increasing completion time. Reach for equal jitter only when you need a guaranteed minimum gap between attempts.

### Does Retry-After override my backoff?

Yes. When the server sends `Retry-After`, honor it — it reflects the server's actual recovery estimate. Fall back to your jittered formula only when the header is missing.

### Can I safely retry a POST?

Only if the endpoint is idempotent or you send an idempotency key. Otherwise a retry after a lost response can duplicate the side effect. GETs and other read-only calls are safe to retry freely.

## Wrapping up

A retry strategy is one of those things that looks trivial and quietly isn't. Get the delay formula right, cap it, cap the attempts, add full jitter, and retry only the errors that stand a chance of clearing. On Laravel, the `retry()` helper and the HTTP client's `retry()` method already give you the hooks — you just supply a sleep closure with your backoff-and-jitter math and a `when` condition that filters out the 4xx noise. Do that, and the next time a dependency stumbles, your retries will help it back up instead of holding it down.