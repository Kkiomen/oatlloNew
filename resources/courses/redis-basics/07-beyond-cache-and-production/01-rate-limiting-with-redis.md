---
title: "Rate limiting with Redis"
slug: rate-limiting-with-redis
seo_title: "Redis Rate Limiting with INCR and EXPIRE"
seo_description: "Build a fixed-window rate limiter with Redis rate limiting: INCR and EXPIRE per user, plus how Laravel's throttle middleware uses the same counter."
---

Redis rate limiting stops one user from hammering your login form or your API. The rule is
simple: "no more than 60 requests per minute". Redis fits this job perfectly because it
counts fast and expires keys on its own, so the count resets without a cron job or a cleanup
task of your own.

## The idea: a counter per user, per window

Give every user (or IP) their own counter key, and let that key live for exactly one time
window. Each request bumps the counter. When the counter goes over your limit, you reject
the request. When the window ends, the key expires and the count resets to zero.

This is the [atomic counter](/course/redis-basics/keys-values-and-expiration/atomic-counters)
pattern from Chapter 2, plus the [expiration](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl)
you learned right after it.

## Building it with INCR and EXPIRE

Say user `42` is making requests. On each request, run:

```bash
INCR rate:user:42
EXPIRE rate:user:42 60 NX
```

```text
(integer) 1
(integer) 1
```

`INCR` creates the key at `1` if it does not exist, or adds one if it does. It returns the
new count, so your code reads that number directly. `EXPIRE ... 60 NX` sets a 60-second
lifetime **only if the key has none yet** (`NX`), so the window starts on the first
request and is not pushed forward by later ones. That `NX` flag on `EXPIRE` is a Redis 7
addition; on older versions you would guard the expiry in your own code by only setting it
when `INCR` returned `1`.

Your application logic is then just:

```text
count = INCR rate:user:42
if first request: EXPIRE rate:user:42 60 NX
if count > 60: reject with "429 Too Many Requests"
```

Because `INCR` is [atomic](/course/redis-basics/keys-values-and-expiration/atomic-counters),
two requests arriving at the same instant still get two different counts. No request slips
through by reading a stale value.

## This is a fixed window

The window is "fixed" because it lines up to a fixed 60-second block that starts on the
first request. It is the simplest limiter and it is what most apps need. Its one quirk:
a user can send 60 requests at the very end of one window and 60 more at the start of the
next, so up to 120 land in a short burst around the boundary. For most sites that is fine.
More advanced limiters (sliding window, token bucket) smooth that out, but they are beyond
this course.

## Rate limiting in Laravel: throttle and RateLimiter

You rarely write the commands above by hand. Laravel's `throttle` middleware and the
`RateLimiter` facade use exactly this counter-plus-expiry approach, and when your
[cache driver is Redis](/course/redis-basics/redis-and-laravel/redis-as-cache-driver) the
counters live in Redis.

```php
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:5,1'); // 5 requests per 1 minute
```

Or check a limit yourself:

```php
use Illuminate\Support\Facades\RateLimiter;

$executed = RateLimiter::attempt(
    key: 'send-message:'.$user->id,
    maxAttempts: 5,
    callback: fn () => $this->sendMessage(),
    decaySeconds: 60,
);

if (! $executed) {
    return response('Too many messages sent.', 429);
}
```

Same mechanism, nicer API. Knowing what happens underneath means you can debug it when a
user reports being blocked - you can go look at the counter key in `redis-cli`.

## Common mistake

Forgetting the expiry. If you run `INCR` but never `EXPIRE`, the counter grows forever and
the user is blocked permanently after their first burst. Always pair the increment with an
expiry, and use `NX` so repeated requests do not keep resetting the window and let a user
sneak past the limit.

## FAQ

### Why INCR and EXPIRE instead of one command?

Redis has no single "increment and set a first-time expiry" command, so you send both.
Using `EXPIRE ... NX` keeps the window anchored to the first request. In Laravel this is
all handled for you.

### Should I limit by user or by IP?

Limit logged-in actions by user id, and limit public or pre-login actions (like the login
form itself) by IP. Build the key from whichever you have: `rate:user:42` or
`rate:ip:203.0.113.9`.

### What does the user see when blocked?

An HTTP `429 Too Many Requests` response. Laravel returns this automatically for the
`throttle` middleware, along with a `Retry-After` header telling the client when to try
again.
