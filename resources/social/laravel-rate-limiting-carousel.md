---
slug: laravel-rate-limiting-carousel
type: carousel
language: en
title: "Laravel rate limiting keys"
topic: laravel
source_type: article
source: laravel-rate-limiting
link: https://oatllo.com/laravel-rate-limiting
publish_at: 2026-11-09 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, api, redis, backend]
caption: |
  `throttle:60,1` keys on the user ID and falls back to the IP. Three service accounts behind one office NAT share one bucket.

  The middleware is not the thing worth designing. The limiter behind it is, and
  the `->by()` key decides who shares a limit with whom.

  Full write-up linked in bio.

  Which key are your API routes actually using?
verified:
  verdict: approved
  at: 2026-07-16 07:17
  fingerprint: 014c26232bec7705b3b17548e6477ea2d30f286b
  checks:
    - throttle:60,1 keys on user id and falls back to IP for guests - correct
    - array of limits counts independently per by() key; 500 user + 100 IP matches the article
    - file/array cache means each server counts alone, so 3 servers turn 60 into 180 - matches the article arithmetic
    - RateLimiter::attempt named args key/maxAttempts/callback are the real parameter names, returns false when over the limit
    - "429 headers correct: Retry-After and X-RateLimit-Reset are handed to the response() callback"
---

## Three service accounts on one office IP throttled as one client

`throttle:60,1` keys by user ID and falls back to the request IP for guests.
Corporate NATs, mobile carriers and CI runners all share one.

<!-- slide -->

## The bucket key is the whole decision

```php
Limit::perMinute(60)->by(
    $request->user()?->id ?: $request->ip()
);
```

The closure runs per request, so you can read the user and branch. Change that
string and you change who shares a limit.

<!-- slide -->

## Return an array. Both limits count.

```php
return [
    Limit::perMinute(500)->by($userKey),
    Limit::perMinute(100)->by($request->ip()),
];
```

Each `Limit` has its own key, so they count independently. A user burns 500
while the shared NAT still caps at 100 across everyone behind it.

<!-- slide -->

## File cache turns throttle:60,1 into 180

```php
// config/cache.php
'default' => env('CACHE_STORE', 'redis'),
```

The limiter counts through the cache. On `file` or `array`, every app server
counts alone. Three servers behind a load balancer means triple the limit.

<!-- slide -->

## Jobs never pass through middleware

```php
RateLimiter::attempt(
    key: 'send:' . $user->id,
    maxAttempts: 5,
    callback: fn () => $user->notify($mail),
);
```

Returns `false` when over the limit instead of running. `tooManyAttempts()`
plus `availableIn()` gives you the seconds for `release()`.

<!-- slide role="cta" -->

## A custom 429 that strips the headers is worse

Laravel hands your `->response()` callback a `$headers` array with
`Retry-After` and `X-RateLimit-Reset`. Drop it and clients lose the only signal
telling them when to come back.
