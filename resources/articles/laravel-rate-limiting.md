---
name: "Laravel Rate Limiting for APIs and Routes"
slug: laravel-rate-limiting
short_description: "How Laravel rate limiting actually works: the throttle middleware, named limiters, custom 429s, response headers, and limiting jobs and commands."
language: en
published_at: 2027-02-19 09:00:00
is_published: true
tags: [laravel, rate-limiting, api, redis, backend]
---

I once shipped an API with `throttle:60,1` slapped on the route group and called it done. It worked, right up until the day a customer authenticated three separate service accounts behind one office IP and started getting throttled as a group. That bug taught me more about **Laravel rate limiting** than any docs page had, because the fix wasn't the middleware at all — it was the named limiter behind it, and the `->by()` key I hadn't thought about.

This post is the framework-mechanics companion to the [algorithm-focused rate limiting article](/blog/api-rate-limiting-token-bucket-vs-fixed-window). If you want to know how a token bucket differs from a fixed window internally, read that one. Here I'm staying inside Laravel: the `throttle` middleware, the `RateLimiter` facade, custom `429` responses, the headers Laravel sets for you, and how to throttle things that aren't HTTP requests at all. Everything below is accurate for Laravel 11 and 12.

## The throttle middleware, the fast way

The quickest form is the inline one. Apply it to a route or group and you're limiting immediately:

```php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
});
```

The two numbers are `maxAttempts` and `decayMinutes`. So `throttle:60,1` means 60 requests per one minute. When a client blows past that, Laravel short-circuits the request with a `429 Too Many Requests` before your controller runs.

By default this keys on the authenticated user's ID, falling back to the request IP for guests. That fallback is exactly where my office-IP bug came from, and it's the reason inline throttling gets you started but rarely gets you finished.

If you're fuzzy on where `throttle` sits in the request lifecycle, the [middleware guide](/blog/laravel-middleware-complete-practical-guide) covers the before/after model that makes this click.

## Named limiters: where the real control lives

Inline strings are fine for a hard-coded cap. The moment the limit needs to depend on *who* is calling (plan tier, authenticated versus guest, a specific header), you define a named limiter and reference it by name.

Named limiters live in a service provider's `boot()` method. A fresh Laravel app registers an `api` limiter in `App\Providers\AppServiceProvider` (older skeletons used a dedicated `RouteServiceProvider`; either works). Here's the shape:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
}
```

Then you point the middleware at the name instead of raw numbers:

```php
Route::middleware('throttle:api')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

A few things worth spelling out:

- **`Limit::perMinute(60)`** builds the limit. There's also `perHour`, `perDay`, `perSecond`, and `perMinutes(5, 100)` for arbitrary windows. Pick the granularity that matches how bursty your clients actually are.
- **`->by(...)`** sets the bucket key. This is the important one. `by($request->user()?->id ?: $request->ip())` gives each logged-in user their own bucket and lumps anonymous traffic by IP. Change this string and you change who shares a limit.
- The closure runs **per request**, so you can read the user and branch on them.

That last point is what makes tiered limits trivial:

```php
RateLimiter::for('api', function (Request $request) {
    $user = $request->user();

    return $user?->onProPlan()
        ? Limit::perMinute(300)->by($user->id)
        : Limit::perMinute(60)->by($user?->id ?: $request->ip());
});
```

Pro accounts get 300 a minute, everyone else 60. No middleware changes, no `if` in the controller.

## Custom 429 responses

The default `429` is a plain error. For an API you almost always want a JSON body clients can parse. `Limit` takes a `->response()` callback:

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'message' => 'Slow down, you have hit the rate limit.',
                'retry_after' => $headers['Retry-After'] ?? null,
            ], 429, $headers);
        });
});
```

Notice the `$headers` argument. Laravel hands you the throttle headers it computed, and passing them into your response keeps `Retry-After` and friends intact. Drop them and clients lose the one signal that tells them when to come back.

## The headers Laravel sets for you

This is the part people miss, then reimplement by hand for no reason. On every throttled route Laravel adds:

- **`X-RateLimit-Limit`**: the maximum attempts allowed in the window.
- **`X-RateLimit-Remaining`**: how many the client has left right now.

And once the client is over the line, it adds two more to the `429`:

- **`Retry-After`**: seconds until they can try again.
- **`X-RateLimit-Reset`**: a UNIX timestamp for the same moment.

A well-behaved client reads `X-RateLimit-Remaining`, backs off before it hits zero, and honors `Retry-After` when it doesn't. You get this for free; your job is mostly not to strip it. If you want to see the backoff side of this contract in the client, the [algorithm article](/blog/api-rate-limiting-token-bucket-vs-fixed-window) walks through `Retry-After` in detail.

## Multiple and segmented limits

A limiter closure can return an **array** of limits, and all of them apply at once. This is how you defend two dimensions at once. Say a generous per-user cap plus a stricter per-IP cap to blunt a single machine spraying many accounts:

```php
RateLimiter::for('api', function (Request $request) {
    return [
        Limit::perMinute(500)->by($request->user()?->id ?: $request->ip()),
        Limit::perMinute(100)->by($request->ip()),
    ];
});
```

Because each `Limit` has its own `by()` key, they count independently. A user can burn through their 500 while a shared NAT'd IP still caps out at 100 across everyone behind it. That second limit is precisely what my office-IP scenario needed: not as the *only* key, but as a backstop.

You can also skip limiting entirely for trusted callers with `Limit::none()`:

```php
RateLimiter::for('api', function (Request $request) {
    if ($request->user()?->isInternalService()) {
        return Limit::none();
    }

    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

## Rate limiting outside HTTP: jobs and commands

The `throttle` middleware only helps with HTTP. But plenty of things need throttling: a queued job hitting a third-party API, an Artisan command that sends emails, a webhook fan-out. For those, use the `RateLimiter` facade directly.

`RateLimiter::attempt()` runs a callback only if the key is under its limit, and returns `false` otherwise:

```php
use Illuminate\Support\Facades\RateLimiter;

$executed = RateLimiter::attempt(
    key: 'send-newsletter:' . $user->id,
    maxAttempts: 5,
    callback: function () use ($user) {
        Mail::to($user)->send(new Newsletter);
    },
    decaySeconds: 60,
);

if (! $executed) {
    // Over the limit for this minute; requeue or skip.
    return;
}
```

If you want to check first and decide yourself, `tooManyAttempts()` plus `hit()` gives you manual control:

```php
$key = 'import-feed:' . $source->id;

if (RateLimiter::tooManyAttempts($key, maxAttempts: 3)) {
    $seconds = RateLimiter::availableIn($key);
    $this->release($seconds); // requeue the job to run later
    return;
}

RateLimiter::hit($key, decaySeconds: 60);

// ...do the actual work
```

`availableIn()` gives you the seconds until the key frees up, which maps neatly onto a job's `release()` so you're not busy-looping. `remaining()` and `clear()` round out the API when you need them.

## Use Redis in production

Here's the trap. The rate limiter counts through Laravel's **cache**. If your cache store is `file` or `array`, every web server counts in isolation. Three app servers behind a load balancer means a client effectively gets triple the limit, and your careful `throttle:60,1` becomes `throttle:180,1` in practice.

Point the cache at a shared **Redis** store so all instances increment the same counter:

```php
// config/cache.php, or via CACHE_STORE=redis in .env
'default' => env('CACHE_STORE', 'redis'),
```

Redis is also atomic, so concurrent requests can't race the counter into an undercount. For a broader look at how to lean on Redis well, the [Redis caching patterns post](/blog/redis-caching-patterns) is a good next stop. On a single box in local dev, none of this matters — but "works on my laptop" is exactly how the multiplier ships to production unnoticed.

## Pitfalls I've actually hit

- **Keying only by IP.** Corporate NATs, mobile carriers, and CI runners share IPs. Key by user ID when you can, and treat IP as a fallback or a secondary backstop, not the primary bucket.
- **Non-shared cache in a cluster.** Covered above, and worth repeating because the symptom (limits that are "too loose") looks like a config typo, not an architecture problem.
- **Stripping the headers on a custom `429`.** If you build your own response body, pass the `$headers` array through. Clients rely on `Retry-After`.
- **Registering the limiter in the wrong place.** The `for()` call has to run during boot. Define it before the routes are handled or `throttle:api` will throw an "unknown limiter" error.
- **Throttling jobs with the HTTP middleware mindset.** Jobs don't pass through middleware. Reach for `RateLimiter::attempt()` / `tooManyAttempts()` instead of trying to bolt HTTP throttling onto the queue.

## FAQ

### What's the difference between `throttle:60,1` and `throttle:api`?

The first is an inline limit: 60 attempts per 1 minute, keyed by user-or-IP automatically. The second references a named limiter you defined with `RateLimiter::for('api', ...)`, which lets the limit depend on the request: tiers, custom keys, multiple limits, custom responses. Start inline, move to named the moment the logic needs a brain.

### Which rate limiting algorithm does Laravel use?

Laravel's limiter is a fixed-window counter backed by the cache: it increments a key and expires it after the decay period. That's simpler than a token bucket and can allow bursts at window boundaries. The trade-offs between fixed window, sliding window, and token bucket are covered in the [token bucket vs fixed window article](/blog/api-rate-limiting-token-bucket-vs-fixed-window).

### Do I need Redis for rate limiting to work?

No — it works with any cache store on a single server. You need Redis (or another shared, atomic store) once you run more than one app instance, otherwise each server keeps its own count and clients get more than their allotted quota.

### How do I rate limit a queued job or Artisan command?

Skip the middleware and use the `RateLimiter` facade directly. `RateLimiter::attempt()` runs your callback only if under the limit; `tooManyAttempts()` combined with `hit()` and `availableIn()` lets you check, act, and requeue with the right delay.

## Wrapping up

The mental model that fixed my thinking: the `throttle` middleware is just a thin HTTP wrapper around a limiter, and the limiter is the thing worth designing. Define named limiters with `RateLimiter::for()`, choose your `->by()` key deliberately, return an array of limits when you need to defend two dimensions, and pass the header array through any custom `429`. Then push the cache to Redis so the numbers mean the same thing across every server.

Concretely: pick your two riskiest routes today, replace their inline `throttle` strings with a named limiter keyed by user ID plus an IP backstop, and confirm `CACHE_STORE=redis` in your production `.env`. That combination would have saved me the evening I spent staring at throttled service accounts — and it takes about ten minutes.