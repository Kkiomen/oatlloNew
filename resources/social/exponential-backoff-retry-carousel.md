---
slug: exponential-backoff-retry-carousel
type: carousel
language: en
title: "Backoff with jitter"
topic: php
source_type: article
source: exponential-backoff-retry
link: https://oatllo.com/exponential-backoff-retry
publish_at: 2026-11-17 19:00
status: ready
formats: [post, reel]
hashtags: [php, laravel, resilience, http, backend]
caption: |
  The payment provider hiccuped for two seconds. Every worker retried at once, and the flood we sent back kept it down.

  Exponential backoff alone does not fix that: a hundred clients that failed at
  T=0 all wait 100ms and all retry at T=100ms. Jitter is the part people skip.

  Full write-up linked in bio.

  Does your retry loop have a cap on the delay?
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: cccdc7772528a0f82008243e1e457986bdfabcec
  checks:
    - Http::retry(times:, sleepMilliseconds:, when:) - all three named arguments are the real Laravel HTTP client signature, and sleepMilliseconds genuinely accepts a closure, so passing a jitter closure works as shown; matches the article snippet
    - delay = min(cap, base * 2 ** attempt) matches the article formula, and PHP precedence is right (** binds tighter than *, so base * (2 ** attempt))
    - full jitter as random_int(0, ceil) matches both the article fullJitterDelay() and the AWS definition; equal jitter described correctly as half fixed, half random with a guaranteed minimum gap
    - "thundering-herd arithmetic checks out and is the article example verbatim: fail at T=0, all wait 100ms, all retry T=100, all wait 200ms, collide again T=300"
    - retryable list (timeouts, connection resets, DNS blips, 429, 5xx) and non-retryable (400, 422, 401, 403) match the article; payload still wrong on attempt five is the article reasoning
    - Retry-After wins over the formula, fall back to jitter when absent - article FAQ says exactly this
  notes: |
    topic php fits. Two-second hiccup and the self-inflicted outage story are the article opening verbatim. No versions or prices to age; the Laravel retry API is stable.
---

## A 2-second API hiccup, then our own retries kept it down for good

It was not the third-party API that failed. It was us. Every worker retried
immediately and held the provider on its knees long after it would have healed.

<!-- slide -->

## The formula needs two bounds, not one

```
delay = min(cap, base * 2 ** attempt)
```

`2 ** attempt` grows fast - without a cap you eventually sleep for minutes. And
backoff decides how long to wait, never when to give up. Bound the attempts too.

<!-- slide -->

## Backoff without jitter just moves the stampede

A hundred workers fail at T=0. They all wait 100ms. They all retry at T=100ms.
They all wait 200ms and collide again at T=300ms. You slowed the herd down.
You did not break it up.

<!-- slide -->

## Full jitter smears them across the window

```php
$ceil = min($cap, $base * 2 ** $attempt);
$delay = random_int(0, $ceil); // full jitter
```

Random anywhere between zero and the ceiling. Equal jitter fixes half and
randomises the rest - pick it only when you need a guaranteed minimum gap.

<!-- slide -->

## Retrying a 422 five times is five wasted attempts

Retry timeouts, connection resets, DNS blips, 429 and 5xx. Not 400 or 422 -
your payload will still be wrong on attempt five. Not 401 or 403 either.

<!-- slide -->

## Laravel already gives you the hooks

```php
Http::retry(
    times: 4,
    sleepMilliseconds: $fullJitter,
    when: $isTransient,
)->get($url);
```

You supply the sleep closure and the condition that filters out the 4xx noise.
No hand-rolled `while` loop.

<!-- slide role="cta" -->

## When the server sends Retry-After, it wins

No point rolling dice when the server handed you the answer. Fall back to the
jittered formula only when the header is missing.
